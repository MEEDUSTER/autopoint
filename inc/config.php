<?php
/**
 * AutoPoint — ЛР6: налаштування бекенду (PHP).
 * Підключається в усіх PHP-сторінках через require_once.
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Kyiv');

// ----- Шляхи -----
define('APP_ROOT', dirname(__DIR__));
define('SURVEY_DIR', APP_ROOT . '/survey');          // файли з відповідями
define('DB_FILE', APP_ROOT . '/db/survey.sqlite');   // база даних SQLite

// ----- Адміністратор -----
// Пароль зберігається лише у вигляді хешу password_hash().
// Згенерувати новий хеш: php -r "echo password_hash('новий_пароль', PASSWORD_DEFAULT);"
define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$12$P9ufx8zo5LrGghc07SmfWu6PNxdOSBhTsXoeDqSyRruj3DUT.2WZC');

define('SITE_NAME', 'AutoPoint');
define('SURVEY_TITLE', 'Яке авто обирають студенти');

// ----- Питання анкети -----
// type: radio | select | checkbox | textarea
const QUESTIONS = [
    'budget' => [
        'label'   => 'Який бюджет ви готові витратити на перше авто?',
        'type'    => 'radio',
        'options' => [
            'lt5'    => 'до 5 000 $',
            '5-10'   => '5 000 – 10 000 $',
            '10-20'  => '10 000 – 20 000 $',
            'gt20'   => 'понад 20 000 $',
        ],
    ],
    'body' => [
        'label'   => 'Який тип кузова вам найбільше підходить?',
        'type'    => 'select',
        'options' => [
            'sedan'     => 'Седан',
            'hatchback' => 'Хетчбек',
            'liftback'  => 'Ліфтбек',
            'wagon'     => 'Універсал',
            'suv'       => 'Кросовер / SUV',
        ],
    ],
    'fuel' => [
        'label'   => 'Який тип двигуна ви б обрали?',
        'type'    => 'radio',
        'options' => [
            'petrol'   => 'Бензин',
            'diesel'   => 'Дизель',
            'lpg'      => 'Газ / бензин (ГБО)',
            'hybrid'   => 'Гібрид',
            'electric' => 'Електро',
        ],
    ],
    'priorities' => [
        'label'   => 'Що для вас найважливіше в авто? (оберіть від 1 до 3)',
        'type'    => 'checkbox',
        'max'     => 3,
        'options' => [
            'economy'     => 'Економність',
            'reliability' => 'Надійність',
            'safety'      => 'Безпека',
            'comfort'     => 'Комфорт',
            'dynamics'    => 'Динаміка',
            'design'      => 'Дизайн',
            'service'     => 'Недороге обслуговування',
        ],
    ],
    'model' => [
        'label'       => 'Яку конкретну модель ви б купили і чому?',
        'type'        => 'textarea',
        'maxlength'   => 500,
        'placeholder' => 'Наприклад: Skoda Octavia — великий багажник і простий сервіс',
    ],
];
