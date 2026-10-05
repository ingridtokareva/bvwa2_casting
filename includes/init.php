<?php
/**
 * Tento soubor se načítá úplně na začátku KAŽDÉ stránky:
 *     require_once __DIR__ . '/includes/init.php';
 * (ve složce admin/ použij '/../includes/init.php')
 */

// --- konfigurace ---
$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    die('Chybí config/config.php. Zkopíruj config/config.example.php a vyplň údaje k databázi.');
}
$GLOBALS['app_config'] = require $configFile;

function config(string $key, $default = null)
{
    return $GLOBALS['app_config'][$key] ?? $default;
}

// --- zobrazování chyb ---
if (config('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

// --- bezpečná session ---
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

// --- ostatní části ---
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
