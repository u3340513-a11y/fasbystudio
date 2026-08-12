<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Ayarları DB'den yükle, config.php sabitleri fallback
$settings = getAllSettings($pdo);
$cfg = [
    'site_name'   => ($settings['site_name']        ?? '') ?: SITE_NAME,
    'description' => ($settings['site_description'] ?? '') ?: SITE_DESCRIPTION,
    'keywords'    => ($settings['site_keywords']    ?? '') ?: SITE_KEYWORDS,
    'author'      => ($settings['site_author']      ?? '') ?: SITE_AUTHOR,
    'email'       => ($settings['contact_email']    ?? '') ?: CONTACT_EMAIL,
    'etsy'        => ($settings['etsy_shop_url']    ?? '') ?: ETSY_SHOP_URL,
    'instagram'   => $settings['instagram_url']    ?? INSTAGRAM_URL,
    'pinterest'   => $settings['pinterest_url']    ?? PINTEREST_URL,
    'twitter'     => $settings['twitter_url']      ?? TWITTER_URL,
    'logo'        => $settings['logo_image']       ?? '',
];

// Tüm aktif kategorileri getir
$stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name");
$categories = $stmt->fetchAll();

// Tüm aktif ürünleri getir
$stmt = $pdo->query("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.active = 1
    ORDER BY p.featured DESC, p.sort_order ASC, p.created_at DESC
");
$products = $stmt->fetchAll();

// Sayılar (hero istatistikleri)
$totalProducts = count($products);
$stmt2 = $pdo->query("SELECT COUNT(*) FROM products WHERE active=1 AND featured=1");
$featuredCount = (int)$stmt2->fetchColumn();

// SEO meta
$meta = [
    'title'       => $cfg['site_name'] . ' | Etsy\'de Özgün Tişört Tasarımları',
    'description' => $cfg['description'],
    'keywords'    => $cfg['keywords'],
    'url'         => SITE_URL,
    'og_image'    => SITE_URL . '/assets/images/og-image.jpg',
];
?>
<!DOCTYPE html>
<html lang="tr" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?= e($meta['title']) ?></title>
    <meta name="description"  content="<?= e($meta['description']) ?>">
    <meta name="keywords"     content="<?= e($meta['keywords']) ?>">
    <meta name="author"       content="<?= e($cfg['author']) ?>">
    <meta name="robots"       content="index, follow">
    <link rel="canonical"     href="<?= e($meta['url']) ?>">

    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= e($meta['url']) ?>">
    <meta property="og:title"       content="<?= e($meta['title']) ?>">
    <meta property="og:description" content="<?= e($meta['description']) ?>">
    <meta property="og:image"       content="<?= e($meta['og_image']) ?>">
    <meta property="og:locale"      content="tr_TR">
    <meta property="og:site_name"   content="<?= e($cfg['site_name']) ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= e($meta['title']) ?>">
    <meta name="twitter:description" content="<?= e($meta['description']) ?>">
    <meta name="twitter:image"       content="<?= e($meta['og_image']) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    <link rel="apple-touch-icon"          href="/assets/images/apple-touch-icon.png">

    <!-- Fonts & CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- Schema.org Yapılandırılmış Veri -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Store",
        "name": "<?= e(SITE_NAME) ?>",
        "url": "<?= e(SITE_URL) ?>",
        "description": "<?= e($cfg['description']) ?>",
        "image": "<?= e($meta['og_image']) ?>",
        "priceRange": "$$",
        "sameAs": [
            "<?= e($cfg['etsy']) ?>"
            <?= $cfg['instagram'] ? ',"' . e($cfg['instagram']) . '"' : '' ?>
            <?= $cfg['pinterest'] ? ',"' . e($cfg['pinterest']) . '"' : '' ?>
        ],
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?= e(SITE_URL) ?>/#urunler",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
</head>
<body>

<!-- ============================================================
     NAVİGASYON
     ============================================================ -->
<header>
    <nav class="navbar" id="navbar" role="navigation" aria-label="Ana navigasyon">
        <div class="container">
            <div class="navbar-inner">

                <a href="/" class="navbar-logo" aria-label="<?= e($cfg['site_name']) ?> Ana Sayfa">
                    <?php if ($cfg['logo']): ?>
                        <img src="/uploads/<?= e($cfg['logo']) ?>" alt="<?= e($cfg['site_name']) ?>" style="max-height:40px;width:auto;">
                    <?php else: ?>
                        Fasby<em>Studio</em>
                    <?php endif; ?>
                </a>

                <ul class="navbar-menu" role="list">
                    <li><a href="#anasayfa"  class="active">Ana Sayfa</a></li>
                    <li><a href="#urunler">Tasarımlar</a></li>
                    <li><a href="#hakkimda">Hakkımda</a></li>
                    <li><a href="#iletisim">İletişim</a></li>
                </ul>

                <div style="display:flex;align-items:center;gap:16px;">
                    <a href="<?= e($cfg['etsy']) ?>" class="navbar-etsy" target="_blank" rel="noopener noreferrer" aria-label="Etsy mağazamı ziyaret et">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M8.075 23.52C.565 23.27 0 22.51 0 14.96V9.04C0 1.49.565.73 8.075.48 9.42.44 10.83.42 12 .42s2.58.02 3.925.06C23.435.73 24 1.49 24 9.04v5.92c0 7.55-.565 8.31-8.075 8.56C14.58 23.56 13.17 23.58 12 23.58s-2.58-.02-3.925-.06zM7.455 8.37l3.12 3.27v3.93c0 .38.31.69.69.69h1.47c.38 0 .69-.31.69-.69v-3.93l3.12-3.27c.28-.29.27-.75-.03-1.03a.737.737 0 0 0-1.05.03l-2.73 2.86-2.73-2.86a.74.74 0 0 0-1.05-.03.737.737 0 0 0-.03 1.03z"/>
                        </svg>
                        Etsy Mağazam
                    </a>
                    <button class="hamburger" id="hamburger" aria-label="Menüyü aç/kapat" aria-expanded="false" aria-controls="mobileMenu">
                        <span></span><span></span><span></span>
                    </button>
                </div>

            </div>
        </div>
    </nav>

    <!-- Mobil Menü -->
    <div class="mobile-menu" id="mobileMenu" role="dialog" aria-label="Mobil menü">
        <a href="#anasayfa">Ana Sayfa</a>
        <a href="#urunler">Tasarımlar</a>
        <a href="#hakkimda">Hakkımda</a>
        <a href="#iletisim">İletişim</a>
        <a href="<?= e($cfg['etsy']) ?>" class="navbar-etsy" target="_blank" rel="noopener noreferrer">
            🛍️ Etsy Mağazam
        </a>
    </div>
</header>

<!-- ============================================================
     HERO
     ============================================================ -->
<section class="hero" id="anasayfa" aria-label="Ana bölüm">
    <div class="container">
        <div class="hero-inner">

            <!-- Sol: Metin -->
            <div class="hero-content">
                <div class="hero-badge">
                    <span></span>
                    Etsy Onaylı Tasarımcı
                </div>

                <h1 class="hero-title">
                    Tasarımın Gücünü<br>
                    <em>Hisset</em>
                </h1>

                <p class="hero-desc">
                    Her tişört bir hikaye anlatır. Özgün, el yapımı tasarımlarımla
                    tarzını yansıt ve yaratıcılığı giy. Etsy mağazamda binlerce
                    mutlu müşteri beni tercih etti.
                </p>

                <div class="hero-actions">
                    <a href="#urunler" class="btn btn-primary btn-lg">
                        Tasarımları Keşfet
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <a href="<?= e($cfg['etsy']) ?>" class="btn btn-etsy btn-lg" target="_blank" rel="noopener noreferrer">
                        🛍️ Etsy'de Al
                    </a>
                </div>

                <hr class="hero-divider">

                <div class="hero-stats" role="list" aria-label="İstatistikler">
                    <div class="stat-item" role="listitem">
                        <span class="stat-number" data-count="<?= $totalProducts ?>" data-suffix="+"><?= $totalProducts ?>+</span>
                        <span class="stat-label">Özgün Tasarım</span>
                    </div>
                    <div class="stat-item" role="listitem">
                        <span class="stat-number" data-count="1200" data-suffix="+">1200+</span>
                        <span class="stat-label">Mutlu Müşteri</span>
                    </div>
                    <div class="stat-item" role="listitem">
                        <span class="stat-number" data-count="5" data-suffix=" Yıl">5 Yıl</span>
                        <span class="stat-label">Deneyim</span>
                    </div>
                </div>
            </div>

            <!-- Sağ: Görsel -->
            <div class="hero-visual">
                <?php
                // Öne çıkan ürün varsa göster, yoksa dekoratif arkaplan
                $featured = array_filter($products, fn($p) => $p['featured'] == 1);
                $heroProduct = !empty($featured) ? array_values($featured)[0] : null;
                ?>
                <div class="hero-img-frame">
                    <?php if ($heroProduct && $heroProduct['image']): ?>
                        <img src="<?= e(productImageUrl($heroProduct['image'])) ?>"
                             alt="<?= e($heroProduct['title']) ?>"
                             width="600" height="750"
                             loading="eager">
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=600&h=750&fit=crop&q=80"
                             alt="Özgün grafik tişört tasarımı"
                             width="600" height="750"
                             loading="eager"
                             style="width:100%;height:100%;object-fit:cover;">
                    <?php endif; ?>
                </div>

                <!-- Floaty kart -->
                <div class="hero-float-card" aria-hidden="true">
                    <strong>⭐ 4.9 / 5</strong>
                    <span>1.200+ değerlendirme</span>
                    <div class="dot-row">
                        <div class="dot" style="background:#C9A882"></div>
                        <div class="dot" style="background:#A8845F"></div>
                        <div class="dot" style="background:#1A1A1A"></div>
                        <div class="dot" style="background:#F5EDE1"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================
     ÜRÜNLER
     ============================================================ -->
<section class="products-section" id="urunler" aria-labelledby="urunler-baslik">
    <div class="container">

        <div class="section-header anim">
            <span class="section-tag">Koleksiyon</span>
            <h2 class="section-title" id="urunler-baslik">Tasarımlarım</h2>
            <p class="section-desc">Her biri özenle tasarlanmış, Etsy'de binlerce kişinin favorisi olan özgün tişörtlerim.</p>
        </div>

        <!-- Kategori filtresi -->
        <?php if (!empty($categories)): ?>
        <div class="filter-bar" role="group" aria-label="Kategori filtresi">
            <button class="filter-btn active" data-cat="all">Tümü</button>
            <?php foreach ($categories as $cat): ?>
                <button class="filter-btn" data-cat="<?= e($cat['slug']) ?>">
                    <?= e($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Ürün ızgarası -->
        <div class="products-grid" role="list">

            <?php if (empty($products)): ?>
                <div class="no-products" role="listitem">
                    <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    <p>Henüz ürün eklenmedi. <a href="/admin/" style="color:var(--accent)">Admin paneli</a>nden ürün ekleyebilirsiniz.</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    $imgUrl  = productImageUrl($product['image'] ?? '');
                    $price   = $product['price'] ? formatPrice((float)$product['price'], $product['currency']) : '';
                    $catName = $product['category_name'] ?? '';
                    $catSlug = $product['category_slug'] ?? '';
                    ?>
                    <article class="product-card"
                        role="listitem"
                        tabindex="0"
                        aria-label="<?= e($product['title']) ?> - Detayları görüntüle"
                        data-category="<?= e($catSlug) ?>"
                        data-category-name="<?= e($catName) ?>"
                        data-title="<?= e($product['title']) ?>"
                        data-description="<?= e($product['description'] ?? '') ?>"
                        data-price="<?= e($price) ?>"
                        data-currency="<?= e($product['currency']) ?>"
                        data-image="<?= e($imgUrl) ?>"
                        data-tags="<?= e($product['tags'] ?? '') ?>"
                        data-etsy-link="<?= e($product['etsy_link'] ?? '') ?>"
                        itemscope itemtype="https://schema.org/Product">

                        <?php if ($product['featured']): ?>
                            <span class="product-badge featured" aria-label="Öne çıkan ürün">⭐ Öne Çıkan</span>
                        <?php endif; ?>

                        <div class="product-card-image">
                            <img src="<?= e($imgUrl) ?>"
                                 alt="<?= e($product['title']) ?>"
                                 loading="lazy"
                                 width="400" height="400"
                                 itemprop="image">
                            <div class="card-overlay" aria-hidden="true">
                                <span class="overlay-text">Detayları Gör</span>
                            </div>
                        </div>

                        <div class="product-card-body">
                            <?php if ($catName): ?>
                                <p class="product-cat" itemprop="category"><?= e($catName) ?></p>
                            <?php endif; ?>
                            <h3 class="product-title" itemprop="name"><?= e($product['title']) ?></h3>
                            <?php if ($price): ?>
                                <p class="product-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                                    <span itemprop="price" content="<?= e($product['price']) ?>"><?= e($price) ?></span>
                                    <small itemprop="priceCurrency" content="<?= e($product['currency']) ?>">Etsy</small>
                                </p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>

                <div class="no-products" id="noProducts" style="display:none" role="status" aria-live="polite">
                    <p>Bu kategoride ürün bulunamadı.</p>
                </div>
            <?php endif; ?>

        </div>

        <!-- Alt CTA -->
        <?php if (!empty($products)): ?>
        <div style="text-align:center;margin-top:56px;" class="anim">
            <p style="color:var(--muted);margin-bottom:20px;font-size:0.95rem;">Tüm tasarımları Etsy mağazamda keşfedin</p>
            <a href="<?= e($cfg['etsy']) ?>" class="btn btn-etsy btn-lg" target="_blank" rel="noopener noreferrer">
                🛍️ Etsy Mağazama Git
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- ============================================================
     HAKKIMDA
     ============================================================ -->
<section class="about-section" id="hakkimda" aria-labelledby="hakkimda-baslik">
    <div class="container">
        <div class="about-inner">

            <!-- Görsel -->
            <div class="about-img-wrap anim">
                <div class="about-img-main">
                    <img src="https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=480&h=600&fit=crop&q=80"
                         alt="Tasarımcı çalışma alanı - Fasby Studio"
                         loading="lazy"
                         width="480" height="600">
                </div>
                <div class="about-img-accent">
                    <div class="accent-inner">
                        <strong>5+</strong>
                        <span>Yıl<br>Deneyim</span>
                    </div>
                </div>
            </div>

            <!-- İçerik -->
            <div class="about-content anim anim-d2">
                <div class="section-header" style="text-align:left;margin-bottom:28px;">
                    <span class="section-tag">Benim Hikayem</span>
                    <h2 class="section-title" id="hakkimda-baslik">
                        Tasarım Benim<br><em>Dilim</em>
                    </h2>
                </div>

                <p class="about-text">
                    Merhaba! Ben Fasby Studio'nun arkasındaki tasarımcıyım. Yıllardır grafik tasarım
                    ve illüstrasyon tutkusuyla çalışıyorum. Her tasarımım, bir duygunun, bir hikayenin
                    ya da bir anın yansıması.
                </p>
                <p class="about-text">
                    Etsy üzerinden dünya genelinde binlerce kişiye ulaşarak tasarımlarımı tişörtlere
                    taşıyorum. Her ürün, kaliteli baskı teknikleriyle hayata geçiriliyor ve sevgiyle
                    paketleniyor.
                </p>

                <div class="about-features">
                    <div class="feature-box">
                        <div class="feature-icon">🎨</div>
                        <h4 class="feature-title">Özgün Tasarım</h4>
                        <p class="feature-text">Her tasarım elle çizilmiş, özgün ve sadece Fasby Studio'ya özel.</p>
                    </div>
                    <div class="feature-box">
                        <div class="feature-icon">✨</div>
                        <h4 class="feature-title">Kaliteli Baskı</h4>
                        <p class="feature-text">DTG ve serigrafi teknikleriyle yıkamaya dayanıklı baskı.</p>
                    </div>
                    <div class="feature-box">
                        <div class="feature-icon">🌍</div>
                        <h4 class="feature-title">Dünya Geneli</h4>
                        <p class="feature-text">Etsy üzerinden dünyanın dört bir yanına hızlı kargo.</p>
                    </div>
                    <div class="feature-box">
                        <div class="feature-icon">💚</div>
                        <h4 class="feature-title">Sürdürülebilir</h4>
                        <p class="feature-text">Organik pamuk ve çevre dostu baskı malzemeleri kullanıyorum.</p>
                    </div>
                </div>

                <div style="margin-top:36px;">
                    <a href="<?= e($cfg['etsy']) ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                        Mağazamı Ziyaret Et
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/>
                        </svg>
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================
     İLETİŞİM
     ============================================================ -->
<section class="contact-section" id="iletisim" aria-labelledby="iletisim-baslik">
    <div class="container">

        <div class="section-header anim">
            <span class="section-tag">İletişim</span>
            <h2 class="section-title" id="iletisim-baslik">Benimle İletişime Geç</h2>
            <p class="section-desc">Tasarım hakkında soru sormak, özel sipariş vermek veya iş birliği yapmak için yazabilirsin.</p>
        </div>

        <div class="contact-inner">

            <!-- Sol: Bilgiler -->
            <div class="contact-info anim">
                <h3>Merhaba,<br>Sana Yardımcı Olmaktan<br>Mutluluk Duyarım! 👋</h3>
                <p>Sorularını, özel tasarım isteklerini veya iş birliği tekliflerini bana iletebilirsin. Genellikle 24 saat içinde yanıt veriyorum.</p>

                <div class="contact-links">
                    <a href="mailto:<?= e($cfg['email']) ?>" class="contact-link" aria-label="E-posta gönder">
                        <span class="contact-link-icon">✉️</span>
                        <span><?= e($cfg['email']) ?></span>
                    </a>
                    <a href="<?= e($cfg['etsy']) ?>" class="contact-link" target="_blank" rel="noopener noreferrer" aria-label="Etsy mağazası">
                        <span class="contact-link-icon">🛒</span>
                        <span>Etsy Mağazam</span>
                    </a>
                    <?php if ($cfg['instagram']): ?>
                    <a href="<?= e($cfg['instagram']) ?>" class="contact-link" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                        <span class="contact-link-icon">📸</span>
                        <span>Instagram</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($cfg['pinterest']): ?>
                    <a href="<?= e($cfg['pinterest']) ?>" class="contact-link" target="_blank" rel="noopener noreferrer" aria-label="Pinterest">
                        <span class="contact-link-icon">📌</span>
                        <span>Pinterest</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sağ: Form -->
            <div class="anim anim-d2">
                <form class="contact-form" id="contactForm" novalidate aria-label="İletişim formu">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label for="contact-name">Ad Soyad *</label>
                            <input type="text" id="contact-name" name="name" class="form-control"
                                   placeholder="Adınız Soyadınız" required autocomplete="name"
                                   maxlength="255">
                            <span class="form-error" data-error="name"></span>
                        </div>
                        <div class="form-group">
                            <label for="contact-email">E-posta *</label>
                            <input type="email" id="contact-email" name="email" class="form-control"
                                   placeholder="ornek@email.com" required autocomplete="email"
                                   maxlength="255">
                            <span class="form-error" data-error="email"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contact-subject">Konu</label>
                        <input type="text" id="contact-subject" name="subject" class="form-control"
                               placeholder="Mesajınızın konusu" maxlength="500">
                    </div>

                    <div class="form-group">
                        <label for="contact-message">Mesaj *</label>
                        <textarea id="contact-message" name="message" class="form-control"
                                  placeholder="Mesajınızı buraya yazın…" required
                                  rows="5" maxlength="2000"></textarea>
                        <span class="form-error" data-error="message"></span>
                    </div>

                    <div class="form-success" id="formSuccess">
                        ✅ Mesajınız başarıyla gönderildi! En kısa sürede yanıtlayacağım.
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                        Mesaj Gönder
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </button>
                </form>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer class="footer" role="contentinfo">
    <div class="container">

        <div class="footer-inner">

            <!-- Marka -->
            <div class="footer-brand">
                <span class="footer-logo">Fasby<em>Studio</em></span>
                <p>Özgün, el yapımı tişört tasarımları. Her tişört bir hikaye anlatır. Etsy üzerinden dünya genelinde satış.</p>
                <div class="footer-socials">
                    <a href="<?= e($cfg['etsy']) ?>" class="social-btn" target="_blank" rel="noopener noreferrer" aria-label="Etsy">🛒</a>
                    <?php if ($cfg['instagram']): ?>
                    <a href="<?= e($cfg['instagram']) ?>" class="social-btn" target="_blank" rel="noopener noreferrer" aria-label="Instagram">📸</a>
                    <?php endif; ?>
                    <?php if ($cfg['pinterest']): ?>
                    <a href="<?= e($cfg['pinterest']) ?>" class="social-btn" target="_blank" rel="noopener noreferrer" aria-label="Pinterest">📌</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bağlantılar -->
            <div class="footer-col">
                <h4>Sayfalar</h4>
                <ul>
                    <li><a href="#anasayfa">Ana Sayfa</a></li>
                    <li><a href="#urunler">Tasarımlar</a></li>
                    <li><a href="#hakkimda">Hakkımda</a></li>
                    <li><a href="#iletisim">İletişim</a></li>
                </ul>
            </div>

            <!-- Kategoriler -->
            <div class="footer-col">
                <h4>Kategoriler</h4>
                <ul>
                    <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                    <li>
                        <a href="#urunler" onclick="document.querySelector('[data-cat=\'<?= e($cat['slug']) ?>\']')?.click()">
                            <?= e($cat['name']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- İletişim -->
            <div class="footer-col">
                <h4>İletişim</h4>
                <ul>
                    <li><a href="mailto:<?= e($cfg['email']) ?>">✉️ <?= e($cfg['email']) ?></a></li>
                    <li><a href="<?= e($cfg['etsy']) ?>" target="_blank" rel="noopener noreferrer">🛍️ Etsy Mağazam</a></li>
                    <li><a href="#iletisim">📩 Mesaj Gönder</a></li>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
            <p>© <?= date('Y') ?> <?= e($cfg['site_name']) ?>. Tüm hakları saklıdır.</p>
            <p>Sevgiyle tasarlandı 🧡 | <a href="/admin/login.php">Admin</a></p>
        </div>

    </div>
</footer>

<!-- ============================================================
     ÜRÜN DETAY MODAL
     ============================================================ -->
<div class="modal-overlay" id="productModal" role="dialog" aria-modal="true" aria-labelledby="modal-title" aria-hidden="true">
    <div class="modal">

        <button class="modal-close" id="modalClose" aria-label="Modalı kapat">✕</button>

        <!-- Görsel -->
        <div class="modal-img-col">
            <img id="modal-image" src="" alt="" loading="lazy">
            <div id="modal-img-placeholder" class="modal-img-placeholder" style="display:none;">
                <img src="/assets/images/placeholder.svg" alt="Görsel mevcut değil" style="width:60%;opacity:0.5;">
            </div>
        </div>

        <!-- İçerik -->
        <div class="modal-content-col">
            <p class="modal-cat" id="modal-cat"></p>
            <h2 class="modal-title" id="modal-title"></h2>
            <p class="modal-price" id="modal-price"></p>
            <hr class="modal-divider">
            <p class="modal-desc" id="modal-desc"></p>
            <div class="modal-tags" id="modal-tags"></div>
            <div class="modal-actions">
                <a id="modal-etsy-btn" href="#" class="btn btn-etsy" target="_blank" rel="noopener noreferrer">
                    🛍️ Etsy'de Satın Al
                </a>
                <button class="btn btn-outline" onclick="document.getElementById('productModal').classList.remove('open');document.body.style.overflow=''">
                    Kapat
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Scroll to top -->
<button class="scroll-top-btn" id="scrollTopBtn" aria-label="Sayfanın başına git">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
        <polyline points="18 15 12 9 6 15"/>
    </svg>
</button>

<!-- Toast bildirimi -->
<div class="toast" id="toast" role="alert" aria-live="assertive"></div>

<script src="/assets/js/main.js"></script>
</body>
</html>
