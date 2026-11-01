<?php
require_once __DIR__ . '/includes/init.php';
requireLogin();

$me = currentUser();

$errors  = [];      // chyby formuláře s údaji
$pwErrors = [];     // chyby formuláře pro změnu hesla
$old     = $me;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ---------- A) změna osobních údajů a fotky ----------
    if ($action === 'profile') {
        [$errors, $clean] = validateProfileFields($_POST);
        $old = $clean + $me;

        if (!isset($errors['email']) && emailTaken($clean['email'], $me['id'])) {
            $errors['email'] = 'Účet s tímto e-mailem už existuje.';
        }

        // fotka je při úpravě volitelná: když není vybraná, zůstane stará
        $newPhoto = null;
        $hasFile  = ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($hasFile && !$errors) {
            $photo = processProfilePhoto($_FILES['photo']);
            if ($photo['error'] !== null) {
                $errors['photo'] = $photo['error'];
            } else {
                $newPhoto = $photo['filename'];
            }
        }

        if (!$errors) {
            try {
                $stmt = db()->prepare(
                    'UPDATE users SET email_hash = ?, email = ?, first_name = ?, last_name = ?,
                            phone = ?, gender = ?, bio = ?, photo = COALESCE(?, photo)
                     WHERE id = ?'
                );
                $stmt->execute([
                    emailHash($clean['email']), encryptText($clean['email']),
                    encryptText($clean['first_name']), encryptText($clean['last_name']),
                    encryptText($clean['phone']), encryptText($clean['gender']),
                    encryptText($clean['bio']), $newPhoto, $me['id'],
                ]);

                if ($newPhoto !== null) {
                    deleteProfilePhoto($me['photo']);     // stará fotka už není potřeba
                }
                flash('Profil byl uložen.', 'success');
                redirect('profile.php');
            } catch (PDOException $ex) {
                deleteProfilePhoto($newPhoto);
                if ($ex->getCode() === '23000') {
                    $errors['email'] = 'Účet s tímto e-mailem už existuje.';
                } else {
                    throw $ex;
                }
            }
        } elseif ($hasFile && !isset($errors['photo'])) {
            $errors['photo'] = 'Vyber fotku znovu (prohlížeč ji z bezpečnostních důvodů nezachovává).';
        }
    }

    // ---------- B) změna hesla ----------
    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['password'] ?? '');
        $new2    = (string) ($_POST['password2'] ?? '');

        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$me['id']]);
        $hash = (string) $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $pwErrors['current_password'] = 'Současné heslo není správné.';
        }
        if ($err = passwordError($new)) {
            $pwErrors['password'] = $err;
        } elseif ($new !== $new2) {
            $pwErrors['password2'] = 'Hesla se neshodují.';
        }

        if (!$pwErrors) {
            $upd = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            session_regenerate_id(true);     // po změně hesla nové ID session
            flash('Heslo bylo změněno.', 'success');
            redirect('profile.php');
        }
    }
}

$photoUrl = userPhotoUrl($me);

$pageTitle = 'Upravit profil';
require __DIR__ . '/includes/header.php';
?>

<h1>Upravit profil</h1>

