<?php defined('SYSPATH') or die('No direct script access.');

class Controller_About extends Controller_Template {

    public $template = 'template';
    
    /**
     * Владелец репозиториев на GitHub.
     */
    private $github_owner = 'Lexer25';
    
    /**
     * Префикс имени репозитория. Полный репозиторий:
     * {owner}/{prefix}{имя_модуля}
     * Пример: Lexer25/city_about
     */
    private $github_repo_prefix = 'city_';
    
    /**
     * Время жизни кэша тегов (в секундах).
     */
    private $tag_cache_ttl = 3600; // 1 час
    
    /**
     * Модули, для которых НЕ нужно проверять GitHub.
     * Обычно это модули ядра Kohana и служебные модули.
     */
    private $skip_github_modules = array(
        'auth', 'cache', 'codebench', 'database',
        'image', 'minion', 'orm', 'unittest', 'userguide',
    );
    
    public function before()
    {
        parent::before();
        $this->template->title = __('О системе');
    }

    public function action_index()
    {
        $config = Kohana::$config->load('about');
        
        $developer_info = $config->developer;
        $user_info = Kohana::$config->load('artonitcity_config');
    
        $current_version = $this->get_current_version();
        $modules_list = $this->get_all_modules_with_versions();
        
        $content = View::factory('about/index')
            ->set('developer', $developer_info)
            ->set('user_info', $user_info)
            ->set('current_version', $current_version)
            ->set('modules_list', $modules_list);
            
        $this->template->content = $content;
    }
    
    /**
     * Получить текущую версию модуля about
     */
    private function get_current_version()
    {
        return defined('ABOUT_VERSION') ? ABOUT_VERSION : '1.0.0';
    }
    
    /**
     * Получить список всех модулей с версиями
     */
    private function get_all_modules_with_versions()
    {
        $modules = array();
        $active_modules = Kohana::modules();
        
        $modpath = rtrim(MODPATH, DIRECTORY_SEPARATOR);
        if (is_dir($modpath)) {
            $items = scandir($modpath);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $item_path = $modpath . DIRECTORY_SEPARATOR . $item;
                if (is_dir($item_path)) {
                    $module_name = $item;
                    $module_path = $item_path . DIRECTORY_SEPARATOR;
                    
                    $init_file = $module_path . 'init.php';
                    $has_init = file_exists($init_file);
                    
                    $const_name = strtoupper($module_name) . '_VERSION';
                    $version = defined($const_name) ? constant($const_name) : 'Не определена';
                    
                    // Специальная обработка для модулей ядра Kohana
                    $kohana_core_modules = array('auth', 'cache', 'codebench', 'database', 'image', 'minion', 'orm', 'unittest', 'userguide');
                    if (in_array($module_name, $kohana_core_modules) && $version === 'Не определена') {
                        $version = 'Kohana';
                    }
                    
                    // Альтернативные источники версии
                    if ($has_init && $version === 'Не определена') {
                        $version = $this->get_module_version_alternative($module_path);
                    }
                    
                    $is_active = array_key_exists($module_name, $active_modules);
                    
                    // Получаем последний тег с GitHub
                    $github = $this->get_github_latest_tag($module_name);
                    
                    $modules[$module_name] = array(
                        'name' => $module_name,
                        'name_display' => $this->format_module_name($module_name),
                        'version' => $version,
                        'path' => $module_path,
                        'is_active' => $is_active,
                        'version_defined' => defined($const_name),
                        'has_init' => $has_init,
                        'version_source' => $this->get_version_source($module_path, $const_name, $version),
                        'latest_tag' => $github['tag'],
                        'latest_tag_url' => $github['url'],
                        'tag_error' => $github['error'],
                    );
                }
            }
        }
        
