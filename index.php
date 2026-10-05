<?php
require_once __DIR__ . '/includes/init.php';

// Rychlý test, že databáze funguje (počet otevřených castingů)
$openCastings = (int) db()->query("SELECT COUNT(*) FROM castings WHERE status = 'open'")->fetchColumn();

$pageTitle = 'Úvod';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <h1>Správa castingů na jednom místě</h1>
    <p>Prohlížej castingy, přihlas se jedním kliknutím a komunikuj přímo s organizátory.</p>
    <p>
        <a class="btn" href="<?= e(url('castings.php')) ?>">Zobrazit castingy (<?= $openCastings ?>)</a>
        <?php if (!isLoggedIn()): ?>
            <a class="btn btn-secondary" href="<?= e(url('register.php')) ?>">Vytvořit účet</a>
        <?php endif; ?>
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
