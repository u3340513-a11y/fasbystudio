<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$id   = (int)($_GET['id']   ?? 0);
$csrf = $_GET['csrf'] ?? '';

if (!$id || !verifyCsrf($csrf)) {
    setFlash('error', 'Geçersiz istek.');
    redirect('/admin/categories.php');
}

$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE id = ?");
$stmt->execute([$id]);
$cat = $stmt->fetch();

if (!$cat) {
    setFlash('error', 'Kategori bulunamadı.');
    redirect('/admin/categories.php');
}

// Ürünü olan kategoriyi silme
$count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
$count->execute([$id]);
if ((int)$count->fetchColumn() > 0) {
    setFlash('error', 'İçinde ürün olan bir kategori silinemez. Önce ürünleri başka bir kategoriye taşıyın.');
    redirect('/admin/categories.php');
}

$pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
unset($_SESSION['csrf_token']);

setFlash('success', '"' . sanitize($cat['name']) . '" kategorisi silindi.');
redirect('/admin/categories.php');
