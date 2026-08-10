<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

// Sayfalama
$perPage  = 12;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;
$catFilter = (int)($_GET['cat'] ?? 0);
$search   = trim($_GET['q'] ?? '');

// Sorgu
$where  = [];
$params = [];

if ($catFilter > 0) {
    $where[]  = 'p.category_id = ?';
    $params[] = $catFilter;
}
if ($search !== '') {
    $where[]  = '(p.title LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL  = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSQL");
$countStmt->execute($params);
$total     = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$params[] = $perPage;
$params[] = $offset;

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $whereSQL
    ORDER BY p.sort_order ASC, p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Kategoriler (filtre için)
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürünler | Fasby Studio Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>

<?php if ($flash): ?>
<span id="flashMsg" style="display:none"><?= e($flash['message']) ?></span>
<span id="flashType" style="display:none"><?= e($flash['type']) ?></span>
<?php endif; ?>

<div class="admin-wrap">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="admin-main">
        <?php include __DIR__ . '/partials/topbar.php'; ?>
        <div class="page-content">

            <div class="breadcrumb">
                <a href="/admin/index.php">Dashboard</a>
                <span class="sep">/</span>
                <span class="current">Ürünler</span>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Ürünler
                        <span class="badge badge-muted" style="margin-left:8px;"><?= $total ?></span>
                    </h3>
                    <div class="toolbar">
                        <!-- Arama -->
                        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;">
                            <div class="search-box">
                                <span class="search-icon">🔍</span>
                                <input type="text" name="q" placeholder="Ürün ara…"
                                       value="<?= e($search) ?>">
                            </div>
                            <!-- Kategori filtresi -->
                            <select name="cat" class="form-admin-control" style="width:auto;padding:9px 34px 9px 12px;" onchange="this.form.submit()">
                                <option value="0">Tüm Kategoriler</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-admin btn-admin-outline">Filtrele</button>
                        </form>
                        <a href="/admin/add-product.php" class="btn-admin btn-admin-accent">
                            + Yeni Ürün
                        </a>
                    </div>
                </div>

                <div class="table-wrap">
                    <?php if (empty($products)): ?>
                        <div class="empty-state">
                            <div class="es-icon">🎨</div>
                            <h4>Ürün bulunamadı</h4>
                            <p>Arama kriterlerinize uygun ürün yok ya da henüz ürün eklenmedi.</p>
                            <a href="/admin/add-product.php" class="btn-admin btn-admin-accent">+ İlk Ürünü Ekle</a>
                        </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width:70px">Görsel</th>
                                <th>Ürün Adı</th>
                                <th>Kategori</th>
                                <th>Fiyat</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th style="width:130px">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr data-search="<?= e(mb_strtolower($p['title'] . ' ' . ($p['category_name'] ?? ''))) ?>">
                                <td>
                                    <?php if ($p['image']): ?>
                                        <img class="table-img" src="<?= e(productImageUrl($p['image'])) ?>" alt="<?= e($p['title']) ?>">
                                    <?php else: ?>
                                        <div class="table-img-placeholder">🖼️</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="font-size:0.875rem;color:var(--primary);"><?= e($p['title']) ?></strong>
                                    <?php if ($p['featured']): ?>
                                        <span class="badge badge-accent" style="margin-left:6px;font-size:0.68rem;">⭐</span>
                                    <?php endif; ?>
                                    <?php if ($p['etsy_link']): ?>
                                        <br><a href="<?= e($p['etsy_link']) ?>" target="_blank" rel="noopener noreferrer"
                                               style="font-size:0.75rem;color:var(--muted);">🔗 Etsy</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['category_name']): ?>
                                        <span class="badge badge-muted"><?= e($p['category_name']) ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--muted);font-size:0.8rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $p['price'] ? e(formatPrice((float)$p['price'], $p['currency'])) : '<span style="color:var(--muted)">—</span>' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $p['active'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $p['active'] ? 'Aktif' : 'Pasif' ?>
                                    </span>
                                </td>
                                <td style="font-size:0.8rem;color:var(--muted);">
                                    <?= formatDateTR($p['created_at']) ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="/admin/edit-product.php?id=<?= $p['id'] ?>"
                                           class="btn-admin btn-admin-outline btn-admin-sm"
                                           title="Düzenle">✏️</a>
                                        <a href="/admin/delete-product.php?id=<?= $p['id'] ?>&csrf=<?= e(csrfToken()) ?>"
                                           class="btn-admin btn-admin-ghost btn-admin-sm"
                                           data-confirm="'<?= e($p['title']) ?>' ürününü silmek istediğinize emin misiniz?"
                                           data-href="/admin/delete-product.php?id=<?= $p['id'] ?>&csrf=<?= e(csrfToken()) ?>"
                                           title="Sil">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <!-- Sayfalama -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>&cat=<?= $catFilter ?>&q=<?= urlencode($search) ?>" class="page-btn">‹</a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                        <a href="?page=<?= $i ?>&cat=<?= $catFilter ?>&q=<?= urlencode($search) ?>"
                           class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page+1 ?>&cat=<?= $catFilter ?>&q=<?= urlencode($search) ?>" class="page-btn">›</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/partials/confirm-modal.php'; ?>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
