<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/castings.php';

// ---------- filtr a stránkování ----------
$filter = $_GET['status'] ?? 'all';
if (!in_array($filter, ['all', 'open', 'closed'], true)) {
    $filter = 'all';
}

$perPage = 9;
$where   = $filter === 'all' ? '' : 'WHERE status = ?';
$params  = $filter === 'all' ? [] : [$filter];

$stmt = db()->prepare("SELECT COUNT(*) FROM castings $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$pages  = max(1, (int) ceil($total / $perPage));
$page   = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
$offset = ($page - 1) * $perPage;

// otevřené nahoře, pak podle data konání (bez data na konec); LIMIT/OFFSET jsou přetypovaná čísla
$stmt = db()->prepare(
    "SELECT id, title, description, location, casting_date, status
     FROM castings $where
     ORDER BY (status = 'open') DESC, casting_date IS NULL, casting_date ASC, id DESC
     LIMIT " . (int) $perPage . ' OFFSET ' . (int) $offset
);
$stmt->execute($params);
$castings = $stmt->fetchAll();

/** Odkaz na stránku seznamu se zachovaným filtrem. */
$listUrl = function (string $status, int $pageNo = 1): string {
    $query = [];
    if ($status !== 'all') {
        $query['status'] = $status;
    }
    if ($pageNo > 1) {
        $query['page'] = $pageNo;
    }
    return url('castings.php' . ($query ? '?' . http_build_query($query) : ''));
};

$pageTitle = 'Castingy';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <h1>Castingy</h1>
    <?php if (isAdmin()): ?>
        <a class="btn" href="<?= e(url('casting_edit.php')) ?>">Nový casting</a>
    <?php endif; ?>
</div>

<nav class="filter-tabs" aria-label="Filtr podle stavu">
    <a href="<?= e($listUrl('all')) ?>"<?= $filter === 'all' ? ' aria-current="page"' : '' ?>>Všechny</a>
    <a href="<?= e($listUrl('open')) ?>"<?= $filter === 'open' ? ' aria-current="page"' : '' ?>>Otevřené</a>
    <a href="<?= e($listUrl('closed')) ?>"<?= $filter === 'closed' ? ' aria-current="page"' : '' ?>>Uzavřené</a>
</nav>

<?php if (!$castings): ?>
    <div class="card empty-state">
        <p>Žádné castingy k zobrazení.</p>
    </div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($castings as $c): ?>
            <a class="card casting-card" href="<?= e(url('casting.php?id=' . (int) $c['id'])) ?>">
                <div class="casting-card-top">
                    <?= castingStatusBadge($c['status']) ?>
                </div>
                <h2><?= e($c['title']) ?></h2>
                <p class="casting-excerpt"><?= e(mb_strimwidth($c['description'], 0, 140, '…', 'UTF-8')) ?></p>
                <ul class="casting-meta">
                    <li><?= e(formatCastingDate($c['casting_date'])) ?></li>
                    <?php if ($c['location'] !== null && $c['location'] !== ''): ?>
                        <li><?= e($c['location']) ?></li>
                    <?php endif; ?>
                </ul>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Stránkování">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="<?= e($listUrl($filter, $i)) ?>"<?= $i === $page ? ' aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
