<?php
/**
 * Vzorová konfigurace.
 * Zkopíruj tento soubor jako config/config.php a vyplň své údaje.
 * config.php je v .gitignore, takže se nikdy nenahraje na GitHub.
 */
return [
    'db' => [
        'host'    => 'localhost',
        'name'    => 'casting_db',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // Cesta k projektu v URL, bez lomítka na konci.
    // XAMPP: '/bvwa2_casting'   |   kořen domény: ''
    'base_url' => '/bvwa2_casting',

    // true = zobrazuje chyby (jen při vývoji), false = na ostrém serveru
    'debug' => true,

    // Nahrávání profilových fotek
    'upload' => [
        'dir'       => __DIR__ . '/../uploads/profile_photos/',
        'max_size'  => 2 * 1024 * 1024, // 2 MB
        'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
    ],
];
