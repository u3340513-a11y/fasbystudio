<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Zaten giriş yapılmışsa panele yönlendir
if (isAdminLoggedIn()) {
    redirect('/admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Kullanıcı adı ve şifre zorunludur.';
        } else {
            require_once dirname(__DIR__) . '/includes/db.php';

            $stmt = $pdo->prepare("SELECT id, username, password FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([sanitize($username)]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Oturumu güvenli ayarla
                session_regenerate_id(true);
                $_SESSION['admin_id']       = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_login_ip'] = $_SERVER['REMOTE_ADDR'];

                // Son giriş zamanını güncelle
                $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                // Redirect'ten geldiyse oraya dön
                $ref = $_GET['ref'] ?? '/admin/index.php';
                if (!str_starts_with($ref, '/admin/')) $ref = '/admin/index.php';
                redirect($ref);
            } else {
                // Brute-force koruması için delay
                sleep(1);
                $error = 'Kullanıcı adı veya şifre hatalı.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi | Fasby Studio</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <style>
        body { background: #0F0F0F; }
    </style>
</head>
<body class="login-page">

    <div class="login-box">
        <div class="login-logo">
            <h1>Fasby<em>Studio</em></h1>
            <p>Admin Paneli</p>
        </div>

        <?php if ($error): ?>
            <div class="login-alert error" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="login-form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" class="login-input"
                       placeholder="Kullanıcı adınız" required
                       autocomplete="username"
                       value="<?= isset($_POST['username']) ? e(sanitize($_POST['username'])) : '' ?>">
            </div>

            <div class="login-form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" class="login-input"
                       placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" class="login-btn">Giriş Yap</button>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:0.8rem;color:#999;">
            <a href="/" style="color:#C9A882;">← Siteye Dön</a>
        </p>
    </div>

</body>
</html>
