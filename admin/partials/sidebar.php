<?php
// Admin sidebar partial
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="logo">Fasby<em>Studio</em></div>
        <span>Admin Paneli</span>
    </div>

    <nav class="sidebar-nav">
        <p class="nav-section-title">Genel</p>

        <a href="/admin/index.php"
           class="sidebar-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <span class="s-icon">📊</span>
            Dashboard
        </a>

        <p class="nav-section-title">İçerik Yönetimi</p>

        <a href="/admin/products.php"
           class="sidebar-link <?= in_array($currentPage, ['products.php','add-product.php','edit-product.php']) ? 'active' : '' ?>">
            <span class="s-icon">🎨</span>
            Ürünler
        </a>

        <a href="/admin/add-product.php"
           class="sidebar-link <?= $currentPage === 'add-product.php' ? 'active' : '' ?>"
           style="padding-left:44px;font-size:0.83rem;">
            <span class="s-icon" style="font-size:0.85rem;">＋</span>
            Ürün Ekle
        </a>

        <a href="/admin/categories.php"
           class="sidebar-link <?= in_array($currentPage, ['categories.php','add-category.php']) ? 'active' : '' ?>">
            <span class="s-icon">🏷️</span>
            Kategoriler
        </a>

        <a href="/admin/messages.php"
           class="sidebar-link <?= $currentPage === 'messages.php' ? 'active' : '' ?>">
            <span class="s-icon">✉️</span>
            Mesajlar
        </a>

        <p class="nav-section-title">Ayarlar</p>

        <a href="/admin/settings.php"
           class="sidebar-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
            <span class="s-icon">⚙️</span>
            Ayarlar
        </a>

        <a href="/" target="_blank" class="sidebar-link">
            <span class="s-icon">🌐</span>
            Siteyi Gör
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/admin/logout.php">
            <span>🚪</span>
            Çıkış Yap
        </a>
    </div>

</aside>

<!-- Mobil overlay -->
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99;backdrop-filter:blur(2px);"
     onclick="document.getElementById('sidebar').classList.remove('open');this.classList.remove('open')"></div>
