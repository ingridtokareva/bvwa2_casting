# Casting – informační systém pro správu castingů (BVWA2)

Webová aplikace v PHP + MySQL. Uživatelé se registrují, prohlížejí castingy,
podávají přihlášky a píší si interní zprávy. Administrátor spravuje uživatele a role.

**Tým:** Aidai Donbaeva (uživatelé a autentizace), Ingrid Tokareva (castingy, zprávy, administrace)


## Struktura

```
config/      config.example.php (vzor), config.php (lokální, není v Gitu)
includes/    init.php, db.php, functions.php, header.php, footer.php,
             crypto.php (šifrování), validation.php, photo.php, users.php,
             castings.php (stavy, validace a načtení castingů)
css/         style.css (společný vzhled)
js/          main.js, auth.js (validace formulářů registrace/přihlášení/profilu)
database/    schema.sql, seed.php (admin + ukázkové castingy)
uploads/     profile_photos/ (nahrané fotky, PHP se tu nespouští)
admin/       administrátorské stránky
*.php        veřejné stránky v kořeni (index, login, register, castings, ...)
```

## Instalace (lokálně, XAMPP)

1. Zkopíruj `config/config.example.php` jako `config/config.php`, nastav `base_url` a údaje k DB.
2. Vygeneruj šifrovací klíč a vlož ho do `config.php` jako `encryption_key`:
   `php -r "echo base64_encode(random_bytes(32));"`
3. Importuj `database/schema.sql` (phpMyAdmin nebo `mysql -u root < database/schema.sql`).
4. Spusť `php database/seed.php` – vytvoří admina (`admin` / `Admin123!`) a ukázkové castingy.
5. Pro převod TIFF fotek musí být zapnuté PHP rozšíření **imagick**; bez něj se TIFF odmítne
   s hláškou (JPEG, PNG, GIF, BMP a WebP fungují vždy, stačí GD).

Každý člen týmu má vlastní DB a vlastní klíč. Po změně klíče jsou už uložená data nečitelná,
takže po jeho změně znovu importuj `schema.sql` a spusť `seed.php`.

## Šifrování a bezpečnost (užitečné i pro zprávy)

- Osobní údaje (e-mail, jméno, příjmení, telefon, pohlaví, popis) jsou v DB šifrované
  AES-256-GCM: `encryptText()` / `decryptText()` v `includes/crypto.php`.
  **Zprávy (`subject`, `body`) se ukládají stejně** – zašifruj před INSERT, dešifruj při čtení.
- Uživatele z DB čti přes `getUserById($id)` (vrací už rozšifrovaná data bez hashe hesla),
  jméno pro zobrazení: `userFullName($user)`. Aktuální uživatel: `currentUser()`.
- Unikátnost e-mailu hlídá sloupec `email_hash` (HMAC), proto se v SQL nedá hledat podle e-mailu
  přímo, použij `emailTaken()` / `findUserForLogin()`.
- Session: odhlášení po 20 min nečinnosti, nové ID při přihlášení a každých 5 min,
  odhlášení při změně prohlížeče (User-Agent), cookie `httponly`, `use_strict_mode`.
- Přihlášení: 5 špatných hesel po sobě = účet na 5 minut zablokován; stejná hláška pro
  neexistující účet i špatné heslo.

## Jak psát stránku

```php
<?php
require_once __DIR__ . '/includes/init.php';   // v admin/: '/../includes/init.php'
requireLogin();                                 // jen pro přihlášené (requireAdmin() pro admin)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();                               // každý POST formulář
    // ... zpracování, prepared statements ...
}

$pageTitle = 'Název stránky';
require __DIR__ . '/includes/header.php';
?>
<h1>Obsah</h1>
<form method="post"><?= csrfField() ?> ... </form>
<?php require __DIR__ . '/includes/footer.php'; ?>
```

## Pravidla týmu

- **SQL jen přes prepared statements** (`$pdo->prepare()` + `execute()`), nikdy skládání řetězců.
- **Každý výstup přes `e()`** (ochrana proti XSS).
- **Každý POST formulář** má `csrfField()` a na serveru `verifyCsrf()`.
- Hesla jen `password_hash()` / `password_verify()`.
- Role se kontroluje na serveru (`requireAdmin()`), nejen skrytím tlačítek.
- Názvy souborů a proměnných anglicky, komentáře česky, odsazení 4 mezery.
- Sdílené soubory (`functions.php`, `header.php`, `style.css`, `schema.sql`): změnu předem oznam druhé.

## Rozdělení souborů

| Aidai | Ingrid |
|---|---|
| `register.php`, `login.php`, `logout.php` | `castings.php`, `casting.php` |
| `profile.php`, `edit_profile.php` | `apply.php`, `my_applications.php` |
| zpracování fotek | `messages.php`, `message_send.php` |
| | `casting_edit.php` (nový / úprava castingu, jen admin) |
| | `admin/index.php`, `admin/users.php` |

## Git

- `main` je vždy funkční, nepushujeme do něj rozbitý kód.
- Každá funkce ve vlastní větvi: `feature/auth`, `feature/castings`, `feature/messages`, ...
- Před prací: `git pull`. Commity malé a s popisem (`Add registration form validation`).
- Hotovou větev sloučit přes Pull Request, druhá osoba se podívá.
