<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/admin/categories.php');

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$cat = $stmt->fetch();
if (!$cat) redirect('/admin/categories.php');

$errors = [];
$data   = $cat;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Güvenlik doğrulaması başarısız.';
    } else {
        $data = [
            'name'        => trim($_POST['name']        ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order'  => (int)($_POST['sort_order']  ?? 0),
        ];

        if (empty($data['name'])) {
            $errors['name'] = 'Kategori adı zorunludur.';
        }

        if (empty($errors)) {
            $pdo->prepare("
                UPDATE categories SET name=?, description=?, sort_order=? WHERE id=?
            ")->execute([
                sanitize($data['name']),
                sanitize($data['description']),
                $data['sort_order'],
                $id,
            ]);

            setFlash('success', 'Kategori güncellendi.');
            redirect('/admin/categories.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Düzenle | Fasby Studio Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
<div class="admin-wrap">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="admin-main">
        <?php include __DIR__ . '/partials/topbar.php'; ?>
        <div class="page-content">

            <div class="breadcrumb">
                <a href="/admin/index.php">Dashboard</a>
                <span class="sep">/</span>
                <a href="/admin/categories.php">Kategoriler</a>
                <span class="sep">/</span>
                <span class="current">Düzenle</span>
            </div>

            <div class="card" style="max-width:560px;">
                <div class="card-header"><h3 class="card-title">Kategori Düzenle</h3></div>
                <div class="card-body">

                    <?php foreach ($errors as $err): ?>
                        <?php if (is_string($err)): ?>
                            <div class="alert alert-danger"><?= e($err) ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <form method="POST" data-validate>
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                        <div class="form-admin-group">
                            <label for="name">Kategori Adı *</label>
                            <input type="text" id="name" name="name"
                                   class="form-admin-control <?= isset($errors['name']) ? 'error' : '' ?>"
                                   value="<?= e($data['name']) ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <span class="form-error-msg"><?= e($errors['name']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-admin-group">
                            <label for="description">Açıklama</label>
                            <textarea id="description" name="description"
                                      class="form-admin-control" rows="3"><?= e($data['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-admin-group">
                            <label for="sort_order">Sıralama</label>
                            <input type="number" id="sort_order" name="sort_order"
                                   class="form-admin-control" value="<?= (int)$data['sort_order'] ?>" min="0">
                        </div>

                        <div style="display:flex;gap:10px;">
                            <button type="submit" class="btn-admin btn-admin-accent">💾 Kaydet</button>
                            <a href="/admin/categories.php" class="btn-admin btn-admin-outline">İptal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
