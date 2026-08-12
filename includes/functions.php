<?php
// ============================================================
// FASBY STUDIO - Yardımcı Fonksiyonlar
// ============================================================

/** XSS korumalı çıktı */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** Girdiyi temizle */
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** CSRF token üret */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** CSRF token doğrula */
function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Yönlendirme */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/** Admin giriş kontrolü */
function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

/** Admin gerektiren sayfalar için */
function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        redirect('/admin/login.php?ref=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

/** Türkçe uyumlu slug üret */
function slugify(string $text): string {
    $tr = ['ı','ğ','ü','ş','ö','ç','İ','Ğ','Ü','Ş','Ö','Ç'];
    $en = ['i','g','u','s','o','c','i','g','u','s','o','c'];
    $text = str_replace($tr, $en, mb_strtolower($text, 'UTF-8'));
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
}

/** Fiyat formatla */
function formatPrice(?float $price, string $currency = 'USD'): string {
    if ($price === null) return '';
    $symbols = ['USD' => '$', 'EUR' => '€', 'TRY' => '₺', 'GBP' => '£'];
    $sym = $symbols[$currency] ?? $currency . ' ';
    return $sym . number_format($price, 2, ',', '.');
}

/** Ürün görseli URL'si */
function productImageUrl(?string $image): string {
    if (empty($image)) return '/assets/images/placeholder.svg';
    return '/uploads/' . e($image);
}

/** Görsel yükle (güvenli) */
function uploadImage(array $file, string $prefix = 'product') {
    $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxBytes = MAX_UPLOAD_SIZE;

    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $maxBytes || $file['size'] === 0) return false;

    // Gerçek MIME tipi magic byte ile doğrula (uzantıya güvenme)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowed, true)) return false;

    $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext  = $exts[$mimeType];

    $filename   = $prefix . '_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $uploadPath = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) return false;

    return $filename;
}

/** Görsel sil */
function deleteImage(?string $filename): void {
    if (!empty($filename)) {
        $path = UPLOAD_DIR . basename($filename);
        if (file_exists($path) && is_file($path)) {
            unlink($path);
        }
    }
}

/** Tarih Türkçe formatla */
function formatDateTR(string $dateStr): string {
    $months = [
        1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',
        7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'
    ];
    $d = new DateTime($dateStr);
    return $d->format('d') . ' ' . $months[(int)$d->format('m')] . ' ' . $d->format('Y');
}

/** Tüm site ayarlarını DB'den getir */
function getAllSettings(PDO $pdo): array {
    try {
        $rows = $pdo->query("SELECT `key`, `value` FROM settings")->fetchAll();
        $out  = [];
        foreach ($rows as $row) {
            $out[$row['key']] = $row['value'] ?? '';
        }
        return $out;
    } catch (PDOException $e) {
        return [];
    }
}

/** Tek ayar kaydet (upsert) */
function saveSetting(PDO $pdo, string $key, string $value): void {
    $stmt = $pdo->prepare(
        "INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?"
    );
    $stmt->execute([$key, $value, $value]);
}

/** Flash mesajı ayarla */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Flash mesajı al ve temizle */
function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
