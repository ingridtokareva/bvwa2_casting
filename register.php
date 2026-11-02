<?php
require_once __DIR__ . '/includes/init.php';

// přihlášený uživatel nemá důvod se znovu registrovat
if (isLoggedIn()) {
    redirect('profile.php');
}

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // 1) validace textových polí
    [$errors, $old] = validateProfileFields($_POST);
    $old['login'] = trim((string) ($_POST['login'] ?? ''));
    $password     = (string) ($_POST['password'] ?? '');
    $password2    = (string) ($_POST['password2'] ?? '');

    if ($err = loginError($old['login'])) {
        $errors['login'] = $err;
    } elseif (loginTaken($old['login'])) {
        $errors['login'] = 'Toto přihlašovací jméno už někdo používá.';
    }

    if (!isset($errors['email']) && emailTaken($old['email'])) {
        $errors['email'] = 'Účet s tímto e-mailem už existuje.';
    }

    if ($err = passwordError($password)) {
        $errors['password'] = $err;
    } elseif ($password !== $password2) {
        $errors['password2'] = 'Hesla se neshodují.';
    }

    // 2) fotka (zpracujeme až když je zbytek v pořádku, ať zbytečně neukládáme soubory)
    $photoName = null;
    if (!$errors) {
        $photo = processProfilePhoto($_FILES['photo'] ?? []);
        if ($photo['error'] !== null) {
            $errors['photo'] = $photo['error'];
        } else {
            $photoName = $photo['filename'];
        }
    } elseif (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $errors['photo'] = 'Vyber profilovou fotku.';
    } else {
        // fotku nelze po opětovném zobrazení formuláře předvyplnit, upozorníme uživatele
        $errors['photo'] = 'Vyber fotku znovu (prohlížeč ji z bezpečnostních důvodů nezachovává).';
    }

    // 3) uložení
    if (!$errors) {
        try {
            createUser($old + ['password' => $password, 'photo' => $photoName]);
            flash('Registrace proběhla úspěšně, můžeš se přihlásit.', 'success');
            redirect('login.php');
        } catch (PDOException $ex) {
            deleteProfilePhoto($photoName);
            if ($ex->getCode() === '23000') {      // souběh: někdo zabral login/e-mail těsně před námi
                $errors['login'] = 'Přihlašovací jméno nebo e-mail už někdo používá.';
            } else {
                throw $ex;
            }
        }
    }
}

$pageTitle = 'Registrace';
require __DIR__ . '/includes/header.php';
?>

<div class="auth">
<div class="auth-head">
    <h1>Vytvoř si účet</h1>
    <p>Registruj se, prohlížej castingy a přihlašuj se na ně.</p>
</div>

<form class="form card" method="post" enctype="multipart/form-data" data-validate novalidate>
    <?= csrfField() ?>

    <div class="form-section">Osobní údaje</div>

    <div class="form-row">
        <div class="form-group">
            <label for="first_name">Jméno</label>
            <input type="text" id="first_name" name="first_name" required maxlength="100"
                   autocomplete="given-name" value="<?= e($old['first_name'] ?? '') ?>">
            <?= fieldError($errors, 'first_name') ?>
        </div>
        <div class="form-group">
            <label for="last_name">Příjmení</label>
            <input type="text" id="last_name" name="last_name" required maxlength="100"
                   autocomplete="family-name" value="<?= e($old['last_name'] ?? '') ?>">
            <?= fieldError($errors, 'last_name') ?>
        </div>
    </div>

    <div class="form-group">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required maxlength="254"
               autocomplete="email" placeholder="jmeno@domena.cz" value="<?= e($old['email'] ?? '') ?>">
        <?= fieldError($errors, 'email') ?>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="phone">Telefon</label>
            <input type="tel" id="phone" name="phone" required autocomplete="tel"
                   pattern="\+?[0-9 \-]{9,20}" placeholder="+420 777 123 456"
                   data-msg="Zadej telefon ve tvaru +420123456789 (9 až 15 číslic)."
                   value="<?= e($old['phone'] ?? '') ?>">
            <div class="hint">Včetně předvolby.</div>
            <?= fieldError($errors, 'phone') ?>
        </div>
        <div class="form-group">
            <label for="gender">Pohlaví</label>
            <select id="gender" name="gender" required>
                <option value="">– vyber –</option>
                <?php foreach (genderOptions() as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= ($old['gender'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= fieldError($errors, 'gender') ?>
        </div>
    </div>

    <div class="form-group">
        <label for="photo">Profilová fotka</label>
        <div class="upload-box" data-upload>
            <div class="upload-thumb" data-upload-thumb>foto</div>
            <div class="upload-info">
                <div class="upload-name" data-upload-name>Žádný soubor nevybrán</div>
                <div class="hint">JPEG, PNG, GIF, BMP, TIFF nebo WebP, do <?= round(config('upload')['max_size'] / 1048576) ?> MB.
                    Upraví se na šířku 800 px a uloží jako JPEG.</div>
            </div>
            <input type="file" id="photo" name="photo" required data-photo
                   accept="image/jpeg,image/png,image/gif,image/bmp,image/tiff,image/webp"
                   data-max-size="<?= (int) config('upload')['max_size'] ?>">
            <label for="photo" class="btn btn-secondary" style="margin:0">Vybrat soubor</label>
        </div>
        <?= fieldError($errors, 'photo') ?>
    </div>

    <div class="form-section">Přihlašovací údaje</div>

    <div class="form-group">
        <label for="login">Přihlašovací jméno</label>
        <input type="text" id="login" name="login" required minlength="3" maxlength="30"
               pattern="[A-Za-z0-9_.\-]{3,30}" autocomplete="username"
               data-msg="3 až 30 znaků: písmena bez diakritiky, číslice, _ . -"
               value="<?= e($old['login'] ?? '') ?>">
        <?= fieldError($errors, 'login') ?>
    </div>

    <div class="form-group">
        <label for="password">Heslo <span class="strength-label" data-strength-label></span></label>
        <div class="password-wrap">
            <input type="password" id="password" name="password" required minlength="8" maxlength="72"
                   autocomplete="new-password" data-password>
            <button type="button" class="password-toggle" data-toggle-password="password">Zobrazit</button>
        </div>
        <div class="strength" data-strength-for="password" data-level="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        <div class="hint">Alespoň 8 znaků, malé a velké písmeno a číslice.</div>
        <?= fieldError($errors, 'password') ?>
    </div>

    <div class="form-group">
        <label for="password2">Heslo znovu</label>
        <div class="password-wrap">
            <input type="password" id="password2" name="password2" required
                   autocomplete="new-password" data-match="password">
            <button type="button" class="password-toggle" data-toggle-password="password2">Zobrazit</button>
        </div>
        <?= fieldError($errors, 'password2') ?>
    </div>

    <button type="submit" class="btn btn-block">Vytvořit účet</button>
</form>

<p class="auth-alt">Už máš účet? <a href="<?= e(url('login.php')) ?>">Přihlas se</a></p>
</div>

<script src="<?= e(url('js/auth.js')) ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
