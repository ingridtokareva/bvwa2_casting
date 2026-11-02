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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= e(url('css/style.css')) ?>">
</head>
<body>
<?php
// zvýraznění aktivní položky menu podle adresy stránky
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$navCurrent = fn(string $file): string => str_ends_with($script, '/' . $file) ? ' aria-current="page"' : '';
// položky menu se zobrazí, až stránka existuje (do té doby by vedly na 404)
$navHas = fn(string $file): bool => is_file(dirname(__DIR__) . '/' . $file);
?>
<header class="site-header">
    <div class="container header-inner">
        <div class="header-left">
            <a class="logo" href="<?= e(url('index.php')) ?>">Casting<span class="logo-dot" aria-hidden="true"></span></a>

            <nav class="main-nav" aria-label="Hlavní navigace">
                <a href="<?= e(url('castings.php')) ?>"<?= $navCurrent('castings.php') ?>>Castingy</a>

                <?php if ($user): ?>
                    <?php if ($navHas('my_applications.php')): ?>
                        <a href="<?= e(url('my_applications.php')) ?>"<?= $navCurrent('my_applications.php') ?>>Moje přihlášky</a>
                    <?php endif; ?>
                    <?php if ($navHas('messages.php')): ?>
                        <a href="<?= e(url('messages.php')) ?>"<?= $navCurrent('messages.php') ?>>
                            Zprávy
                            <?php if ($unread > 0): ?>
                                <span class="badge"><?= $unread ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'admin' && $navHas('admin/index.php')): ?>
                        <a href="<?= e(url('admin/index.php')) ?>"<?= $navCurrent('admin/index.php') ?>>Administrace</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
        </div>

        <?php if ($user): ?>
            <div class="user-box">
                <a class="user-chip" href="<?= e(url('profile.php')) ?>" title="Můj profil">
                    <?php if ($photo = userPhotoUrl($user)): ?>
                        <img class="user-avatar" src="<?= e($photo) ?>" alt="">
                    <?php else: ?>
                        <span class="user-avatar" aria-hidden="true"><?= e(userInitials($user)) ?></span>
                    <?php endif; ?>
                    <span class="user-chip-name">
                        <?= e($user['first_name']) ?>
                        <small><?= $user['role'] === 'admin' ? 'Administrátor' : 'Uživatel' ?></small>
                    </span>
                </a>
                <form class="inline-form" action="<?= e(url('logout.php')) ?>" method="post">
                    <?= csrfField() ?>
                    <button type="submit" class="link-button">Odhlásit</button>
                </form>
            </div>
        <?php else: ?>
            <nav class="main-nav" aria-label="Účet">
                <a href="<?= e(url('login.php')) ?>"<?= $navCurrent('login.php') ?>>Přihlásit</a>
                <a class="btn btn-small" href="<?= e(url('register.php')) ?>">Registrace</a>
            </nav>
        <?php endif; ?>
    </div>
</header>

<main class="container">
    <?php foreach (getFlashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
