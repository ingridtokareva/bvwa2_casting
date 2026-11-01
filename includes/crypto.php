<?php
/**
 * Šifrování osobních údajů a zpráv (AES-256-GCM).
 *
 * Použití (i pro zprávy - Ingrid):
 *     $cipher = encryptText('Ahoj');      // do DB
 *     $plain  = decryptText($cipher);     // z DB
 *
 * GCM je "authenticated" režim: kromě šifrování hlídá i to, že se data v DB
 * nikdo nepokusil upravit (při změně se dešifrování nepovede a vrátí null).
 * Formát uložené hodnoty: base64( IV 12 B | TAG 16 B | šifrový text ).
 * Pro každou hodnotu se generuje nový náhodný IV.
 */

/** Klíč z config.php (base64 z 32 bajtů). Když chybí, aplikace se zastaví. */
function encryptionKey(): string
{
    static $key = null;

    if ($key === null) {
        $decoded = base64_decode((string) config('encryption_key', ''), true);
        if ($decoded === false || strlen($decoded) !== 32) {
            http_response_code(500);
            die('Chybí platný "encryption_key" v config/config.php. Vygeneruj ho: '
                . 'php -r "echo base64_encode(random_bytes(32));"');
        }
        $key = $decoded;
    }

    return $key;
}

/** Zašifruje text. Prázdný řetězec a null se nechají beze změny (do DB jde NULL). */
function encryptText(?string $plain): ?string
{
    if ($plain === null || $plain === '') {
        return null;
    }

    $iv     = random_bytes(12);
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', encryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) {
        throw new RuntimeException('Šifrování se nepovedlo.');
    }

    return base64_encode($iv . $tag . $cipher);
}

/** Dešifruje text z DB. Při chybě (poškozená data, špatný klíč) vrátí null. */
function decryptText(?string $data): ?string
{
    if ($data === null || $data === '') {
        return null;
    }

    $raw = base64_decode($data, true);
    if ($raw === false || strlen($raw) < 28) {
        return null;
    }

    $plain = openssl_decrypt(
        substr($raw, 28), 'aes-256-gcm', encryptionKey(), OPENSSL_RAW_DATA,
        substr($raw, 0, 12), substr($raw, 12, 16)
    );

    return $plain === false ? null : $plain;
}

/**
 * Otisk e-mailu (HMAC-SHA256) pro kontrolu unikátnosti a přihlášení e-mailem.
 * Zašifrovaný e-mail se nedá hledat, protože každé šifrování dá jiný výsledek.
 */
function emailHash(string $email): string
{
    return hash_hmac('sha256', mb_strtolower(trim($email)), encryptionKey());
}
