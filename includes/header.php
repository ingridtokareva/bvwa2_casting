<?php
/**
 * Společná hlavička. Na stránce nastav před include:
 *     $pageTitle = 'Castingy';
 *     require __DIR__ . '/includes/header.php';   (v admin/: '/../includes/header.php')
 */
$pageTitle = $pageTitle ?? 'Castingy';
$user      = currentUser();
$unread    = unreadMessagesCount();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Casting</title>
    <link rel="stylesheet" href="<?= e(url('css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="<?= e(url('index.php')) ?>">Casting</a>

        <nav class="main-nav" aria-label="Hlavní navigace">
            <a href="<?= e(url('castings.php')) ?>">Castingy</a>

            <?php if ($user): ?>
                <a href="<?= e(url('my_applications.php')) ?>">Moje přihlášky</a>
                <a href="<?= e(url('messages.php')) ?>">
                    Zprávy
                    <?php if ($unread > 0): ?>
                        <span class="badge"><?= $unread ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= e(url('profile.php')) ?>"><?= e($user['first_name']) ?></a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?= e(url('admin/index.php')) ?>">Administrace</a>
                <?php endif; ?>
                <form class="inline-form" action="<?= e(url('logout.php')) ?>" method="post">
                    <?= csrfField() ?>
                    <button type="submit" class="link-button">Odhlásit</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>">Přihlásit</a>
                <a class="btn btn-small" href="<?= e(url('register.php')) ?>">Registrace</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
    <?php foreach (getFlashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
