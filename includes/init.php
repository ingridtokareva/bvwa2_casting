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

// --- ostatní části ---
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/photo.php';
require_once __DIR__ . '/users.php';

// --- bezpečná session (jen pro web, ne pro skripty z příkazové řádky) ---
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');   // server nepřijme ID session, které sám nevytvořil
    ini_set('session.use_only_cookies', '1');  // ID session nikdy v URL

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,       // JavaScript se k cookie nedostane (ochrana proti krádeži přes XSS)
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();

    if (!empty($_SESSION['user_id'])) {
        $timeout = (int) config('session_timeout', 20 * 60);

        // 1) odhlášení po nečinnosti (výchozí 20 minut)
        $expired = time() - ($_SESSION['last_activity'] ?? 0) > $timeout;
        // 2) ochrana proti únosu session: jiný prohlížeč než při přihlášení = odhlásit
        $hijacked = !hash_equals($_SESSION['fingerprint'] ?? '', sessionFingerprint());

        if ($expired || $hijacked) {
            destroySession();
            session_start();
            session_regenerate_id(true);
            flash($expired
                ? 'Byl jsi odhlášen po 20 minutách nečinnosti. Přihlas se prosím znovu.'
                : 'Z bezpečnostních důvodů jsi byl odhlášen. Přihlas se prosím znovu.', 'error');
        } else {
            $_SESSION['last_activity'] = time();

            // ID session se po 5 minutách obmění, aby případně odposlechnuté ID rychle přestalo platit
            if (time() - ($_SESSION['created'] ?? 0) > 300) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
}
