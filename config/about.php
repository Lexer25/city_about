<?php defined('SYSPATH') or die('No direct script access.');

return array(
    'developer' => array(
        'name' => 'Разработчик системы',
        'company' => 'ООО "Артсек"',
        'email' => 'support@artsec.ru',
        'website_1' => 'http://artsec.ru',
        'website_2' => 'http://artonit.ru'
    ),
    'exclude_modules' => array(
        // Модули, которые не нужно показывать в списке
    ),
    'core_modules' => array(
        'auth', 'cache', 'codebench', 'database', 
        'image', 'minion', 'orm', 'unittest', 'userguide'
    )
);