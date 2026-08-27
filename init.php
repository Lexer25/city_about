<?php
// MODPATH/about/init.php
defined('ABOUT_VERSION') OR define('ABOUT_VERSION', '1.0.6');

// Дополнительная инициализация модуля About
// Можно добавить загрузку дополнительных файлов если нужно

// Добавьте в файл bootstrap.php после определения маршрутов
Route::set('about_install', 'about/install_update/<module>', array('module' => '.*'))
    ->defaults(array(
        'controller' => 'about',
        'action' => 'install_update',
    ));

Route::set('about_download', 'about/download_update/<module>', array('module' => '.*'))
    ->defaults(array(
        'controller' => 'about',
        'action' => 'download_update',
    ));
	
	
Kohana::$config->load('menu')
    ->set('about', array(
        'title' => 'О программе',
        'url' => 'about',
        'icon' => 'fa-cog',
        'order' => 100,
       
    ));