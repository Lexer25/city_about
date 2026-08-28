<?php
// MODPATH/about/init.php
defined('ABOUT_VERSION') OR define('ABOUT_VERSION', '1.0.7');

// Добавляем маршрут для главной страницы модуля
Route::set('about', 'about')
    ->defaults(array(
        'controller' => 'about',
        'action' => 'index',
    ));

// Добавляем пункт меню
Kohana::$config->load('menu')
    ->set('about', array(
        'title' => 'О программе',
        'url' => 'about',
        'icon' => 'fa-cog',
        'order' => 100,
    ));