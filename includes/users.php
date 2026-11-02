<?php
/**
 * Práce s tabulkou users (registrace, přihlášení, čtení profilu).
 * Osobní údaje jsou v DB šifrované, proto se z DB nečte přímo, ale přes decryptUser().
 */

/** Rozšifruje řádek z tabulky users. Hash hesla a technické sloupce do výsledku nepatří. */
function decryptUser(array $row): array
{
    return [
        'id'         => (int) $row['id'],
        'login'      => $row['login'],
        'email'      => decryptText($row['email'] ?? null) ?? '',
        'first_name' => decryptText($row['first_name'] ?? null) ?? '',
        'last_name'  => decryptText($row['last_name'] ?? null) ?? '',
        'phone'      => decryptText($row['phone'] ?? null) ?? '',
        'gender'     => decryptText($row['gender'] ?? null) ?? 'undisclosed',
        'bio'        => decryptText($row['bio'] ?? null) ?? '',
        'photo'      => $row['photo'] ?? null,
        'role'       => $row['role'],
        'created_at' => $row['created_at'] ?? null,
    ];
}

/** Celý rozšifrovaný uživatel podle ID (pro zprávy, administraci, ...), nebo null. */
function getUserById(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? decryptUser($row) : null;
}

/** Celé jméno uživatele z rozšifrovaného pole. */
function userFullName(array $user): string
{
    return trim($user['first_name'] . ' ' . $user['last_name']);
}

/** URL profilové fotky, nebo null (pak se ukáže iniciály). */
function userPhotoUrl(array $user): ?string
{
    return !empty($user['photo']) ? url('uploads/profile_photos/' . $user['photo']) : null;
}

function userInitials(array $user): string
{
    return mb_strtoupper(mb_substr($user['first_name'], 0, 1) . mb_substr($user['last_name'], 0, 1));
}

function loginTaken(string $login, ?int $exceptId = null): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE login = ? AND id <> ?');
    $stmt->execute([$login, $exceptId ?? 0]);
    return (int) $stmt->fetchColumn() > 0;
}

function emailTaken(string $email, ?int $exceptId = null): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email_hash = ? AND id <> ?');
    $stmt->execute([emailHash($email), $exceptId ?? 0]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Vytvoří uživatele. Klíče: login, email, password, first_name, last_name, phone,
 * gender, bio (volitelně), photo (volitelně), role (volitelně, výchozí user).
 * Vrací ID nového uživatele.
 */
function createUser(array $d): int
{
    $stmt = db()->prepare(
        'INSERT INTO users (login, email_hash, password_hash, email, first_name, last_name, phone, gender, bio, photo, role)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $d['login'],
        emailHash($d['email']),
        password_hash($d['password'], PASSWORD_DEFAULT),
        encryptText($d['email']),
        encryptText($d['first_name']),
        encryptText($d['last_name']),
        encryptText($d['phone']),
        encryptText($d['gender']),
        encryptText($d['bio'] ?? null),
        $d['photo'] ?? null,
        $d['role'] ?? 'user',
    ]);

    return (int) db()->lastInsertId();
}

/** Najde uživatele podle přihlašovacího jména NEBO e-mailu (surový řádek vč. hashe hesla). */
function findUserForLogin(string $identifier): ?array
{
    $stmt = db()->prepare(
        'SELECT *, (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked,
                GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), locked_until)) AS lock_seconds
         FROM users WHERE login = ? OR email_hash = ? LIMIT 1'
    );
    $stmt->execute([$identifier, emailHash($identifier)]);
    return $stmt->fetch() ?: null;
}

/** Přihlásí uživatele: nové ID session (proti session fixation) a uložení kontrolních údajů. */
function loginUser(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']       = $userId;
    $_SESSION['last_activity'] = time();
    $_SESSION['created']       = time();
    $_SESSION['fingerprint']   = sessionFingerprint();
}

/** Úplné odhlášení: smaže data session i cookie. */
function destroySession(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Otisk prohlížeče; když se v průběhu session změní, považujeme ji za ukradenou. */
function sessionFingerprint(): string
{
    return hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . encryptionKey());
}
