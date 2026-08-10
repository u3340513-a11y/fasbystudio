<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
$errors = [];
$data   = [
    'title'       => '',
    'description' => '',
    'price'       => '',
    'currency'    => 'USD',
    'category_id' => '',
    'etsy_link'   => '',
    'tags'        => '',
    'featured'    => 0,
    'active'      => 1,
    'sort_order'  => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Güvenlik doğrulaması başarısız.';
    } else {
        // Veri al
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
        ];

        // Doğrulama
        if (empty($data['title'])) {
            $errors['title'] = 'Ürün adı zorunludur.';
        } elseif (mb_strlen($data['title']) > 255) {
            $errors['title'] = 'Ürün adı en fazla 255 karakter olabilir.';
        }

        if ($data['price'] !== '' && (!is_numeric($data['price']) || (float)$data['price'] < 0)) {
            $errors['price'] = 'Geçerli bir fiyat girin.';
        }

        if (!empty($data['etsy_link']) && !filter_var($data['etsy_link'], FILTER_VALIDATE_URL)) {
            $errors['etsy_link'] = 'Geçerli bir URL girin (https://…)';
        }

        // Görsel yükleme
        $imageFilename = null;
        if (!empty($_FILES['image']['name'])) {
            $imageFilename = uploadImage($_FILES['image'], 'product');
            if ($imageFilename === false) {
                $errors['image'] = 'Görsel yüklenemedi. Geçerli bir resim dosyası seçin (JPG, PNG, WEBP) ve 5MB altı olmasına dikkat edin.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO products
                    (title, description, price, currency, category_id, etsy_link, image, tags, featured, active, sort_order)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                sanitize($data['title']),
                sanitize($data['description']),
                $data['price'] !== '' ? (float)$data['price'] : null,
                $data['currency'],
                $data['category_id'],
                $data['etsy_link'] ?: null,
                $imageFilename,
                sanitize($data['tags']),
                $data['featured'],
                $data['active'],
                $data['sort_order'],
            ]);

            setFlash('success', '"' . sanitize($data['title']) . '" ürünü başarıyla eklendi.');
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
    <title>Ürün Ekle | Fasby Studio Admin</title>
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
                <span class="current">Yeni Ürün</span>
            </div>

            <?php foreach ($errors as $err): ?>
                <?php if (is_string($err)): ?>
                    <div class="alert alert-danger"><?= e($err) ?></div>
                <?php endif; ?>
            <?php endforeach; ?>

            <form method="POST" enctype="multipart/form-data" data-validate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

                    <!-- Sol: Ana bilgiler -->
                    <div style="display:flex;flex-direction:column;gap:24px;">

                        <!-- Temel Bilgiler -->
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Ürün Bilgileri</h3></div>
                            <div class="card-body">

                                <div class="form-admin-group">
                                    <label for="title">Ürün Adı *</label>
                                    <input type="text" id="title" name="title" class="form-admin-control <?= isset($errors['title']) ? 'error' : '' ?>"
                                           value="<?= e($data['title']) ?>" required maxlength="255"
                                           placeholder="Ürün adını girin…">
                                    <?php if (isset($errors['title'])): ?>
                                        <span class="form-error-msg"><?= e($errors['title']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-admin-group">
                                    <label for="description">
                                        Açıklama
                                        <span style="font-weight:400;color:var(--muted);font-size:0.75rem;">(opsiyonel)</span>
                                    </label>
                                    <textarea id="description" name="description"
                                              class="form-admin-control"
                                              data-maxlength="2000"
                                              rows="5"
                                              placeholder="Ürün hakkında kısa bir açıklama…"><?= e($data['description']) ?></textarea>
                                    <div style="display:flex;justify-content:flex-end;">
                                        <small style="color:var(--muted);" data-counter="description">0/2000</small>
                                    </div>
                                </div>

                                <div class="form-row cols-2">
                                    <div class="form-admin-group" style="margin-bottom:0">
                                        <label for="etsy_link">Etsy Ürün Linki</label>
                                        <input type="url" id="etsy_link" name="etsy_link"
                                               class="form-admin-control <?= isset($errors['etsy_link']) ? 'error' : '' ?>"
                                               value="<?= e($data['etsy_link']) ?>"
                                               placeholder="https://www.etsy.com/listing/…">
                                        <?php if (isset($errors['etsy_link'])): ?>
                                            <span class="form-error-msg"><?= e($errors['etsy_link']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-admin-group" style="margin-bottom:0">
                                        <label for="tags">Etiketler</label>
                                        <input type="text" id="tags" name="tags"
                                               class="form-admin-control"
                                               value="<?= e($data['tags']) ?>"
                                               placeholder="grafik, renkli, minimalist (virgülle ayır)">
                                        <p class="hint">Virgülle ayrılmış etiketler</p>
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

                                <!-- Önizleme (gizli başlar) -->
                                <div id="imagePreviewWrap" class="img-preview-wrap" style="display:none;margin-bottom:16px;">
                                    <img id="imagePreview" src="" alt="Önizleme">
                                    <button type="button" id="removeImageBtn" class="img-preview-remove" aria-label="Görseli kaldır">✕</button>
                                </div>

                                <!-- Yükleme alanı -->
                                <label id="imageUploadArea" class="img-upload-area" for="imageInput">
                                    <div class="upload-icon">📷</div>
                                    <p><strong>Tıklayın</strong> veya sürükleyip bırakın</p>
                                    <p class="hint">JPG, PNG, WEBP — Maks. 5MB</p>
                                </label>
                                <input type="file" id="imageInput" name="image" accept="image/*" style="display:none;">
                            </div>
                        </div>

                    </div>

                    <!-- Sağ: Meta bilgiler -->
                    <div style="display:flex;flex-direction:column;gap:24px;">

                        <!-- Fiyat & Kategori -->
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Fiyat & Kategori</h3></div>
                            <div class="card-body">

                                <div class="form-admin-group">
                                    <label for="price">Fiyat (Opsiyonel)</label>
                                    <input type="number" id="price" name="price"
                                           class="form-admin-control <?= isset($errors['price']) ? 'error' : '' ?>"
                                           value="<?= e($data['price']) ?>"
                                           step="0.01" min="0" placeholder="Örn: 29.99">
                                    <?php if (isset($errors['price'])): ?>
                                        <span class="form-error-msg"><?= e($errors['price']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-admin-group">
                                    <label for="currency">Para Birimi</label>
                                    <select id="currency" name="currency" class="form-admin-control">
                                        <option value="USD" <?= $data['currency']==='USD' ? 'selected' : '' ?>>USD ($)</option>
                                        <option value="EUR" <?= $data['currency']==='EUR' ? 'selected' : '' ?>>EUR (€)</option>
                                        <option value="TRY" <?= $data['currency']==='TRY' ? 'selected' : '' ?>>TRY (₺)</option>
                                        <option value="GBP" <?= $data['currency']==='GBP' ? 'selected' : '' ?>>GBP (£)</option>
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
                                           class="form-admin-control" value="<?= (int)$data['sort_order'] ?>"
                                           min="0" placeholder="0">
                                    <p class="hint">Küçük sayı daha önce gösterilir.</p>
                                </div>

                            </div>
                        </div>

                        <!-- Durum -->
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Durum</h3></div>
                            <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">

                                <label class="toggle-wrap">
                                    <input type="checkbox" name="active" class="toggle-input" id="activeToggle"
                                           <?= $data['active'] ? 'checked' : '' ?>>
                                    <span class="toggle-switch"></span>
                                    <span class="toggle-label">Aktif (sitede görünür)</span>
                                </label>

                                <label class="toggle-wrap">
                                    <input type="checkbox" name="featured" class="toggle-input" id="featuredToggle"
                                           <?= $data['featured'] ? 'checked' : '' ?>>
                                    <span class="toggle-switch"></span>
                                    <span class="toggle-label">⭐ Öne Çıkan</span>
                                </label>

                            </div>
                        </div>

                        <!-- Kaydet -->
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <button type="submit" class="btn-admin btn-admin-accent" style="width:100%;justify-content:center;padding:14px;">
                                ✅ Ürünü Kaydet
                            </button>
                            <a href="/admin/products.php" class="btn-admin btn-admin-outline" style="width:100%;justify-content:center;">
                                İptal
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
