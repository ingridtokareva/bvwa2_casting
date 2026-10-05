<?php
/**
 * Připojení k databázi přes PDO.
 * Použití:  $pdo = db();
 *           $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
 *           $stmt->execute([$id]);
 * VŽDY používej prepared statements (ochrana proti SQL injection).
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $c = config('db');
        $dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";

        try {
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            die(config('debug')
                ? 'Chyba připojení k databázi: ' . htmlspecialchars($e->getMessage())
                : 'Služba je dočasně nedostupná.');
        }
    }

    return $pdo;
}
