<?php defined('SYSPATH') or die('No direct script access.');

class UpdateInstaller {
    
    /**
     * Скачать обновление модуля
     * @param string $moduleName
     * @return array результат операции
     */
    public static function download_update($moduleName) {
        $config = Kohana::$config->load('github_updates');
        
        if (!$config['enabled']) {
            return array('success' => false, 'error' => 'Обновления отключены в конфигурации');
        }
        
        $repo = isset($config['repositories'][$moduleName]) ? $config['repositories'][$moduleName] : null;
        if (!$repo) {
            return array('success' => false, 'error' => 'Репозиторий не найден для модуля: ' . $moduleName);
        }
        
        // Получаем информацию о последнем релизе
        $release_info = self::get_latest_release_info($repo);
        if (!$release_info) {
            return array('success' => false, 'error' => 'Не удалось получить информацию о релизе');
        }
        
        // Создаем временную директорию
        $temp_dir = $config['updates']['temp_dir'];
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        $zip_path = $temp_dir . $moduleName . '_' . $release_info['version'] . '.zip';
        
        // Скачиваем ZIP архив
        if (!self::download_file($release_info['zipball_url'], $zip_path)) {
            return array('success' => false, 'error' => 'Не удалось скачать архив');
        }
        
        // Извлекаем и возвращаем путь к извлеченным файлам
        $extract_path = $temp_dir . $moduleName . '_' . $release_info['version'] . '_extracted';
        if (self::extract_zip($zip_path, $extract_path)) {
            return array(
                'success' => true,
                'version' => $release_info['version'],
                'zip_path' => $zip_path,
                'extract_path' => $extract_path,
                'repo_info' => $release_info
            );
        }
        
        return array('success' => false, 'error' => 'Не удалось распаковать архив');
    }
    
    /**
     * Установить обновление модуля
     * @param string $moduleName
     * @return array результат операции
     */
    public static function install_update($moduleName) {
        try {
            // Находим путь к модулю
            $module_path = self::get_module_path($moduleName);
            if (!$module_path) {
                return array('success' => false, 'error' => 'Модуль не найден');
            }
            
            // Скачиваем обновление
            $download_result = self::download_update($moduleName);
            if (!$download_result['success']) {
                return $download_result;
            }
            
            // Создаем бэкап
            $backup_result = self::create_backup($moduleName, $module_path);
            if (!$backup_result['success']) {
                // Очищаем временные файлы
                self::cleanup_temp_files($download_result);
                return array('success' => false, 'error' => 'Не удалось создать бэкап: ' . $backup_result['error']);
            }
            
            // Устанавливаем обновление
            $install_result = self::replace_module_files($moduleName, $module_path, $download_result);
            
            if ($install_result['success']) {
                // Очищаем кэш обновлений
                GitHub_UpdateChecker::clear_cache($moduleName);
                
                // Обновляем версию в init.php если нужно
                self::update_module_version($moduleName, $download_result['version']);
                
                return array(
                    'success' => true,
                    'message' => 'Модуль успешно обновлен до версии ' . $download_result['version'],
                    'backup_path' => $backup_result['backup_path']
                );
            } else {
                // Восстанавливаем из бэкапа при ошибке
                self::restore_from_backup($moduleName, $backup_result['backup_path'], $module_path);
                return $install_result;
            }
            
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        } finally {
            // Очищаем временные файлы
            if (isset($download_result)) {
                self::cleanup_temp_files($download_result);
            }
        }
    }
    
    /**
     * Получить информацию о последнем релизе
     */
    private static function get_latest_release_info($repo) {
        $url = "https://api.github.com/repos/{$repo}/releases/latest";
        $response = self::http_get($url);
        
        if (!$response) {
            return false;
        }
        
        $data = json_decode($response, true);
        if (!isset($data['tag_name']) || !isset($data['zipball_url'])) {
            return false;
        }
        
        return array(
            'version' => ltrim($data['tag_name'], 'v'),
            'zipball_url' => $data['zipball_url'],
            'tag_name' => $data['tag_name'],
            'body' => isset($data['body']) ? $data['body'] : ''
        );
    }
    
    /**
     * Скачать файл через cURL
     */
    private static function download_file($url, $destination) {
        $fp = fopen($destination, 'w+');
        if (!$fp) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Kohana-UpdateInstaller/1.0');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        
        if ($result === false) {
            unlink($destination);
            error_log("Download failed: " . $error);
            return false;
        }
        
        return true;
    }
    
    /**
     * Извлечь ZIP архив
     */
    private static function extract_zip($zip_path, $extract_path) {
        if (!extension_loaded('zip')) {
            error_log("ZIP extension not loaded");
            return false;
        }
        
        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            return false;
        }
        
        // Создаем временную папку для извлечения
        if (is_dir($extract_path)) {
            self::delete_directory($extract_path);
        }
        mkdir($extract_path, 0755, true);
        
        // Извлекаем все файлы
        $zip->extractTo($extract_path);
        $zip->close();
        
        // GitHub добавляет папку с именем репозитория, перемещаем содержимое вверх
        $directories = glob($extract_path . '/*', GLOB_ONLYDIR);
        if (count($directories) == 1) {
            $subdir = $directories[0];
            self::merge_directories($subdir, $extract_path);
            rmdir($subdir);
        }
        
