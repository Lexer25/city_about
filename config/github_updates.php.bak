<?php defined('SYSPATH') or die('No direct script access.');

return array(
    'enabled' => true,                     // глобальный выключатель
    'cache_lifetime' => 3600,              // кэш на 1 час
    'repositories' => array(
        // 'имя_модуля' => 'владелец/репозиторий'
        'about'        => 'Lexer25/city_about',       // пример
        'eventconfig'  => 'ArtSec/city_eventconfig',
        'monitoring'   => 'ArtSec/city_monitoring',
        // добавьте остальные модули, которые публикуются на GitHub
		'people'  => 'Lexer25/city_people',     // <-- добавляем модуль people
    ),
    //'version_source' => 'releases', // 'releases' (теги), либо 'file' (version.txt)
    'version_source' => 'file', // 'releases' (теги), либо 'file' (version.txt)
    'version_file_url_pattern' => 'https://raw.githubusercontent.com/{repo}/main/version.txt',
);