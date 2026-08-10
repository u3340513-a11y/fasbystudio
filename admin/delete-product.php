<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$id   = (int)($_GET['id']   ?? 0);
$csrf = $_GET['csrf'] ?? '';

if (!$id || !verifyCsrf($csrf)) {
    setFlash('error', 'Geçersiz istek.');
    redirect('/admin/products.php');
}

$stmt = $pdo->prepare("SELECT id, title, image FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Ürün bulunamadı.');
    redirect('/admin/products.php');
}

// Görseli sil
deleteImage($product['image']);

// Kaydı sil
$pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);

// CSRF token yenile (yeniden kullanımı engelle)
unset($_SESSION['csrf_token']);

setFlash('success', '"' . sanitize($product['title']) . '" ürünü silindi.');
redirect('/admin/products.php');
