<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/castings.php';
requireAdmin();     // vytvářet a upravovat castingy smí jen administrátor

// bez ?id= = nový casting, s ?id= = úprava existujícího
$id      = (int) ($_GET['id'] ?? 0);
$casting = null;
if ($id > 0) {
    $casting = getCasting($id);
    if ($casting === null) {
        http_response_code(404);
        die('Casting nenalezen.');
    }
}
$isEdit = $casting !== null;

$errors = [];
$old = $casting ?? ['title' => '', 'description' => '', 'location' => '', 'casting_date' => '', 'status' => 'open'];
// z DB formátu "2026-11-15 10:00:00" na formát pole datetime-local "2026-11-15T10:00"
if (!empty($old['casting_date'])) {
    $old['casting_date'] = str_replace(' ', 'T', substr($old['casting_date'], 0, 16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    [$errors, $clean] = validateCasting($_POST);
    $old = $clean;

    if (!$errors) {
        $date     = $clean['casting_date'] !== '' ? str_replace('T', ' ', $clean['casting_date']) . ':00' : null;
        $location = $clean['location'] !== '' ? $clean['location'] : null;

        if ($isEdit) {
            $stmt = db()->prepare(
                'UPDATE castings SET title = ?, description = ?, location = ?, casting_date = ?, status = ? WHERE id = ?'
            );
            $stmt->execute([$clean['title'], $clean['description'], $location, $date, $clean['status'], $casting['id']]);
            $savedId = (int) $casting['id'];
            flash('Casting byl uložen.', 'success');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO castings (title, description, location, casting_date, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$clean['title'], $clean['description'], $location, $date, $clean['status'], currentUserId()]);
            $savedId = (int) db()->lastInsertId();
            flash('Casting byl vytvořen.', 'success');
        }
        redirect('casting.php?id=' . $savedId);
    }
}

$pageTitle = $isEdit ? 'Upravit casting' : 'Nový casting';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= e(url($isEdit ? 'casting.php?id=' . (int) $casting['id'] : 'castings.php')) ?>">&larr; Zpět</a></p>
<h1><?= e($pageTitle) ?></h1>

<form class="form form-wide card" method="post">
    <?= csrfField() ?>

    <div class="form-group">
        <label for="title">Název</label>
        <input type="text" id="title" name="title" required minlength="3" maxlength="200"
               class="<?= isset($errors['title']) ? 'is-invalid' : '' ?>" value="<?= e($old['title']) ?>">
        <?= fieldError($errors, 'title') ?>
    </div>

    <div class="form-group">
        <label for="description">Popis</label>
        <textarea id="description" name="description" required minlength="10" maxlength="5000"
                  class="<?= isset($errors['description']) ? 'is-invalid' : '' ?>"><?= e($old['description']) ?></textarea>
        <?= fieldError($errors, 'description') ?>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="location">Místo</label>
            <input type="text" id="location" name="location" maxlength="200"
                   class="<?= isset($errors['location']) ? 'is-invalid' : '' ?>" value="<?= e($old['location']) ?>">
            <?= fieldError($errors, 'location') ?>
        </div>
        <div class="form-group">
            <label for="casting_date">Datum a čas</label>
            <input type="datetime-local" id="casting_date" name="casting_date"
                   class="<?= isset($errors['casting_date']) ? 'is-invalid' : '' ?>" value="<?= e($old['casting_date']) ?>">
            <?= fieldError($errors, 'casting_date') ?>
        </div>
    </div>

    <div class="form-group">
        <label for="status">Stav</label>
        <select id="status" name="status">
            <?php foreach (castingStatuses() as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $old['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="hint">U uzavřeného castingu se už nedají podávat přihlášky.</div>
        <?= fieldError($errors, 'status') ?>
    </div>

    <button type="submit" class="btn"><?= $isEdit ? 'Uložit změny' : 'Vytvořit casting' ?></button>
    <a class="btn btn-secondary" href="<?= e(url($isEdit ? 'casting.php?id=' . (int) $casting['id'] : 'castings.php')) ?>">Zrušit</a>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
