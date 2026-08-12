<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$settings = getAllSettings($pdo);
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Güvenlik doğrulaması başarısız.';

    } elseif ($action === 'general') {
        $fields = [
            'site_name', 'site_description', 'site_keywords',
            'site_author', 'contact_email',
            'etsy_shop_url', 'instagram_url', 'pinterest_url', 'twitter_url',
        ];
        $urlFields   = ['etsy_shop_url', 'instagram_url', 'pinterest_url', 'twitter_url'];
        $toSave      = [];

        foreach ($fields as $key) {
            $val = trim($_POST[$key] ?? '');
            if ($key === 'contact_email' && $val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                $errors[$key] = 'Geçerli bir e-posta adresi girin.';
                continue;
            }
            if (in_array($key, $urlFields, true) && $val !== '' && !filter_var($val, FILTER_VALIDATE_URL)) {
                $errors[$key] = 'Geçerli bir URL girin (https:// ile başlamalı).';
                continue;
            }
            $toSave[$key] = $val;
        }

        if (empty($errors)) {
            foreach ($toSave as $k => $v) {
                saveSetting($pdo, $k, $v);
            }
            setFlash('success', 'Ayarlar başarıyla kaydedildi.');
            redirect('/admin/settings.php');
        }

    } elseif ($action === 'logo') {
        if (!empty($_FILES['logo']['name'])) {
            if (!empty($settings['logo_image'])) {
                deleteImage($settings['logo_image']);
            }
            $filename = uploadImage($_FILES['logo'], 'logo');
            if ($filename === false) {
                $errors['logo'] = 'Logo yüklenemedi. JPG, PNG veya WebP olmalı, max 5MB.';
            } else {
                saveSetting($pdo, 'logo_image', $filename);
                setFlash('success', 'Logo güncellendi.');
                redirect('/admin/settings.php');
            }
        } elseif (isset($_POST['remove_logo'])) {
            if (!empty($settings['logo_image'])) {
                deleteImage($settings['logo_image']);
            }
            saveSetting($pdo, 'logo_image', '');
            setFlash('success', 'Logo kaldırıldı, metin logo kullanılıyor.');
            redirect('/admin/settings.php');
        }

    } elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($current, $admin['password'])) {
            $errors['current_password'] = 'Mevcut şifre yanlış.';
        } elseif (strlen($new) < 8) {
            $errors['new_password'] = 'Yeni şifre en az 8 karakter olmalıdır.';
        } elseif ($new !== $confirm) {
            $errors['confirm_password'] = 'Şifreler eşleşmiyor.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['admin_id']]);
            setFlash('success', 'Şifre başarıyla güncellendi.');
            redirect('/admin/settings.php');
        }
    }
}

