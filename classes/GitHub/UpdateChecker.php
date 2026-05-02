<?php defined('SYSPATH') or die('No direct script access.');

class Github_UpdateChecker {

    /**
     * Универсальный HTTP GET с поддержкой cURL и file_get_contents
     * @param string $url
     * @return string|null содержимое или null при ошибке
     */
    private static function http_get($url) {
        // Пробуем cURL
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Kohana-City-UpdateChecker/1.0');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $data = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            if ($data === false) {
                error_log("cURL error for $url: $error");
                return null;
            }
            return $data;
        }

        // Fallback: file_get_contents
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => ['User-Agent: Kohana-City-UpdateChecker/1.0'],
                'timeout' => 5
            ]
        ];
        $context = stream_context_create($opts);
        $data = @file_get_contents($url, false, $context);
        if ($data === false) {
            error_log("file_get_contents failed for $url");
            return null;
        }
        return $data;
    }

    /**
     * Получить последнюю версию модуля с GitHub
     * @param string $moduleName имя модуля (ключ из конфига)
     * @return string|false версия или false при ошибке
     */
    public static function get_latest_version($moduleName) {
        $config = Kohana::$config->load('github_updates');
        if (!$config['enabled']) {
            return false;
        }

        $repo = isset($config['repositories'][$moduleName]) ? $config['repositories'][$moduleName] : null;
        if (!$repo) {
            return false;
        }

        // Используем Kohana Cache вместо файлового кэша
        $cache_key = 'github_version_' . $moduleName;
        $cache_lifetime = isset($config['cache_lifetime']) ? $config['cache_lifetime'] : 3600;
        
        try {
            // Пытаемся получить из Kohana Cache
            $cached = Cache::instance()->get($cache_key);
            if ($cached !== null) {
                return $cached;
            }
        } catch (Exception $e) {
            error_log("Cache read error for $cache_key: " . $e->getMessage());
        }

        // Получаем версию из GitHub
        $version = false;
        if ($config['version_source'] === 'releases') {
            $version = self::get_latest_release_version($repo);
        } else {
            $version = self::get_version_from_file($repo, $config);
        }

        // Сохраняем в Kohana Cache
        if ($version) {
            try {
                Cache::instance()->set($cache_key, $version, $cache_lifetime);
            } catch (Exception $e) {
                error_log("Cache write error for $cache_key: " . $e->getMessage());
            }
        }

        return $version;
    }

    /**
     * Получить последнюю версию через GitHub Releases API
     * @param string $repo
     * @return string|false
     */
    private static function get_latest_release_version($repo) {
        $url = "https://api.github.com/repos/{$repo}/releases/latest";
        $response = self::http_get($url);
        if (!$response) {
            return false;
        }
        $data = json_decode($response, true);
        if (!isset($data['tag_name'])) {
            return false;
        }
        return ltrim($data['tag_name'], 'v');
    }

    /**
     * Получить версию из файла version.txt в репозитории
     * @param string $repo
     * @param array $config
     * @return string|false
     */
    private static function get_version_from_file($repo, $config) {
        $url = str_replace('{repo}', $repo, $config['version_file_url_pattern']);
        $content = self::http_get($url);
        if ($content === null) {
            return false;
        }
        return trim($content);
    }
    
    /**
     * Очистить кэш для конкретного модуля
     * @param string $moduleName
     */
    public static function clear_cache($moduleName = null) {
        try {
            if ($moduleName) {
                Cache::instance()->delete('github_version_' . $moduleName);
            } else {
                // Очистить все кэши GitHub (если нужно)
                // Это зависит от драйвера кэша
            }
        } catch (Exception $e) {
            error_log("Cache clear error: " . $e->getMessage());
        }
    }
}