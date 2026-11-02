<?php
require_once __DIR__ . '/includes/init.php';
requireLogin();

$user      = currentUser();
$photoUrl  = userPhotoUrl($user);
$genders   = genderOptions();

$pageTitle = 'Můj profil';
require __DIR__ . '/includes/header.php';
?>

<h1>Můj profil</h1>

<section class="card profile-card">
    <div class="profile-photo">
        <?php if ($photoUrl): ?>
            <img src="<?= e($photoUrl) ?>" alt="Profilová fotka uživatele <?= e(userFullName($user)) ?>">
        <?php else: ?>
            <div class="avatar" aria-hidden="true"><?= e(userInitials($user)) ?></div>
        <?php endif; ?>
    </div>

    <dl class="profile-data">
        <dt>Jméno</dt>          <dd><?= e(userFullName($user)) ?></dd>
        <dt>Přihlašovací jméno</dt> <dd><?= e($user['login']) ?></dd>
        <dt>E-mail</dt>         <dd><?= e($user['email']) ?></dd>
        <dt>Telefon</dt>        <dd><?= e($user['phone']) ?></dd>
        <dt>Pohlaví</dt>        <dd><?= e($genders[$user['gender']] ?? '') ?></dd>
        <dt>Role</dt>           <dd><?= $user['role'] === 'admin' ? 'Administrátor' : 'Uživatel' ?></dd>
        <dt>O mně</dt>          <dd><?= $user['bio'] !== '' ? nl2br(e($user['bio'])) : '<span class="hint">nevyplněno</span>' ?></dd>
    </dl>
</section>

<p><a class="btn btn-secondary" href="<?= e(url('edit_profile.php')) ?>">Upravit profil a heslo</a></p>

<?php require __DIR__ . '/includes/footer.php'; ?>
