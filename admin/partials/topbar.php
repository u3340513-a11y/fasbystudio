<?php
// Admin topbar partial
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
$initial = strtoupper(mb_substr($adminUsername, 0, 1));
?>
<header class="topbar">
    <div style="display:flex;align-items:center;gap:16px;">
        <!-- Mobil hamburger -->
        <button id="sidebarToggle" aria-label="Menüyü aç/kapat"
                style="display:none;background:none;border:none;cursor:pointer;padding:6px;border-radius:6px;"
                class="hamburger-admin">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6"  x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <h1 class="topbar-title">
            <?= e(ucfirst(str_replace(['-', '.php', '_'], [' ', '', ' '], basename($_SERVER['PHP_SELF'])))) ?>
        </h1>
    </div>

    <div class="topbar-right">
        <a href="/" target="_blank" class="topbar-site-link">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            Siteyi Gör
        </a>
        <div class="admin-avatar" title="<?= e($adminUsername) ?>"><?= e($initial) ?></div>
    </div>
</header>

<style>
@media (max-width: 768px) {
    .hamburger-admin { display: block !important; }
    #sidebarOverlay  { display: block !important; }
}
</style>
