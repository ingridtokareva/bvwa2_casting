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

    // Klíč pro šifrování osobních údajů a zpráv (AES-256-GCM), base64 z 32 náhodných bajtů.
    // Vygeneruj příkazem:   php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
    // POZOR: bez tohoto klíče nejdou šifrovaná data přečíst. Nikdy ho necommituj
    // a po vytvoření dat ho neměň (jinak se data stanou nečitelnými).
    'encryption_key' => '',

    // Automatické odhlášení po nečinnosti (sekundy). Zadání: 20 minut.
    'session_timeout' => 20 * 60,

    // Nahrávání profilových fotek (výsledek se vždy uloží jako JPEG, kvalita 90, šířka 800 px)
    'upload' => [
        'dir'        => __DIR__ . '/../uploads/profile_photos/',
        'max_size'   => 5 * 1024 * 1024, // 5 MB
        'width'      => 800,
        'quality'    => 90,
        'mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-ms-bmp', 'image/tiff', 'image/webp'],
    ],
];
