<?php defined('SYSPATH') or die('No direct script access.');

return array(
    'enabled' => true,                     // Включаем для получения версий
    'cache_lifetime' => 3600,              // время кэша 1 час
    'repositories' => array(
        'about'  => 'Lexer25/city_about',
        'apb'  => 'Lexer25/city_apb',
        'cfg'  => 'Lexer25/city_cfg',
        'dbservice'  => 'Lexer25/city_dbservice',
        'dbsetting'  => 'Lexer25/city_dbsetting',
        'dev'  => 'Lexer25/city_dev',
        'door'  => 'Lexer25/city_door',
        'email'  => 'Lexer25/city_email',
        'eventConfig'  => 'Lexer25/city_eventConfig',
        'events'  => 'Lexer25/city_events',
        'eximdata'  => 'Lexer25/city_eximdata',
        'identifier'  => 'Lexer25/city_identifier',
        'parsec'  => 'Lexer25/city_parsec',
        'people'  => 'Lexer25/city_people',
        'setting'  => 'Lexer25/city_setting',
    ),
    'version_source' => 'file', // 'releases' (релизы), либо 'file' (version.txt)
    'version_file_url_pattern' => 'https://raw.githubusercontent.com/{repo}/main/version.txt',
);