$flash    = getFlash();
$settings = getAllSettings($pdo); // Yenile (redirect olmadıysa)
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar | Fasby Studio Admin</title>
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
                <span class="current">⚙️ Ayarlar</span>
            </div>

            <?php foreach ($errors as $key => $err): ?>
                <?php if (is_int($key)): ?>
                    <div class="alert alert-danger"><?= e($err) ?></div>
                <?php endif; ?>
            <?php endforeach; ?>

            <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

                <!-- Sol kolon -->
                <div style="display:flex;flex-direction:column;gap:24px;">

                    <!-- Genel Ayarlar -->
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">🌐 Genel Site Ayarları</h3></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="general">

                                <div class="form-row cols-2">
                                    <div class="form-admin-group">
                                        <label for="site_name">Site Adı</label>
                                        <input type="text" id="site_name" name="site_name"
                                               class="form-admin-control <?= isset($errors['site_name']) ? 'error' : '' ?>"
                                               value="<?= e($settings['site_name'] ?? SITE_NAME) ?>"
                                               maxlength="100">
                                    </div>
                                    <div class="form-admin-group">
                                        <label for="site_author">Yazar / Marka</label>
                                        <input type="text" id="site_author" name="site_author"
                                               class="form-admin-control"
                                               value="<?= e($settings['site_author'] ?? SITE_AUTHOR) ?>"
                                               maxlength="100">
                                    </div>
                                </div>

                                <div class="form-admin-group">
                                    <label for="site_description">Site Açıklaması <small style="color:var(--muted)">(SEO meta description)</small></label>
                                    <textarea id="site_description" name="site_description"
                                              class="form-admin-control" rows="2"
                                              maxlength="300"><?= e($settings['site_description'] ?? SITE_DESCRIPTION) ?></textarea>
                                </div>

                                <div class="form-admin-group">
                                    <label for="site_keywords">Anahtar Kelimeler <small style="color:var(--muted)">(virgülle ayırın)</small></label>
                                    <input type="text" id="site_keywords" name="site_keywords"
                                           class="form-admin-control"
                                           value="<?= e($settings['site_keywords'] ?? SITE_KEYWORDS) ?>"
                                           maxlength="500">
                                </div>

                                <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
                                <p class="card-title" style="font-size:0.85rem;margin-bottom:16px;color:var(--muted)">İletişim & Sosyal Medya</p>

                                <div class="form-row cols-2">
                                    <div class="form-admin-group">
                                        <label for="contact_email">İletişim E-postası</label>
                                        <input type="email" id="contact_email" name="contact_email"
                                               class="form-admin-control <?= isset($errors['contact_email']) ? 'error' : '' ?>"
                                               value="<?= e($settings['contact_email'] ?? CONTACT_EMAIL) ?>">
                                        <?php if (isset($errors['contact_email'])): ?>
                                            <span class="form-error-msg"><?= e($errors['contact_email']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-admin-group">
                                        <label for="etsy_shop_url">Etsy Mağaza URL</label>
                                        <input type="url" id="etsy_shop_url" name="etsy_shop_url"
                                               class="form-admin-control <?= isset($errors['etsy_shop_url']) ? 'error' : '' ?>"
                                               value="<?= e($settings['etsy_shop_url'] ?? ETSY_SHOP_URL) ?>"
                                               placeholder="https://www.etsy.com/shop/…">
                                        <?php if (isset($errors['etsy_shop_url'])): ?>
                                            <span class="form-error-msg"><?= e($errors['etsy_shop_url']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-row cols-2">
                                    <div class="form-admin-group">
                                        <label for="instagram_url">Instagram URL</label>
                                        <input type="url" id="instagram_url" name="instagram_url"
                                               class="form-admin-control <?= isset($errors['instagram_url']) ? 'error' : '' ?>"
                                               value="<?= e($settings['instagram_url'] ?? INSTAGRAM_URL) ?>"
                                               placeholder="https://instagram.com/…">
                                        <?php if (isset($errors['instagram_url'])): ?>
                                            <span class="form-error-msg"><?= e($errors['instagram_url']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-admin-group">
                                        <label for="pinterest_url">Pinterest URL</label>
                                        <input type="url" id="pinterest_url" name="pinterest_url"
                                               class="form-admin-control <?= isset($errors['pinterest_url']) ? 'error' : '' ?>"
                                               value="<?= e($settings['pinterest_url'] ?? PINTEREST_URL) ?>"
                                               placeholder="https://pinterest.com/…">
                                        <?php if (isset($errors['pinterest_url'])): ?>
                                            <span class="form-error-msg"><?= e($errors['pinterest_url']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-admin-group">
                                    <label for="twitter_url">Twitter / X URL</label>
                                    <input type="url" id="twitter_url" name="twitter_url"
                                           class="form-admin-control <?= isset($errors['twitter_url']) ? 'error' : '' ?>"
                                           value="<?= e($settings['twitter_url'] ?? TWITTER_URL) ?>"
                                           placeholder="https://twitter.com/…">
                                    <?php if (isset($errors['twitter_url'])): ?>
                                        <span class="form-error-msg"><?= e($errors['twitter_url']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div style="display:flex;justify-content:flex-end;">
                                    <button type="submit" class="btn-admin btn-admin-primary">💾 Kaydet</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>

                <!-- Sağ kolon -->
                <div style="display:flex;flex-direction:column;gap:24px;">

                    <!-- Logo -->
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">🖼️ Site Logosu</h3></div>
                        <div class="card-body">

                            <?php if (isset($errors['logo'])): ?>
                                <div class="alert alert-danger" style="margin-bottom:16px;"><?= e($errors['logo']) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($settings['logo_image'])): ?>
                                <div style="text-align:center;margin-bottom:16px;padding:16px;background:var(--bg);border-radius:8px;border:1px solid var(--border);">
                                    <img src="/uploads/<?= e($settings['logo_image']) ?>"
                                         alt="Mevcut logo"
                                         style="max-height:80px;max-width:100%;object-fit:contain;">
                                </div>
                                <form method="POST" style="margin-bottom:12px;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="logo">
                                    <input type="hidden" name="remove_logo" value="1">
                                    <button type="submit" class="btn-admin btn-admin-danger" style="width:100%;"
                                            onclick="return confirm('Logo kaldırılsın mı? Metin logo kullanılacak.')">
                                        🗑️ Logoyu Kaldır
                                    </button>
                                </form>
                            <?php else: ?>
                                <div style="text-align:center;padding:24px 16px;background:var(--bg);border-radius:8px;border:1px dashed var(--border);margin-bottom:16px;">
                                    <p style="color:var(--muted);font-size:0.85rem;margin:0;">Mevcut logo yok<br>Metin logo kullanılıyor: <strong>Fasby<em>Studio</em></strong></p>
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="logo">

                                <div class="form-admin-group" style="margin-bottom:12px;">
                                    <label for="logo">
                                        <?= !empty($settings['logo_image']) ? 'Logoyu Değiştir' : 'Logo Yükle' ?>
                                    </label>
                                    <input type="file" id="logo" name="logo"
                                           class="form-admin-control"
                                           accept="image/jpeg,image/png,image/webp,image/gif">
                                    <p class="hint">JPG, PNG, WebP — max 5MB. Şeffaf arka plan için PNG önerilir.</p>
                                </div>

                                <button type="submit" class="btn-admin btn-admin-primary" style="width:100%;">
                                    📤 Logoyu Yükle
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Admin Hesabı -->
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">🔐 Admin Şifresi</h3></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="password">

                                <div class="form-admin-group">
                                    <label for="current_password">Mevcut Şifre</label>
                                    <input type="password" id="current_password" name="current_password"
                                           class="form-admin-control <?= isset($errors['current_password']) ? 'error' : '' ?>"
                                           autocomplete="current-password">
                                    <?php if (isset($errors['current_password'])): ?>
                                        <span class="form-error-msg"><?= e($errors['current_password']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-admin-group">
                                    <label for="new_password">Yeni Şifre</label>
                                    <input type="password" id="new_password" name="new_password"
                                           class="form-admin-control <?= isset($errors['new_password']) ? 'error' : '' ?>"
                                           autocomplete="new-password" minlength="8">
                                    <?php if (isset($errors['new_password'])): ?>
                                        <span class="form-error-msg"><?= e($errors['new_password']) ?></span>
                                    <?php endif; ?>
                                    <p class="hint">En az 8 karakter</p>
                                </div>

                                <div class="form-admin-group">
                                    <label for="confirm_password">Şifre Tekrar</label>
                                    <input type="password" id="confirm_password" name="confirm_password"
                                           class="form-admin-control <?= isset($errors['confirm_password']) ? 'error' : '' ?>"
                                           autocomplete="new-password">
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <span class="form-error-msg"><?= e($errors['confirm_password']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <button type="submit" class="btn-admin btn-admin-primary" style="width:100%;">
                                    🔑 Şifreyi Güncelle
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>
</div>

<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
