<?php defined('SYSPATH') or die('No direct script access.');

class UpdateInstaller {
    
    public static function install_update($moduleName) {
        return array(
            'success' => true,
            'message' => 'Тестовое обновление для ' . $moduleName
        );
    }
}