<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$id   = (int)($_GET['id']   ?? 0);
$csrf = $_GET['csrf'] ?? '';

if (!$id || !verifyCsrf($csrf)) {
    setFlash('error', 'Geçersiz istek.');
    redirect('/admin/messages.php');
}

$pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$id]);
unset($_SESSION['csrf_token']);

setFlash('success', 'Mesaj silindi.');
redirect('/admin/messages.php');
