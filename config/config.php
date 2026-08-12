<?php
// ============================================================
// FASBY STUDIO - Site Yapılandırması
// Bu dosyadaki değerleri cPanel ayarlarınıza göre güncelleyin
// ============================================================

// Site Bilgileri
define('SITE_NAME',        'Fasby Studio');
define('SITE_URL',         'https://fasbystudio.com'); // Yerel test için: http://localhost/fasbystudio.com
define('SITE_DESCRIPTION', 'Etsy\'de özgün, el yapımı tişört tasarımları. Her tişört bir hikaye anlatır.');
define('SITE_KEYWORDS',    'tişört tasarım, etsy tshirt, özgün tasarım, türk tasarımcı, grafik tişört');
define('SITE_AUTHOR',      'Fasby Studio');

// Etsy & Sosyal Medya
define('ETSY_SHOP_URL',  'https://www.etsy.com/shop/FasbyStudio'); // Gerçek Etsy mağaza URL'nizi yazın
define('INSTAGRAM_URL',  ''); // https://instagram.com/fasbystudio
define('PINTEREST_URL',  ''); // https://pinterest.com/fasbystudio
define('TWITTER_URL',    '');

// İletişim
define('CONTACT_EMAIL', 'info@fasbystudio.com');

// Veritabanı - credentials config/env.php dosyasından okunur (git'e eklenmez)
$_envFile = __DIR__ . '/env.php';
if (file_exists($_envFile)) {
    require_once $_envFile;
}
unset($_envFile);

define('DB_HOST',    getenv('DB_HOST') ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME') ?: '');
define('DB_USER',    getenv('DB_USER') ?: '');
define('DB_PASS',    getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Yükleme Ayarları
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');

// Güvenlik
define('SESSION_NAME', 'fasby_sess');

// Oturumu başlat
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start([
        'cookie_httponly'  => true,
        'cookie_samesite'  => 'Strict',
        'use_strict_mode'  => true,
        'gc_maxlifetime'   => 3600,
    ]);
}
