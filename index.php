<?php
require_once __DIR__ . '/includes/init.php';

// čísla na úvodní stránce (skutečná data z DB)
$openCastings = (int) db()->query("SELECT COUNT(*) FROM castings WHERE status = 'open'")->fetchColumn();
$totalUsers   = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalApps    = (int) db()->query('SELECT COUNT(*) FROM applications')->fetchColumn();

$pageTitle = 'Úvod';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <span class="eyebrow">Správa castingů</span>
    <h1>Správa castingů <em>na jednom místě</em></h1>
    <p>Prohlížej castingy, přihlas se jedním kliknutím a komunikuj přímo s organizátory.</p>

    <div class="hero-actions">
        <a class="btn" href="<?= e(url('castings.php')) ?>">Zobrazit castingy</a>
        <?php if (!isLoggedIn()): ?>
            <a class="btn btn-secondary" href="<?= e(url('register.php')) ?>">Vytvořit účet</a>
        <?php endif; ?>
    </div>

    <div class="stats">
        <div class="stat"><strong><?= $openCastings ?></strong><span>Otevřené castingy</span></div>
        <div class="stat"><strong><?= $totalApps ?></strong><span>Podané přihlášky</span></div>
        <div class="stat"><strong><?= $totalUsers ?></strong><span>Registrovaní uživatelé</span></div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
