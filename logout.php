<?php
require_once __DIR__ . '/includes/init.php';

// odhlášení jen přes POST s CSRF tokenem (odkaz <img src="logout.php"> na cizí stránce nikoho neodhlásí)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
verifyCsrf();

destroySession();

// po zničení session potřebujeme novou, aby šla zobrazit hláška
session_start();
session_regenerate_id(true);
flash('Byl jsi odhlášen.', 'info');
redirect('login.php');
