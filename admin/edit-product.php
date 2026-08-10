<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/admin/products.php');

// Ürünü getir
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) redirect('/admin/products.php');

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
$errors = [];
$data   = $product; // Başlangıç değerleri mevcut ürün

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Güvenlik doğrulaması başarısız.';
    } else {
        $data = [
            'title'       => trim($_POST['title']       ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price'       => trim($_POST['price']       ?? ''),
            'currency'    => in_array($_POST['currency'] ?? '', ['USD','EUR','TRY','GBP']) ? $_POST['currency'] : 'USD',
            'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
            'etsy_link'   => trim($_POST['etsy_link']   ?? ''),
            'tags'        => trim($_POST['tags']         ?? ''),
            'featured'    => isset($_POST['featured']) ? 1 : 0,
            'active'      => isset($_POST['active'])   ? 1 : 0,
            'sort_order'  => (int)($_POST['sort_order']  ?? 0),
            'image'       => $product['image'], // Var olan görsel
        ];

        // Doğrulama
        if (empty($data['title'])) {
            $errors['title'] = 'Ürün adı zorunludur.';
        }
        if ($data['price'] !== '' && (!is_numeric($data['price']) || (float)$data['price'] < 0)) {
            $errors['price'] = 'Geçerli bir fiyat girin.';
        }
        if (!empty($data['etsy_link']) && !filter_var($data['etsy_link'], FILTER_VALIDATE_URL)) {
            $errors['etsy_link'] = 'Geçerli bir URL girin.';
        }

        // Görsel güncelle
        if (!empty($_FILES['image']['name'])) {
            $newImage = uploadImage($_FILES['image'], 'product');
            if ($newImage === false) {
                $errors['image'] = 'Görsel yüklenemedi.';
            } else {
                // Eski görseli sil
                deleteImage($product['image']);
                $data['image'] = $newImage;
            }
        } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            deleteImage($product['image']);
            $data['image'] = null;
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                UPDATE products
                SET title=?, description=?, price=?, currency=?, category_id=?,
                    etsy_link=?, image=?, tags=?, featured=?, active=?, sort_order=?
                WHERE id=?
            ");
            $stmt->execute([
                sanitize($data['title']),
                sanitize($data['description']),
                $data['price'] !== '' ? (float)$data['price'] : null,
                $data['currency'],
                $data['category_id'],
                $data['etsy_link'] ?: null,
                $data['image'],
                sanitize($data['tags']),
                $data['featured'],
                $data['active'],
                $data['sort_order'],
                $id,
            ]);

            setFlash('success', '"' . sanitize($data['title']) . '" ürünü güncellendi.');
            redirect('/admin/products.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Düzenle | Fasby Studio Admin</title>
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
                <a href="/admin/products.php">Ürünler</a>
                <span class="sep">/</span>
                <span class="current">Düzenle</span>
            </div>

            <?php foreach ($errors as $err): ?>
                <?php if (is_string($err)): ?>
                    <div class="alert alert-danger"><?= e($err) ?></div>
                <?php endif; ?>
            <?php endforeach; ?>

            <form method="POST" enctype="multipart/form-data" data-validate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="remove_image" id="removeImageInput" value="0">

                <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

                    <!-- Sol -->
                    <div style="display:flex;flex-direction:column;gap:24px;">

                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Ürün Bilgileri</h3></div>
                            <div class="card-body">

                                <div class="form-admin-group">
                                    <label for="title">Ürün Adı *</label>
                                    <input type="text" id="title" name="title"
                                           class="form-admin-control <?= isset($errors['title']) ? 'error' : '' ?>"
                                           value="<?= e($data['title']) ?>" required maxlength="255">
                                    <?php if (isset($errors['title'])): ?>
                                        <span class="form-error-msg"><?= e($errors['title']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-admin-group">
                                    <label for="description">Açıklama</label>
                                    <textarea id="description" name="description"
                                              class="form-admin-control"
                                              data-maxlength="2000"
                                              rows="5"><?= e($data['description'] ?? '') ?></textarea>
                                    <div style="display:flex;justify-content:flex-end;">
                                        <small style="color:var(--muted);" data-counter="description"></small>
                                    </div>
                                </div>

                                <div class="form-row cols-2">
                                    <div class="form-admin-group" style="margin-bottom:0">
                                        <label for="etsy_link">Etsy Ürün Linki</label>
                                        <input type="url" id="etsy_link" name="etsy_link"
                                               class="form-admin-control"
                                               value="<?= e($data['etsy_link'] ?? '') ?>"
                                               placeholder="https://www.etsy.com/listing/…">
                                    </div>
                                    <div class="form-admin-group" style="margin-bottom:0">
                                        <label for="tags">Etiketler</label>
                                        <input type="text" id="tags" name="tags"
                                               class="form-admin-control"
                                               value="<?= e($data['tags'] ?? '') ?>"
                                               placeholder="grafik, renkli (virgülle ayır)">
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Görsel -->
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Ürün Görseli</h3></div>
                            <div class="card-body">
                                <?php if (isset($errors['image'])): ?>
                                    <div class="alert alert-danger" style="margin-bottom:16px;"><?= e($errors['image']) ?></div>
                                <?php endif; ?>

                                <?php if ($data['image']): ?>
                                <div id="imagePreviewWrap" class="img-preview-wrap" style="margin-bottom:16px;">
                                    <img id="imagePreview" src="<?= e(productImageUrl($data['image'])) ?>" alt="Ürün görseli">
                                    <button type="button" id="removeImageBtn" class="img-preview-remove"
                                            onclick="document.getElementById('removeImageInput').value='1';this.parentElement.style.display='none';document.getElementById('imageUploadArea').style.display='';"
                                            aria-label="Görseli kaldır">✕</button>
                                </div>
                                <div id="imageUploadArea" class="img-upload-area" style="display:none;">
                                <?php else: ?>
                                <div id="imagePreviewWrap" class="img-preview-wrap" style="display:none;margin-bottom:16px;">
                                    <img id="imagePreview" src="" alt="Önizleme">
                                    <button type="button" id="removeImageBtn" class="img-preview-remove" aria-label="Görseli kaldır">✕</button>
                                </div>
                                <div id="imageUploadArea" class="img-upload-area">
                                <?php endif; ?>
                                    <label for="imageInput" style="cursor:pointer;display:block;">
                                        <div class="upload-icon">📷</div>
                                        <p><strong>Tıklayın</strong> veya sürükleyip bırakın</p>
                                        <p class="hint">JPG, PNG, WEBP — Maks. 5MB</p>
                                    </label>
                                </div>
                                <input type="file" id="imageInput" name="image" accept="image/*" style="display:none;">
                            </div>
                        </div>

                    </div>

                    <!-- Sağ -->
                    <div style="display:flex;flex-direction:column;gap:24px;">

                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Fiyat & Kategori</h3></div>
                            <div class="card-body">

                                <div class="form-admin-group">
                                    <label for="price">Fiyat</label>
                                    <input type="number" id="price" name="price"
                                           class="form-admin-control"
                                           value="<?= e($data['price'] ?? '') ?>"
                                           step="0.01" min="0">
                                </div>

                                <div class="form-admin-group">
                                    <label for="currency">Para Birimi</label>
                                    <select id="currency" name="currency" class="form-admin-control">
                                        <option value="USD" <?= ($data['currency']??'USD')==='USD' ? 'selected' : '' ?>>USD ($)</option>
                                        <option value="EUR" <?= ($data['currency']??'')==='EUR' ? 'selected' : '' ?>>EUR (€)</option>
                                        <option value="TRY" <?= ($data['currency']??'')==='TRY' ? 'selected' : '' ?>>TRY (₺)</option>
                                        <option value="GBP" <?= ($data['currency']??'')==='GBP' ? 'selected' : '' ?>>GBP (£)</option>
                                    </select>
                                </div>

                                <div class="form-admin-group">
                                    <label for="category_id">Kategori</label>
                                    <select id="category_id" name="category_id" class="form-admin-control">
                                        <option value="">— Seçiniz —</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $data['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                            <?= e($cat['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-admin-group" style="margin-bottom:0">
                                    <label for="sort_order">Sıralama</label>
                                    <input type="number" id="sort_order" name="sort_order"
                                           class="form-admin-control" value="<?= (int)($data['sort_order']??0) ?>"
                                           min="0">
                                </div>

                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Durum</h3></div>
                            <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">

                                <label class="toggle-wrap">
                                    <input type="checkbox" name="active" class="toggle-input"
                                           <?= $data['active'] ? 'checked' : '' ?>>
                                    <span class="toggle-switch"></span>
                                    <span class="toggle-label">Aktif</span>
                                </label>

                                <label class="toggle-wrap">
                                    <input type="checkbox" name="featured" class="toggle-input"
                                           <?= $data['featured'] ? 'checked' : '' ?>>
                                    <span class="toggle-switch"></span>
                                    <span class="toggle-label">⭐ Öne Çıkan</span>
                                </label>

                            </div>
                        </div>

                        <!-- Bilgi -->
                        <div class="card" style="border-color:var(--border);">
                            <div class="card-body" style="font-size:0.8rem;color:var(--muted);">
                                <p>🆔 ID: #<?= $id ?></p>
                                <p style="margin-top:6px;">📅 Eklenme: <?= formatDateTR($product['created_at']) ?></p>
                                <p style="margin-top:6px;">🔄 Güncelleme: <?= formatDateTR($product['updated_at']) ?></p>
                            </div>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <button type="submit" class="btn-admin btn-admin-accent" style="width:100%;justify-content:center;padding:14px;">
                                💾 Değişiklikleri Kaydet
                            </button>
                            <a href="/admin/products.php" class="btn-admin btn-admin-outline" style="width:100%;justify-content:center;">
                                İptal
                            </a>
                            <a href="/admin/delete-product.php?id=<?= $id ?>&csrf=<?= e(csrfToken()) ?>"
                               class="btn-admin btn-admin-danger" style="width:100%;justify-content:center;"
                               data-confirm="Bu ürünü silmek istediğinize emin misiniz?"
                               data-href="/admin/delete-product.php?id=<?= $id ?>&csrf=<?= e(csrfToken()) ?>">
                                🗑️ Ürünü Sil
                            </a>
                        </div>

                    </div>
                </div>
            </form>

        </div>
    </main>
</div>

<?php include __DIR__ . '/partials/confirm-modal.php'; ?>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
