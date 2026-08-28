<?php defined('SYSPATH') or die('No direct script access.');

class Controller_About extends Controller_Template {

    public $template = 'template';
    
    public function before()
    {
        parent::before();
        $this->template->title = __('О системе');
    }

    public function action_index()
    {
        $config = Kohana::$config->load('about');
        
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
        
        $content = View::factory('about/index')
            ->set('developer', $developer_info)
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
                    
                    $modules[$module_name] = array(
                        'name' => $module_name,
                        'name_display' => $this->format_module_name($module_name),
                        'version' => $version,
                        'path' => $module_path,
                        'is_active' => $is_active,
                        'version_defined' => defined($const_name),
                        'has_init' => $has_init,
                        'version_source' => $this->get_version_source($module_path, $const_name, $version)
                    );
                }
            }
        }
        
        ksort($modules);
        return $modules;
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
        // Проверяем version.php в корне модуля
        $version_file = $module_path . 'version.php';
        if (file_exists($version_file)) {
            $version_data = include $version_file;
            if (is_array($version_data) && isset($version_data['version'])) {
                return $version_data['version'];
            } elseif (is_string($version_data)) {
                return $version_data;
            }
        }
        
        // Проверяем config/version.php
        $config_file = $module_path . 'config/version.php';
        if (file_exists($config_file)) {
            $config = include $config_file;
            if (isset($config['version'])) {
                return $config['version'];
            }
        }
        
        // Проверяем файл VERSION
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
}