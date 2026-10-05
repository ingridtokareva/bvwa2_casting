# Casting – informační systém pro správu castingů (BVWA2)

Webová aplikace v PHP + MySQL. Uživatelé se registrují, prohlížejí castingy,
podávají přihlášky a píší si interní zprávy. Administrátor spravuje uživatele a role.

**Tým:** Aidai Donbaeva (uživatelé a autentizace), Ingrid Tokareva (castingy, zprávy, administrace)

## Spuštění (XAMPP)

1. Naklonuj repozitář do `htdocs/`:
   `git clone https://github.com/ingridtokareva/bvwa2_casting.git`
2. Spusť Apache a MySQL v XAMPP.
3. V phpMyAdmin importuj `database/schema.sql`, potom `database/seed.sql` (testovací data).
4. Zkopíruj `config/config.example.php` jako `config/config.php` a uprav údaje k DB.
   Pokud složka není `bvwa2_casting`, uprav i `base_url`.
5. Otevři `http://localhost/bvwa2_casting/`.

Testovací admin: `admin@casting.local` / `Admin123!`

## Struktura

```
config/      config.example.php (vzor), config.php (lokální, není v Gitu)
includes/    init.php, db.php, functions.php, header.php, footer.php
css/         style.css (společný vzhled)
js/          main.js (+ skripty pro konkrétní stránky)
database/    schema.sql, seed.sql
uploads/     profile_photos/ (nahrané fotky, PHP se tu nespouští)
admin/       administrátorské stránky
*.php        veřejné stránky v kořeni (index, login, register, castings, ...)
```

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
| | `admin/index.php`, `admin/users.php` |

## Git

- `main` je vždy funkční, nepushujeme do něj rozbitý kód.
- Každá funkce ve vlastní větvi: `feature/auth`, `feature/castings`, `feature/messages`, ...
- Před prací: `git pull`. Commity malé a s popisem (`Add registration form validation`).
- Hotovou větev sloučit přes Pull Request, druhá osoba se podívá.
