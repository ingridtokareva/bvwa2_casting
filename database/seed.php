<?php
/**
 * Testovací data (jen pro vývoj). Spuštění z příkazové řádky, až PO importu schema.sql:
 *
 *     php database/seed.php
 *
 * Vytvoří výchozího administrátora a ukázkové castingy. Admin má šifrované údaje,
 * proto ho nejde vložit čistým SQL (potřebuje klíč z config/config.php).
 * Na ostrém serveru změň heslo admina nebo ho smaž!
 *
 *     login: admin   (nebo e-mail admin@casting.local)
 *     heslo: Admin123!
 */
if (PHP_SAPI !== 'cli') {
    exit('Tento skript se spouští jen z příkazové řádky.');
}

require_once __DIR__ . '/../includes/init.php';

// --- výchozí administrátor ---
if (!loginTaken('admin')) {
    $adminId = createUser([
        'login'      => 'admin',
        'email'      => 'admin@casting.local',
        'password'   => 'Admin123!',
        'first_name' => 'Admin',
        'last_name'  => 'Test',
        'phone'      => '+420000000000',
        'gender'     => 'undisclosed',
        'role'       => 'admin',
    ]);
    echo "Vytvořen admin (id $adminId).\n";
} else {
    $stmt = db()->prepare("SELECT id FROM users WHERE login = 'admin'");
    $stmt->execute();
    $adminId = (int) $stmt->fetchColumn();
    echo "Admin už existuje (id $adminId).\n";
}

// --- ukázkové castingy (jen když tabulka je prázdná) ---
if ((int) db()->query('SELECT COUNT(*) FROM castings')->fetchColumn() === 0) {
    $ins = db()->prepare(
        'INSERT INTO castings (title, description, location, casting_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $rows = [
        ['Reklama na kávu', 'Hledáme tváře do televizní reklamy. Věk 25-40 let.', 'Praha', '2026-11-15 10:00:00', 'open'],
        ['Studentský film "Návrat"', 'Casting do krátkého studentského filmu, hlavní i vedlejší role.', 'Pardubice', '2026-11-22 14:00:00', 'open'],
        ['Divadelní hra - podzim', 'Amatérské divadlo hledá herce do nové inscenace.', 'Brno', '2026-10-30 18:00:00', 'closed'],
    ];
    foreach ($rows as $r) {
        $ins->execute([...$r, $adminId]);
    }
    echo "Vloženy ukázkové castingy.\n";
}
