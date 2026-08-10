<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.sort_order, c.name
")->fetchAll();

// Yeni kategori ekleme
$errors = [];
$newCat = ['name' => '', 'description' => '', 'sort_order' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Güvenlik doğrulaması başarısız.';
    } else {
        $newCat['name']        = trim($_POST['name']        ?? '');
        $newCat['description'] = trim($_POST['description'] ?? '');
        $newCat['sort_order']  = (int)($_POST['sort_order'] ?? 0);

        if (empty($newCat['name'])) {
            $errors['name'] = 'Kategori adı zorunludur.';
        }

        if (empty($errors)) {
            $slug = slugify($newCat['name']);
            // Slug çakışması kontrolü
            $exists = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $exists->execute([$slug]);
            if ($exists->fetch()) {
                $slug .= '-' . time();
            }

            $pdo->prepare("
                INSERT INTO categories (name, slug, description, sort_order)
                VALUES (?, ?, ?, ?)
            ")->execute([
                sanitize($newCat['name']),
                $slug,
                sanitize($newCat['description']),
                $newCat['sort_order'],
            ]);

            setFlash('success', '"' . sanitize($newCat['name']) . '" kategorisi eklendi.');
            redirect('/admin/categories.php');
        }
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategoriler | Fasby Studio Admin</title>
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
                <span class="current">Kategoriler</span>
            </div>

            <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

                <!-- Kategori listesi -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Kategoriler
                            <span class="badge badge-muted" style="margin-left:8px;"><?= count($categories) ?></span>
                        </h3>
                    </div>
                    <div class="table-wrap">
                        <?php if (empty($categories)): ?>
                            <div class="empty-state">
                                <div class="es-icon">🏷️</div>
                                <h4>Henüz kategori yok</h4>
                                <p>Sağdaki formdan ilk kategorinizi ekleyin.</p>
                            </div>
                        <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Kategori Adı</th>
                                    <th>Slug</th>
                                    <th>Ürün Sayısı</th>
                                    <th>Sıra</th>
                                    <th style="width:100px">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($cat['name']) ?></strong>
                                        <?php if ($cat['description']): ?>
                                            <br><small style="color:var(--muted)"><?= e(mb_substr($cat['description'], 0, 50)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><code style="font-size:0.78rem;background:#F3F4F6;padding:3px 8px;border-radius:4px;"><?= e($cat['slug']) ?></code></td>
                                    <td>
                                        <span class="badge badge-muted"><?= $cat['product_count'] ?> ürün</span>
                                    </td>
                                    <td><?= (int)$cat['sort_order'] ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="/admin/edit-category.php?id=<?= $cat['id'] ?>"
                                               class="btn-admin btn-admin-outline btn-admin-sm">✏️</a>
                                            <?php if ($cat['product_count'] == 0): ?>
                                            <a href="/admin/delete-category.php?id=<?= $cat['id'] ?>&csrf=<?= e(csrfToken()) ?>"
                                               class="btn-admin btn-admin-ghost btn-admin-sm"
                                               data-confirm="'<?= e($cat['name']) ?>' kategorisini silmek istediğinize emin misiniz?"
                                               data-href="/admin/delete-category.php?id=<?= $cat['id'] ?>&csrf=<?= e(csrfToken()) ?>">🗑️</a>
                                            <?php else: ?>
                                            <button class="btn-admin btn-admin-ghost btn-admin-sm"
                                                    title="İçinde ürün olan kategori silinemez" disabled style="opacity:0.3;">🗑️</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Yeni Kategori Formu -->
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Yeni Kategori Ekle</h3></div>
                    <div class="card-body">

                        <?php if (!empty($errors) && is_string(reset($errors))): ?>
                            <div class="alert alert-danger"><?= e(reset($errors)) ?></div>
                        <?php endif; ?>

                        <form method="POST" data-validate>
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="add">

                            <div class="form-admin-group">
                                <label for="cat-name">Kategori Adı *</label>
                                <input type="text" id="cat-name" name="name"
                                       class="form-admin-control <?= isset($errors['name']) ? 'error' : '' ?>"
                                       value="<?= e($newCat['name']) ?>"
                                       required placeholder="Örn: Grafik Sanat">
                                <?php if (isset($errors['name'])): ?>
                                    <span class="form-error-msg"><?= e($errors['name']) ?></span>
                                <?php endif; ?>
                                <p class="hint">Slug otomatik oluşturulur.</p>
                            </div>

                            <div class="form-admin-group">
                                <label for="cat-desc">Açıklama</label>
                                <textarea id="cat-desc" name="description"
                                          class="form-admin-control" rows="3"
                                          placeholder="Kısa bir açıklama…"><?= e($newCat['description']) ?></textarea>
                            </div>

                            <div class="form-admin-group">
                                <label for="cat-sort">Sıralama</label>
                                <input type="number" id="cat-sort" name="sort_order"
                                       class="form-admin-control"
                                       value="<?= (int)$newCat['sort_order'] ?>" min="0">
                            </div>

                            <button type="submit" class="btn-admin btn-admin-accent" style="width:100%;justify-content:center;">
                                + Kategori Ekle
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/partials/confirm-modal.php'; ?>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
