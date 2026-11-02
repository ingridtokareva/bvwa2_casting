<?php
/**
 * Pomocné funkce pro castingy (Ingrid). Načti na stránce za init.php:
 *     require_once __DIR__ . '/includes/castings.php';
 */

/** Stavy castingu: klíč v DB => popisek. */
function castingStatuses(): array
{
    return ['open' => 'Otevřený', 'closed' => 'Uzavřený'];
}

/** HTML štítek stavu (barvy .status-open / .status-closed ve style.css). */
function castingStatusBadge(string $status): string
{
    $labels = castingStatuses();
    return '<span class="status status-' . e($status) . '">' . e($labels[$status] ?? $status) . '</span>';
}

/** Datum castingu pro zobrazení, např. "15. 11. 2026, 10:00". */
function formatCastingDate(?string $dateTime): string
{
    if ($dateTime === null || $dateTime === '') {
        return 'Termín bude upřesněn';
    }
    $ts = strtotime($dateTime);
    return $ts === false ? 'Termín bude upřesněn' : date('j. n. Y, H:i', $ts);
}

/** Jeden casting podle ID, nebo null. */
function getCasting(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM castings WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Validace formuláře castingu.
 * Vrací [chyby, očištěná data]; data jsou použitelná i pro znovu vyplnění formuláře.
 */
function validateCasting(array $in): array
{
    $errors = [];
    $clean = [
        'title'        => trim((string) ($in['title'] ?? '')),
        'description'  => trim((string) ($in['description'] ?? '')),
        'location'     => trim((string) ($in['location'] ?? '')),
        'casting_date' => trim((string) ($in['casting_date'] ?? '')),
        'status'       => (string) ($in['status'] ?? 'open'),
    ];

    $len = mb_strlen($clean['title']);
    if ($len < 3 || $len > 200) {
        $errors['title'] = 'Název musí mít 3 až 200 znaků.';
    }

    $len = mb_strlen($clean['description']);
    if ($len < 10 || $len > 5000) {
        $errors['description'] = 'Popis musí mít 10 až 5000 znaků.';
    }

    if (mb_strlen($clean['location']) > 200) {
        $errors['location'] = 'Místo může mít nejvýše 200 znaků.';
    }

    // datum je nepovinné; formát z <input type="datetime-local">: 2026-11-15T10:00
    if ($clean['casting_date'] !== '') {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $clean['casting_date']);
        $dtErrors = DateTime::getLastErrors();
        $invalid = $dt === false || ($dtErrors && ($dtErrors['warning_count'] > 0 || $dtErrors['error_count'] > 0));
        if ($invalid) {
            $errors['casting_date'] = 'Zadej platné datum a čas.';
        }
    }

    if (!array_key_exists($clean['status'], castingStatuses())) {
        $errors['status'] = 'Neplatný stav castingu.';
    }

    return [$errors, $clean];
}
