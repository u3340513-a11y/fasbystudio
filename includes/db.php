<?php
require_once dirname(__DIR__) . '/config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('[FasbyStudio] DB bağlantı hatası: ' . $e->getMessage());
    http_response_code(500);
    die('<h1 style="font-family:sans-serif;text-align:center;margin-top:10%">Sunucu hatası. Lütfen daha sonra tekrar deneyin.</h1>');
}
