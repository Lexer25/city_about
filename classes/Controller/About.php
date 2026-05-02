<?php defined('SYSPATH') or die('No direct script access.');

class Controller_About extends Controller_Template {

    public $template = 'template';

    public function before()
    {
        parent::before();
        
        // Устанавливаем заголовок страницы
        $this->template->title = __('О системе');
    }

    public function action_index()
    {
        // При открытии окна пытаемся показать кэшированные версии с GitHub
        $config = Kohana::$config->load('github_updates');
        $original_enabled = $config['enabled'];
        
        // Получаем информацию о разработчике
        $developer_info = array(
            'name' => 'Разработчик системы',
            'company' => 'ООО "Артсек"',
            'email' => 'support@artsec.ru',
            'website_1' => 'http://artsec.ru',
            'website_2' => 'http://artonit.ru'
        );
        
        // Получаем текущую версию
        $current_version = $this->get_current_version();
        
        // Получаем список модулей с версиями
        $modules_list = $this->get_all_modules_with_versions();
        
        // Пытаемся получить кэшированные версии с GitHub
        $modules_list = $this->enrich_modules_with_updates($modules_list);
        
        // Добавляем кнопку "Проверить обновления" в данные для представления
        $content = View::factory('about/index')
            ->set('developer', $developer_info)
            ->set('current_version', $current_version)
            ->set('modules_list', $modules_list)
            ->set('check_updates_url', URL::site('about/check_updates'));
            
        $this->template->content = $content;
    }
    
    /**
     * AJAX-обработчик для проверки обновлений
     */
    public function action_check_updates()
    {
        $this->auto_render = false;
        
        // Включаем проверку обновлений
        $config = Kohana::$config->load('github_updates');
        $original_enabled = $config['enabled'];
        Kohana::$config->load('github_updates')->set('enabled', true);
        
        // Очищаем кэш обновлений, чтобы получить свежие данные
        $this->clear_updates_cache();
        
        // Получаем список модулей
        $modules_list = $this->get_all_modules_with_versions();
        
        // Проверяем обновления
        $modules_list = $this->enrich_modules_with_updates($modules_list);
        
        // Восстанавливаем оригинальное значение
        Kohana::$config->load('github_updates')->set('enabled', $original_enabled);
        
        // Формируем ответ в формате JSON
        $response = array();
        foreach ($modules_list as $module_name => $module) {
            $status = $module['update_status'];
            $response[$module_name] = array(
                'has_update' => $status['has_update'],
                'latest_version' => $status['latest_version'],
                'error' => $status['error'],
                'message' => $status['message'],
                'current_version' => $module['version']
            );
        }
        
        $this->response->headers('Content-Type', 'application/json');
        $this->response->body(json_encode($response));
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
                    
                    $kohana_core_modules = array('auth', 'cache', 'codebench', 'database', 'image', 'minion', 'orm', 'unittest', 'userguide');
                    if (in_array($module_name, $kohana_core_modules) && $version === 'Не определена') {
                        $version = 'Kohana';
                    }
                    
                    if ($has_init && $version === 'Не определена') {
                        $version = $this->get_module_version_alternative($module_path);
                    }
                    
                    $is_active = array_key_exists($module_name, $active_modules);
                    
                    $modules[$module_name] = array(
                        'name' => $module_name,
                        'name_display' => $this->format_module_name($module_name),
                        'version' => $version,
                        'path' => $module_path,
                        'is_active' => $is_active,
                        'version_defined' => defined($const_name),
                        'has_init' => $has_init
                    );
                }
            }
        }
        
        ksort($modules);
        
        return $modules;
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
    
    /**
     * Обогащение модулей информацией об обновлениях
     */
    private function enrich_modules_with_updates($modules_list)
    {
        $config = Kohana::$config->load('github_updates');
        
        // Пытаемся получить данные из кэша
        $cache_key = 'github_updates_statuses';
        try {
            $cached = Cache::instance()->get($cache_key);
            if ($cached !== null && is_array($cached)) {
                // Используем кэшированные данные
                foreach ($modules_list as $name => &$module) {
                    if (isset($cached[$name])) {
                        $module['update_status'] = $cached[$name];
                    } else {
                        $module['update_status'] = array(
                            'has_update' => false,
                            'latest_version' => null,
                            'error' => false,
                            'message' => 'Нет данных'
                        );
                    }
                }
                return $modules_list;
            }
        } catch (Exception $e) {
            // Игнорируем ошибки кэша
        }
        
        // Если кэша нет, проверяем обновления (только если включено)
        if (!$config['enabled']) {
            foreach ($modules_list as &$module) {
                $module['update_status'] = array(
                    'has_update' => false,
                    'latest_version' => null,
                    'error' => false,
                    'message' => 'Проверка обновлений отключена'
                );
            }
            return $modules_list;
        }
        
        // Проверяем обновления через GitHub
        $statuses = $this->check_updates_for_modules($modules_list);
        
        // Сохраняем в кэш
        try {
            Cache::instance()->set($cache_key, $statuses, $config['cache_lifetime']);
        } catch (Exception $e) {
            // Игнорируем ошибки кэша
        }
        
        // Применяем статусы к модулям
        foreach ($modules_list as $name => &$module) {
            if (isset($statuses[$name])) {
                $module['update_status'] = $statuses[$name];
            } else {
                $module['update_status'] = array(
                    'has_update' => false,
                    'latest_version' => null,
                    'error' => false,
                    'message' => 'Нет данных от GitHub'
                );
            }
        }
        
        return $modules_list;
    }
    
    /**
     * Проверка обновлений для модулей
     */
    private function check_updates_for_modules($modules_list)
    {
        $statuses = array();
        
        foreach ($modules_list as $name => $info) {
            $currentVersion = $info['version'];
            if ($currentVersion === 'Не определена' || $currentVersion === 'Kohana') {
                $statuses[$name] = array(
                    'has_update' => false,
                    'latest_version' => null,
                    'error' => false,
                    'message' => 'Версия не определена'
                );
                continue;
            }
            
            // Используем класс из модуля about
            $latest = GitHub_UpdateChecker::get_latest_version($name);
            
            if ($latest === false) {
                $statuses[$name] = array(
                    'has_update' => false,
                    'latest_version' => null,
                    'error' => true,
                    'message' => 'Не удалось проверить'
                );
                continue;
            }
            
            $hasUpdate = version_compare($latest, $currentVersion, '>');
            $statuses[$name] = array(
                'has_update' => $hasUpdate,
                'latest_version' => $latest,
                'error' => false,
                'message' => $hasUpdate ? "Доступна версия {$latest}" : "Актуальная версия"
            );
        }
        
        return $statuses;
    }
    
    /**
     * Очистка кэша обновлений
     */
    private function clear_updates_cache()
    {
        try {
            Cache::instance()->delete('github_updates_statuses');
        } catch (Exception $e) {
            // Игнорируем ошибки кэша
        }
    }
}