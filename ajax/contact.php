<?php
// İletişim formu AJAX endpoint
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Yalnızca POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

// CSRF kontrolü
$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrf($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.']);
    exit;
}

// Girdi al & doğrula
$errors = [];
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || mb_strlen($name) < 2) {
    $errors['name'] = 'Lütfen geçerli bir isim girin.';
} elseif (mb_strlen($name) > 255) {
    $errors['name'] = 'Ad soyad en fazla 255 karakter olabilir.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Lütfen geçerli bir e-posta adresi girin.';
} elseif (mb_strlen($email) > 255) {
    $errors['email'] = 'E-posta adresi çok uzun.';
}

if (empty($message) || mb_strlen($message) < 10) {
    $errors['message'] = 'Mesajınız en az 10 karakter olmalıdır.';
} elseif (mb_strlen($message) > 2000) {
    $errors['message'] = 'Mesaj en fazla 2000 karakter olabilir.';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors, 'message' => 'Lütfen formu eksiksiz doldurun.']);
    exit;
}

// IP al (proxy arkasında güvenli)
$ip = $_SERVER['HTTP_CF_CONNECTING_IP']
    ?? $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['REMOTE_ADDR']
    ?? null;
if ($ip && strpos($ip, ',') !== false) {
    $ip = trim(explode(',', $ip)[0]);
}

// Rate limit (basit: aynı IP'den 5 dakikada 3'ten fazla mesaj)
$rateStmt = $pdo->prepare("
    SELECT COUNT(*) FROM contact_messages
    WHERE ip_address = ? AND created_at > NOW() - INTERVAL 5 MINUTE
");
$rateStmt->execute([$ip]);
if ((int)$rateStmt->fetchColumn() >= 3) {
    echo json_encode(['success' => false, 'message' => 'Çok fazla mesaj gönderildi. Lütfen birkaç dakika bekleyin.']);
    exit;
}

// Kaydet
try {
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (name, email, subject, message, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        sanitize($name),
        $email,
        sanitize($subject),
        sanitize($message),
        $ip,
    ]);

    // CSRF token yenile
    unset($_SESSION['csrf_token']);

    echo json_encode(['success' => true, 'message' => 'Mesajınız başarıyla gönderildi!']);
} catch (PDOException $e) {
    error_log('[FasbyStudio] Contact form DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası. Lütfen tekrar deneyin.']);
}