        return true;
    }
    
    /**
     * Получить путь к модулю
     */
    private static function get_module_path($moduleName) {
        $modules = Kohana::modules();
        
        if (isset($modules[$moduleName])) {
            return $modules[$moduleName];
        }
        
        // Поиск в директории MODPATH
        $modpath = rtrim(MODPATH, DIRECTORY_SEPARATOR);
        $module_path = $modpath . DIRECTORY_SEPARATOR . $moduleName;
        if (is_dir($module_path)) {
            return $module_path;
        }
        
        return false;
    }
    
    /**
     * Создать бэкап модуля
     */
    private static function create_backup($moduleName, $module_path) {
        $config = Kohana::$config->load('github_updates');
        
        if (!$config['updates']['backup_enabled']) {
            return array('success' => true, 'backup_path' => null);
        }
        
        $backup_dir = $config['updates']['backup_dir'];
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $backup_filename = $moduleName . '_' . date('Y-m-d_H-i-s') . '.zip';
        $backup_path = $backup_dir . $backup_filename;
        
        if (!self::create_zip($module_path, $backup_path)) {
            return array('success' => false, 'error' => 'Не удалось создать ZIP архив бэкапа');
        }
        
        // Удаляем старые бэкапы (оставляем последние 5)
        self::cleanup_old_backups($moduleName, $backup_dir, 5);
        
        return array('success' => true, 'backup_path' => $backup_path);
    }
    
    /**
     * Создать ZIP архив
     */
    private static function create_zip($source, $destination) {
        if (!extension_loaded('zip')) {
            return false;
        }
        
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }
        
        $source = rtrim($source, '/');
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($source) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        return $zip->close();
    }
    
    /**
     * Заменить файлы модуля
     */
    private static function replace_module_files($moduleName, $module_path, $download_result) {
        $extract_path = $download_result['extract_path'];
        
        // Режим обслуживания
        self::enable_maintenance_mode();
        
        try {
            // Удаляем старые файлы
            self::delete_directory_contents($module_path);
            
            // Копируем новые файлы
            if (!self::merge_directories($extract_path, $module_path)) {
                return array('success' => false, 'error' => 'Не удалось скопировать файлы');
            }
            
            return array('success' => true);
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        } finally {
            self::disable_maintenance_mode();
        }
    }
    
    /**
     * HTTP GET запрос
     */
    private static function http_get($url) {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Kohana-UpdateInstaller/1.0');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => ['User-Agent: Kohana-UpdateInstaller/1.0'],
                'timeout' => 10
            ]
        ]);
        
        return file_get_contents($url, false, $context);
    }
    
    /**
     * Вспомогательные методы
     */
    private static function delete_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? self::delete_directory($path) : unlink($path);
        }
        return rmdir($dir);
    }
    
    private static function delete_directory_contents($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? self::delete_directory($path) : unlink($path);
        }
    }
    
    private static function merge_directories($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $files = array_diff(scandir($source), array('.', '..'));
        foreach ($files as $file) {
            $source_path = $source . DIRECTORY_SEPARATOR . $file;
            $dest_path = $destination . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($source_path)) {
                if (!self::merge_directories($source_path, $dest_path)) {
                    return false;
                }
            } else {
                if (!copy($source_path, $dest_path)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    private static function cleanup_temp_files($download_result) {
        if (isset($download_result['zip_path']) && file_exists($download_result['zip_path'])) {
            unlink($download_result['zip_path']);
        }
        if (isset($download_result['extract_path']) && is_dir($download_result['extract_path'])) {
            self::delete_directory($download_result['extract_path']);
        }
    }
    
    private static function cleanup_old_backups($moduleName, $backup_dir, $keep_count) {
        $backups = glob($backup_dir . $moduleName . '_*.zip');
        if (count($backups) > $keep_count) {
            usort($backups, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            $to_delete = array_slice($backups, 0, count($backups) - $keep_count);
            foreach ($to_delete as $backup) {
                unlink($backup);
            }
        }
    }
    
    private static function restore_from_backup($moduleName, $backup_path, $module_path) {
        if (!$backup_path || !file_exists($backup_path)) {
            return false;
        }
        
        $temp_restore = APPPATH . 'cache/temp/restore_' . $moduleName;
        self::extract_zip($backup_path, $temp_restore);
        self::delete_directory_contents($module_path);
        self::merge_directories($temp_restore, $module_path);
        self::delete_directory($temp_restore);
        
        return true;
    }
    
    private static function update_module_version($moduleName, $new_version) {
        $init_file = self::get_module_path($moduleName) . '/init.php';
        if (file_exists($init_file)) {
            $content = file_get_contents($init_file);
            $const_name = strtoupper($moduleName) . '_VERSION';
            $pattern = "/defined\('{$const_name}'\) OR define\('{$const_name}',\s*'[^']*'\)/";
            $replacement = "defined('{$const_name}') OR define('{$const_name}', '{$new_version}')";
            $new_content = preg_replace($pattern, $replacement, $content);
            if ($new_content !== null) {
                file_put_contents($init_file, $new_content);
            }
        }
    }
    
    private static function enable_maintenance_mode() {
        $config = Kohana::$config->load('github_updates');
        $maintenance_file = $config['updates']['maintenance_mode_file'];
        file_put_contents($maintenance_file, date('Y-m-d H:i:s'));
    }
    
    private static function disable_maintenance_mode() {
        $config = Kohana::$config->load('github_updates');
        $maintenance_file = $config['updates']['maintenance_mode_file'];
        if (file_exists($maintenance_file)) {
            unlink($maintenance_file);
        }
    }
}