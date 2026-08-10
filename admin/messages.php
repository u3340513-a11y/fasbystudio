<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$perPage  = 15;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;
$filter   = $_GET['filter'] ?? 'all'; // all | unread | read

$where  = $filter === 'unread' ? 'WHERE is_read = 0' : ($filter === 'read' ? 'WHERE is_read = 1' : '');
$total  = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages $where")->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$messages = $pdo->query("
    SELECT * FROM contact_messages $where
    ORDER BY created_at DESC
    LIMIT $perPage OFFSET $offset
")->fetchAll();

// Seçili mesaj
$viewId  = (int)($_GET['view'] ?? 0);
$viewMsg = null;
if ($viewId) {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$viewId]);
    $viewMsg = $stmt->fetch();
    if ($viewMsg && !$viewMsg['is_read']) {
        $pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([$viewId]);
        $viewMsg['is_read'] = 1;
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesajlar | Fasby Studio Admin</title>
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
                <span class="current">Mesajlar</span>
            </div>

            <?php if ($viewMsg): ?>
            <!-- Mesaj Detayı -->
            <div class="card" style="margin-bottom:24px;">
                <div class="card-header">
                    <div>
                        <h3 class="card-title"><?= e($viewMsg['subject'] ?: 'Konu belirtilmemiş') ?></h3>
                        <p style="font-size:0.8rem;color:var(--muted);margin-top:4px;">
                            <strong><?= e($viewMsg['name']) ?></strong> —
                            <a href="mailto:<?= e($viewMsg['email']) ?>"><?= e($viewMsg['email']) ?></a> —
                            <?= formatDateTR($viewMsg['created_at']) ?>
                        </p>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <a href="mailto:<?= e($viewMsg['email']) ?>?subject=Re: <?= urlencode($viewMsg['subject'] ?: 'Mesajınız hakkında') ?>"
                           class="btn-admin btn-admin-accent btn-admin-sm">✉️ Yanıtla</a>
                        <a href="/admin/messages.php" class="btn-admin btn-admin-outline btn-admin-sm">← Geri</a>
                    </div>
                </div>
                <div class="card-body">
                    <div style="background:var(--bg);padding:24px;border-radius:var(--radius);font-size:0.95rem;line-height:1.8;color:var(--text);white-space:pre-line;">
                        <?= e($viewMsg['message']) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Mesaj Listesi -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Tüm Mesajlar
                        <span class="badge badge-muted" style="margin-left:8px;"><?= $total ?></span>
                    </h3>
                    <div style="display:flex;gap:8px;">
                        <a href="?filter=all"    class="btn-admin btn-admin-sm <?= $filter==='all'    ? 'btn-admin-primary' : 'btn-admin-outline' ?>">Tümü</a>
                        <a href="?filter=unread" class="btn-admin btn-admin-sm <?= $filter==='unread' ? 'btn-admin-primary' : 'btn-admin-outline' ?>">Okunmamış</a>
                        <a href="?filter=read"   class="btn-admin btn-admin-sm <?= $filter==='read'   ? 'btn-admin-primary' : 'btn-admin-outline' ?>">Okunmuş</a>
                    </div>
                </div>
                <div class="table-wrap">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state">
                            <div class="es-icon">✉️</div>
                            <h4>Mesaj bulunamadı</h4>
                        </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Durum</th>
                                <th>Gönderen</th>
                                <th>Konu</th>
                                <th>Mesaj (Kısa)</th>
                                <th>Tarih</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                            <tr style="<?= !$msg['is_read'] ? 'background:#FEFAF5;' : '' ?>">
                                <td>
                                    <?php if (!$msg['is_read']): ?>
                                        <span class="badge badge-info">Yeni</span>
                                    <?php else: ?>
                                        <span class="badge badge-muted">Okundu</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="font-size:0.875rem;"><?= e($msg['name']) ?></strong>
                                    <br><a href="mailto:<?= e($msg['email']) ?>" style="font-size:0.78rem;color:var(--muted);"><?= e($msg['email']) ?></a>
                                </td>
                                <td style="font-size:0.875rem;"><?= e($msg['subject'] ?: '—') ?></td>
                                <td style="font-size:0.82rem;color:var(--muted);max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= e(mb_substr($msg['message'], 0, 80)) ?>…
                                </td>
                                <td style="font-size:0.8rem;color:var(--muted);white-space:nowrap;">
                                    <?= formatDateTR($msg['created_at']) ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="?view=<?= $msg['id'] ?>" class="btn-admin btn-admin-outline btn-admin-sm">👁 Oku</a>
                                        <a href="/admin/delete-message.php?id=<?= $msg['id'] ?>&csrf=<?= e(csrfToken()) ?>"
                                           class="btn-admin btn-admin-ghost btn-admin-sm"
                                           data-confirm="Bu mesajı silmek istediğinize emin misiniz?"
                                           data-href="/admin/delete-message.php?id=<?= $msg['id'] ?>&csrf=<?= e(csrfToken()) ?>">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>&filter=<?= $filter ?>" class="page-btn">‹</a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                        <a href="?page=<?= $i ?>&filter=<?= $filter ?>"
                           class="page-btn <?= $i===$page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page+1 ?>&filter=<?= $filter ?>" class="page-btn">›</a>
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
