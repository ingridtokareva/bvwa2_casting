<?php
require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
    redirect('profile.php');
}

const MAX_FAILED_LOGINS = 5;     // tolik špatných pokusů po sobě...
const LOCK_MINUTES      = 5;     // ...zablokuje účet na tuto dobu

$error = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $login    = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $user     = $login !== '' ? findUserForLogin($login) : null;

    if ($user && $user['is_locked']) {
        $minutes = (int) ceil($user['lock_seconds'] / 60);
        $error = "Příliš mnoho nepovedených pokusů. Účet je dočasně zablokován, zkus to za $minutes min.";
    } elseif ($user && password_verify($password, $user['password_hash'])) {
        // úspěch: vynulovat počítadlo, případně obnovit hash novějším algoritmem
        $upd = db()->prepare('UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?');
        $upd->execute([$user['id']]);

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $re = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $re->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }

        loginUser((int) $user['id']);
        flash('Vítej zpět!', 'success');
        redirect('index.php');
    } else {
        if ($user) {
            $failed = (int) $user['failed_logins'] + 1;
            $lock   = $failed >= MAX_FAILED_LOGINS;
            $upd = db()->prepare(
                'UPDATE users SET failed_logins = ?,
                        locked_until = IF(?, DATE_ADD(NOW(), INTERVAL ' . LOCK_MINUTES . ' MINUTE), locked_until)
                 WHERE id = ?'
            );
            $upd->execute([$lock ? 0 : $failed, (int) $lock, $user['id']]);
        } else {
            // ať odpověď trvá stejně dlouho i pro neexistující účet (nejde zjistit, které loginy existují)
            password_verify($password, '$2y$10$Ko5Sj3Hu5G1LMkCM9FLG8.JappY/pslzQ69by5J1jJdQL7VKo0nyC');
        }
        // stejná hláška pro špatný login i špatné heslo
        $error = 'Nesprávné přihlašovací údaje.';
    }
}

$pageTitle = 'Přihlášení';
require __DIR__ . '/includes/header.php';
?>

<div class="auth" style="max-width:460px">
<div class="auth-head">
    <h1>Přihlášení</h1>
    <p>Vítej zpět, přihlas se ke svému účtu.</p>
</div>

<form class="form card" method="post" data-validate novalidate>
    <?= csrfField() ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="form-group">
        <label for="login">Přihlašovací jméno nebo e-mail</label>
        <input type="text" id="login" name="login" required maxlength="254"
               autocomplete="username" value="<?= e($login) ?>" autofocus>
        <div class="field-error" data-error-for="login" role="alert"></div>
    </div>

    <div class="form-group">
        <label for="password">Heslo</label>
        <div class="password-wrap">
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button type="button" class="password-toggle" data-toggle-password="password">Zobrazit</button>
        </div>
        <div class="field-error" data-error-for="password" role="alert"></div>
    </div>

    <button type="submit" class="btn btn-block">Přihlásit</button>
</form>

<p class="auth-alt">Nemáš účet? <a href="<?= e(url('register.php')) ?>">Zaregistruj se</a></p>
</div>

<script src="<?= e(url('js/auth.js')) ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
