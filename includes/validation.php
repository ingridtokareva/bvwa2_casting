<?php
/**
 * Serverová validace formulářů (frontend validace v js/auth.js je jen pro pohodlí,
 * spolehnout se smíme jen na tuto).
 */

/** Hodnoty pro pohlaví (klíč se ukládá do DB, popisek se zobrazuje). */
function genderOptions(): array
{
    return [
        'female'      => 'Žena',
        'male'        => 'Muž',
        'other'       => 'Jiné',
        'undisclosed' => 'Nechci uvádět',
    ];
}

/** HTML pro chybu u pole; JS ho použije pro stejné místo (data-error-for). */
function fieldError(array $errors, string $name): string
{
    return '<div class="field-error" data-error-for="' . e($name) . '" role="alert">'
        . e($errors[$name] ?? '') . '</div>';
}

/** Vrátí text chyby, nebo null, když je heslo v pořádku. */
function passwordError(string $password): ?string
{
    if (mb_strlen($password) < 8) {
        return 'Heslo musí mít alespoň 8 znaků.';
    }
    if (mb_strlen($password) > 72) {
        return 'Heslo může mít nejvýše 72 znaků.';   // omezení bcrypt
    }
    if (!preg_match('/\p{Ll}/u', $password) || !preg_match('/\p{Lu}/u', $password) || !preg_match('/\d/', $password)) {
        return 'Heslo musí obsahovat malé písmeno, velké písmeno a číslici.';
    }
    return null;
}

/**
 * Zvaliduje společná pole profilu (registrace i úprava).
 * Vrací [chyby, očištěná data]. Chyby jsou ve tvaru [pole => text].
 */
function validateProfileFields(array $in): array
{
    $errors = [];
    $clean  = [
        'first_name' => trim((string) ($in['first_name'] ?? '')),
        'last_name'  => trim((string) ($in['last_name'] ?? '')),
        'email'      => trim((string) ($in['email'] ?? '')),
        'phone'      => preg_replace('/[\s-]+/', '', (string) ($in['phone'] ?? '')),
        'gender'     => (string) ($in['gender'] ?? ''),
        'bio'        => trim((string) ($in['bio'] ?? '')),
    ];

    // jméno: písmena (i s diakritikou), mezera, pomlčka, apostrof
    foreach (['first_name' => 'Jméno', 'last_name' => 'Příjmení'] as $field => $label) {
        $v = $clean[$field];
        if ($v === '') {
            $errors[$field] = "$label je povinné.";
        } elseif (mb_strlen($v) > 100) {
            $errors[$field] = "$label může mít nejvýše 100 znaků.";
        } elseif (!preg_match("/^\p{L}[\p{L} '’-]*$/u", $v)) {
            $errors[$field] = "$label může obsahovat jen písmena, mezeru, pomlčku a apostrof.";
        }
    }

    if ($clean['email'] === '') {
        $errors['email'] = 'E-mail je povinný.';
    } elseif (mb_strlen($clean['email']) > 254 || !filter_var($clean['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Zadej platný e-mail.';
    }

    // telefon: volitelné +, pak 9-15 číslic (mezery a pomlčky se předem odstraní)
    if ($clean['phone'] === '') {
        $errors['phone'] = 'Telefon je povinný.';
    } elseif (!preg_match('/^\+?\d{9,15}$/', $clean['phone'])) {
        $errors['phone'] = 'Zadej telefon ve tvaru +420123456789 (9 až 15 číslic).';
    }

    if (!array_key_exists($clean['gender'], genderOptions())) {
        $errors['gender'] = 'Vyber pohlaví ze seznamu.';
    }

    if (mb_strlen($clean['bio']) > 1000) {
        $errors['bio'] = 'Popis může mít nejvýše 1000 znaků.';
    }

    return [$errors, $clean];
}

/** Validace přihlašovacího jména (unikátnost se kontroluje zvlášť v DB). */
function loginError(string $login): ?string
{
    if ($login === '') {
        return 'Přihlašovací jméno je povinné.';
    }
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $login)) {
        return 'Přihlašovací jméno má 3 až 30 znaků: písmena bez diakritiky, číslice, _ . -';
    }
    return null;
}
