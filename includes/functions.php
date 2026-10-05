<?php
/**
 * Společné pomocné funkce. Před úpravou tohoto souboru se domluvte (je sdílený).
 */

// ---------- Výstup a odkazy ----------

/** Escapování výstupu (ochrana proti XSS). Použij u KAŽDÉHO textu z DB / formuláře. */
function e(?string $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/** Vytvoří URL v rámci projektu: url('login.php') */
function url(string $path = ''): string
{
    return rtrim(config('base_url', ''), '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

// ---------- Flash zprávy (jednorázové hlášky po přesměrování) ----------

/** $type: success | error | info */
function flash(string $message, string $type = 'info'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function getFlashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

// ---------- Přihlášení a role ----------

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUserId(): ?int
{
    return isLoggedIn() ? (int) $_SESSION['user_id'] : null;
}

/** Aktuální uživatel z DB (načte se jen jednou za požadavek). */
function currentUser(): ?array
{
    static $user = false;

    if ($user === false) {
        $user = null;
        if (isLoggedIn()) {
            $stmt = db()->prepare(
                'SELECT id, email, first_name, last_name, role, photo FROM users WHERE id = ?'
            );
            $stmt->execute([currentUserId()]);
            $user = $stmt->fetch() ?: null;

            // uživatel mezitím smazán -> odhlásit
            if ($user === null) {
                $_SESSION = [];
            }
        }
    }

    return $user;
}

function isAdmin(): bool
{
    $u = currentUser();
    return $u !== null && $u['role'] === 'admin';
}

/** Na začátek stránek, které vyžadují přihlášení. */
function requireLogin(): void
{
    if (!isLoggedIn() || currentUser() === null) {
        flash('Pro tuto stránku se musíš přihlásit.', 'error');
        redirect('login.php');
    }
}

/** Na začátek admin stránek. Kontroluje roli v DB, ne jen skrytá tlačítka. */
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        die('Přístup odepřen.');
    }
}

// ---------- CSRF ochrana formulářů ----------

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Do každého <form method="post"> vlož: <?= csrfField() ?> */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/** Na začátek zpracování každého POST formuláře. */
function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Neplatný bezpečnostní token. Vrať se zpět a zkus to znovu.');
    }
}

// ---------- Zprávy ----------

/** Počet nepřečtených zpráv přihlášeného uživatele (pro hlavičku). */
function unreadMessagesCount(): int
{
    if (!isLoggedIn()) {
        return 0;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0');
    $stmt->execute([currentUserId()]);
    return (int) $stmt->fetchColumn();
}