<div class="auth" style="margin:0">
<form class="form form-wide card" method="post" enctype="multipart/form-data" data-validate novalidate>
    <?= csrfField() ?>
    <input type="hidden" name="action" value="profile">

    <div class="form-section">Osobní údaje</div>
    <p class="hint">Přihlašovací jméno <strong><?= e($me['login']) ?></strong> se měnit nedá.</p>

    <div class="form-row">
        <div class="form-group">
            <label for="first_name">Jméno</label>
            <input type="text" id="first_name" name="first_name" required maxlength="100"
                   autocomplete="given-name" value="<?= e($old['first_name']) ?>">
            <?= fieldError($errors, 'first_name') ?>
        </div>
        <div class="form-group">
            <label for="last_name">Příjmení</label>
            <input type="text" id="last_name" name="last_name" required maxlength="100"
                   autocomplete="family-name" value="<?= e($old['last_name']) ?>">
            <?= fieldError($errors, 'last_name') ?>
        </div>
    </div>

    <div class="form-group">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required maxlength="254"
               autocomplete="email" value="<?= e($old['email']) ?>">
        <?= fieldError($errors, 'email') ?>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="phone">Telefon</label>
            <input type="tel" id="phone" name="phone" required autocomplete="tel"
                   pattern="\+?[0-9 \-]{9,20}"
                   data-msg="Zadej telefon ve tvaru +420123456789 (9 až 15 číslic)."
                   value="<?= e($old['phone']) ?>">
            <?= fieldError($errors, 'phone') ?>
        </div>
        <div class="form-group">
            <label for="gender">Pohlaví</label>
            <select id="gender" name="gender" required>
                <?php foreach (genderOptions() as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $old['gender'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= fieldError($errors, 'gender') ?>
        </div>
    </div>

    <div class="form-group">
        <label for="bio">O mně</label>
        <textarea id="bio" name="bio" maxlength="1000"><?= e($old['bio']) ?></textarea>
        <?= fieldError($errors, 'bio') ?>
    </div>

    <div class="form-group">
        <label for="photo">Profilová fotka</label>
        <div class="upload-box" data-upload>
            <?php if ($photoUrl): ?>
                <img class="upload-thumb" data-upload-thumb src="<?= e($photoUrl) ?>" alt="Současná profilová fotka">
            <?php else: ?>
                <div class="upload-thumb" data-upload-thumb>foto</div>
            <?php endif; ?>
            <div class="upload-info">
                <div class="upload-name" data-upload-name><?= $photoUrl ? 'Současná fotka (nech beze změny, nebo vyber novou)' : 'Žádný soubor nevybrán' ?></div>
                <div class="hint">JPEG, PNG, GIF, BMP, TIFF nebo WebP, do <?= round(config('upload')['max_size'] / 1048576) ?> MB.
                    Upraví se na šířku 800 px a uloží jako JPEG.</div>
            </div>
            <input type="file" id="photo" name="photo"  data-photo
                   accept="image/jpeg,image/png,image/gif,image/bmp,image/tiff,image/webp"
                   data-max-size="<?= (int) config('upload')['max_size'] ?>">
            <label for="photo" class="btn btn-secondary" style="margin:0">Vybrat soubor</label>
        </div>
        <?= fieldError($errors, 'photo') ?>
    </div>

    <button type="submit" class="btn">Uložit změny</button>
    <a class="btn btn-secondary" href="<?= e(url('profile.php')) ?>">Zrušit</a>
</form>

<h2 style="margin-top:2rem">Změna hesla</h2>

<form class="form card" method="post" data-validate novalidate>
    <?= csrfField() ?>
    <input type="hidden" name="action" value="password">

    <div class="form-group">
        <label for="current_password">Současné heslo</label>
        <div class="password-wrap">
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            <button type="button" class="password-toggle" data-toggle-password="current_password">Zobrazit</button>
        </div>
        <?= fieldError($pwErrors, 'current_password') ?>
    </div>

    <div class="form-group">
        <label for="new_password">Nové heslo <span class="strength-label" data-strength-label></span></label>
        <div class="password-wrap">
            <input type="password" id="new_password" name="password" required minlength="8" maxlength="72"
                   autocomplete="new-password" data-password>
            <button type="button" class="password-toggle" data-toggle-password="new_password">Zobrazit</button>
        </div>
        <div class="strength" data-strength-for="new_password" data-level="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        <div class="hint">Alespoň 8 znaků, malé a velké písmeno a číslice.</div>
        <?= fieldError($pwErrors, 'password') ?>
    </div>

    <div class="form-group">
        <label for="new_password2">Nové heslo znovu</label>
        <div class="password-wrap">
            <input type="password" id="new_password2" name="password2" required
                   autocomplete="new-password" data-match="new_password">
            <button type="button" class="password-toggle" data-toggle-password="new_password2">Zobrazit</button>
        </div>
        <?= fieldError($pwErrors, 'password2') ?>
    </div>

    <button type="submit" class="btn">Změnit heslo</button>
</form>
</div>

<script src="<?= e(url('js/auth.js')) ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