        ksort($modules);
        return $modules;
    }
    
    /**
     * Сформировать полное имя репозитория для модуля.
     * Например: about -> Lexer25/city_about
     *
     * @param string $module_name Имя модуля
     * @return string
     */
    private function get_github_repo($module_name)
    {
        return $this->github_owner . '/' . $this->github_repo_prefix . $module_name;
    }
    
    /**
     * Получить последний тег модуля с GitHub (с кэшированием).
     * URL репозитория формируется автоматически из имени модуля.
     *
     * @param string $module_name Имя модуля
     * @return array Массив с полями: tag, url, error
     */
    private function get_github_latest_tag($module_name)
    {
        // Пропускаем модули ядра и служебные
        if (in_array($module_name, $this->skip_github_modules)) {
            return array('tag' => null, 'url' => null, 'error' => null);
        }
        
        $repo = $this->get_github_repo($module_name);
        
        // Путь к файлу кэша
        $cache_dir = APPPATH . 'cache' . DIRECTORY_SEPARATOR;
        $cache_file = $cache_dir . 'github_tag_' . $module_name . '.json';
        
        // Проверяем кэш
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $this->tag_cache_ttl) {
            $cached = json_decode(file_get_contents($cache_file), true);
            if (is_array($cached) && array_key_exists('tag', $cached)) {
                return $cached;
            }
        }
        
        // Формируем запрос к GitHub API
        $url = 'https://api.github.com/repos/' . $repo . '/releases/latest';
        
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_USERAGENT      => 'city_parsec-updater',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ));
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            $result = array('tag' => null, 'url' => null, 'error' => 'cURL: ' . $curl_error);
        } elseif ($http_code === 404) {
            $result = array('tag' => null, 'url' => null, 'error' => 'Репозиторий или релиз не найден');
        } elseif ($http_code === 403) {
            $result = array('tag' => null, 'url' => null, 'error' => 'Превышен лимит GitHub API');
        } elseif ($http_code !== 200 || !$response) {
            $result = array('tag' => null, 'url' => null, 'error' => 'HTTP ' . $http_code);
        } else {
            $data = json_decode($response, true);
            if (isset($data['tag_name'])) {
                $result = array(
                    'tag'   => $data['tag_name'],
                    'url'   => isset($data['html_url']) ? $data['html_url'] : null,
                    'error' => null,
                );
            } else {
                $result = array('tag' => null, 'url' => null, 'error' => 'Тег не найден');
            }
        }
        
        // Сохраняем в кэш (включая ошибки, чтобы не долбить API)
        if (is_dir($cache_dir) && is_writable($cache_dir)) {
            file_put_contents($cache_file, json_encode($result));
        }
        
        return $result;
    }
    
    /**
     * Определить источник версии для отображения
     */
    private function get_version_source($module_path, $const_name, $version)
    {
        if (defined($const_name)) {
            return 'Константа в init.php';
        }
        
        if (file_exists($module_path . 'version.php')) {
            return 'Файл version.php';
        }
        
        if (file_exists($module_path . 'config/version.php')) {
            return 'Файл config/version.php';
        }
        
        if (file_exists($module_path . 'VERSION')) {
            return 'Файл VERSION';
        }
        
        return 'Не определен';
    }
    
    /**
     * Альтернативный способ получения версии модуля
     */
    private function get_module_version_alternative($module_path)
    {
        $version_file = $module_path . 'version.php';
        if (file_exists($version_file)) {
            $version_data = include $version_file;
            if (is_array($version_data) && isset($version_data['version'])) {
                return $version_data['version'];
            } elseif (is_string($version_data)) {
                return $version_data;
            }
        }
        
        $config_file = $module_path . 'config/version.php';
        if (file_exists($config_file)) {
            $config = include $config_file;
            if (isset($config['version'])) {
                return $config['version'];
            }
        }
        
        $version_txt = $module_path . 'VERSION';
        if (file_exists($version_txt)) {
            return trim(file_get_contents($version_txt));
        }
        
        return 'Не определена';
    }
    
    /**
     * Форматирование имени модуля
     */
    private function format_module_name($module_name)
    {
        $formatted = preg_replace('/(?<=\\p{L})(?=\\p{Lu})/u', ' ', $module_name);
        $formatted = ucfirst(strtolower($formatted));
        
        $special_names = array(
            'about' => 'О системе',
            'eventconfig' => 'Конфигурация событий',
            'accesscontrol' => 'Контроль доступа',
            'monitoring' => 'Мониторинг',
            'reports' => 'Отчеты',
            'users' => 'Пользователи системы'
        );
        
        $key = strtolower($module_name);
        if (isset($special_names[$key])) {
            return $special_names[$key];
        }
        
        return $formatted;
    }
	
	public function action_clear_tag_cache()
{
    $cache_dir = APPPATH . 'cache' . DIRECTORY_SEPARATOR;
    $files = glob($cache_dir . 'github_tag_*.json');
    $deleted = 0;
    foreach ($files as $file) {
        if (@unlink($file)) {
            $deleted++;
        }
    }
    // Редирект обратно с сообщением
    $this->redirect('about?cache_cleared=' . $deleted);
}
}
