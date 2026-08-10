<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

// İstatistikler
$stats = [
    'products'      => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE active=1")->fetchColumn(),
    'products_all'  => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'categories'    => (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'messages'      => (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn(),
    'featured'      => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE featured=1 AND active=1")->fetchColumn(),
];

// Son eklenen ürünler
$recentProducts = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 6
")->fetchAll();

// Son mesajlar
$recentMessages = $pdo->query("
    SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5
")->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Fasby Studio Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>

<!-- Flash message (gizli, JS okuyacak) -->
<?php if ($flash): ?>
<span id="flashMsg" style="display:none"><?= e($flash['message']) ?></span>
<span id="flashType" style="display:none"><?= e($flash['type']) ?></span>
<?php endif; ?>

<div class="admin-wrap">

    <!-- Sidebar -->
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <!-- İçerik -->
    <main class="admin-main">

        <!-- Topbar -->
        <?php include __DIR__ . '/partials/topbar.php'; ?>

        <div class="page-content">

            <div class="breadcrumb">
                <span class="current">📊 Dashboard</span>
            </div>

            <!-- Stat kartları -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <p class="stat-label-admin">Aktif Ürünler</p>
                        <p class="stat-value"><?= $stats['products'] ?></p>
                        <p class="stat-sub">Toplam: <?= $stats['products_all'] ?> ürün</p>
                    </div>
                    <div class="stat-icon-box accent">🎨</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <p class="stat-label-admin">Kategoriler</p>
                        <p class="stat-value"><?= $stats['categories'] ?></p>
                        <p class="stat-sub">Aktif kategori</p>
                    </div>
                    <div class="stat-icon-box success">🏷️</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <p class="stat-label-admin">Öne Çıkan</p>
                        <p class="stat-value"><?= $stats['featured'] ?></p>
                        <p class="stat-sub">Vitrin ürün</p>
                    </div>
                    <div class="stat-icon-box info">⭐</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <p class="stat-label-admin">Okunmamış Mesaj</p>
                        <p class="stat-value"><?= $stats['messages'] ?></p>
                        <p class="stat-sub">Yeni mesaj var</p>
                    </div>
                    <div class="stat-icon-box warning">✉️</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:24px;margin-top:0;">

                <!-- Son Ürünler -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Son Eklenen Ürünler</h3>
                        <a href="/admin/products.php" class="btn-admin btn-admin-outline btn-admin-sm">Tümünü Gör</a>
                    </div>
                    <div class="table-wrap">
                        <?php if (empty($recentProducts)): ?>
                            <div class="empty-state">
                                <div class="es-icon">🎨</div>
                                <h4>Henüz ürün yok</h4>
                                <p>İlk ürününüzü ekleyerek başlayın.</p>
                                <a href="/admin/add-product.php" class="btn-admin btn-admin-accent">+ Ürün Ekle</a>
                            </div>
                        <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Görsel</th>
                                    <th>Ürün Adı</th>
                                    <th>Kategori</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentProducts as $p): ?>
                                <tr>
                                    <td>
                                        <?php if ($p['image']): ?>
                                            <img class="table-img" src="<?= e(productImageUrl($p['image'])) ?>" alt="<?= e($p['title']) ?>">
                                        <?php else: ?>
                                            <div class="table-img-placeholder">🖼️</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= e($p['title']) ?></strong>
                                        <?php if ($p['price']): ?>
                                            <br><small style="color:var(--muted)"><?= e(formatPrice((float)$p['price'], $p['currency'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($p['category_name'] ?? '—') ?></td>
                                    <td>
                                        <?php if ($p['active']): ?>
                                            <span class="badge badge-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Pasif</span>
                                        <?php endif; ?>
                                        <?php if ($p['featured']): ?>
                                            <span class="badge badge-accent">⭐ Öne Çıkan</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Son Mesajlar -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Son Mesajlar</h3>
                        <a href="/admin/messages.php" class="btn-admin btn-admin-outline btn-admin-sm">Tümünü Gör</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($recentMessages)): ?>
                            <div class="empty-state">
                                <div class="es-icon">✉️</div>
                                <p>Henüz mesaj yok.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentMessages as $msg): ?>
                            <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;gap:12px;align-items:flex-start;">
                                <div style="width:40px;height:40px;border-radius:50%;background:var(--accent-l);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem;">
                                    <?= strtoupper(mb_substr($msg['name'], 0, 1)) ?>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                                        <strong style="font-size:0.875rem;color:var(--primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            <?= e($msg['name']) ?>
                                        </strong>
                                        <?php if (!$msg['is_read']): ?>
                                            <span class="badge badge-info" style="font-size:0.65rem;flex-shrink:0;">Yeni</span>
                                        <?php endif; ?>
                                    </div>
                                    <p style="font-size:0.8rem;color:var(--muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?= e(mb_substr($msg['message'], 0, 60)) ?>…
                                    </p>
                                    <p style="font-size:0.72rem;color:var(--muted);margin-top:4px;">
                                        <?= formatDateTR($msg['created_at']) ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<!-- Onay modalı -->
<?php include __DIR__ . '/partials/confirm-modal.php'; ?>

<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
