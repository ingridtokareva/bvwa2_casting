<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/castings.php';

$id      = (int) ($_GET['id'] ?? 0);
$casting = $id > 0 ? getCasting($id) : null;

if ($casting === null) {
    http_response_code(404);
    $pageTitle = 'Casting nenalezen';
    require __DIR__ . '/includes/header.php';
    echo '<h1>Casting nenalezen</h1><p><a href="' . e(url('castings.php')) . '">Zpět na seznam castingů</a></p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ---------- změna stavu open/closed (jen administrátor) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAdmin();
    verifyCsrf();

    if (($_POST['action'] ?? '') === 'toggle_status') {
        $newStatus = $casting['status'] === 'open' ? 'closed' : 'open';
        $stmt = db()->prepare('UPDATE castings SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $casting['id']]);
        flash($newStatus === 'open' ? 'Casting byl znovu otevřen.' : 'Casting byl uzavřen.', 'success');
    }
    redirect('casting.php?id=' . (int) $casting['id']);
}

// ---------- autor castingu a přihláška přihlášeného uživatele ----------
$creator   = getUserById((int) $casting['created_by']);
$myApp     = null;
$appCount  = null;

if (isLoggedIn()) {
    $stmt = db()->prepare('SELECT status FROM applications WHERE user_id = ? AND casting_id = ?');
    $stmt->execute([currentUserId(), $casting['id']]);
    $myApp = $stmt->fetch() ?: null;
}
if (isAdmin()) {
    $stmt = db()->prepare('SELECT COUNT(*) FROM applications WHERE casting_id = ?');
    $stmt->execute([$casting['id']]);
    $appCount = (int) $stmt->fetchColumn();
}

// stránka pro podání přihlášky vznikne až v dalším kroku (apply.php)
$applyReady = is_file(__DIR__ . '/apply.php');
$isOpen     = $casting['status'] === 'open';
$appLabels  = ['pending' => 'Čeká na vyřízení', 'accepted' => 'Přijata', 'rejected' => 'Zamítnuta'];

$pageTitle = $casting['title'];
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= e(url('castings.php')) ?>">&larr; Všechny castingy</a></p>

<article class="card casting-detail">
    <div class="casting-card-top">
        <?= castingStatusBadge($casting['status']) ?>
    </div>
    <h1><?= e($casting['title']) ?></h1>

    <dl class="profile-data casting-facts">
        <dt>Termín</dt>
        <dd><?= e(formatCastingDate($casting['casting_date'])) ?></dd>
        <dt>Místo</dt>
        <dd><?= e($casting['location'] !== null && $casting['location'] !== '' ? $casting['location'] : 'Bude upřesněno') ?></dd>
        <dt>Vytvořil(a)</dt>
        <dd><?= e($creator ? userFullName($creator) : 'Neznámý uživatel') ?></dd>
        <dt>Vytvořeno</dt>
        <dd><?= e(date('j. n. Y', strtotime($casting['created_at']))) ?></dd>
        <?php if ($appCount !== null): ?>
            <dt>Přihlášky</dt>
            <dd><?= $appCount ?></dd>
        <?php endif; ?>
    </dl>

    <h2>Popis</h2>
    <p class="casting-description"><?= nl2br(e($casting['description'])) ?></p>

    <div class="casting-actions">
        <?php if ($myApp): ?>
            <p class="alert alert-info">Už ses přihlásil(a). Stav přihlášky:
                <span class="status status-<?= e($myApp['status']) ?>"><?= e($appLabels[$myApp['status']] ?? $myApp['status']) ?></span>
            </p>
        <?php elseif (!$isOpen): ?>
            <p class="alert alert-info">Tento casting je uzavřený, přihlášky se už nepřijímají.</p>
        <?php elseif (!isLoggedIn()): ?>
            <a class="btn" href="<?= e(url('login.php')) ?>">Přihlas se a přihlas se na casting</a>
        <?php elseif ($applyReady): ?>
            <a class="btn" href="<?= e(url('apply.php?casting_id=' . (int) $casting['id'])) ?>">Přihlásit se na casting</a>
        <?php else: ?>
            <p class="hint">Podávání přihlášek bude brzy k dispozici.</p>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
            <a class="btn btn-secondary" href="<?= e(url('casting_edit.php?id=' . (int) $casting['id'])) ?>">Upravit</a>
            <form class="inline-form" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle_status">
                <button type="submit" class="btn <?= $isOpen ? 'btn-danger' : 'btn-secondary' ?>">
                    <?= $isOpen ? 'Uzavřít casting' : 'Znovu otevřít' ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</article>

<?php require __DIR__ . '/includes/footer.php'; ?>
