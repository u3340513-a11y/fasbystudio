<?php
// Dinamik XML Sitemap
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= htmlspecialchars(SITE_URL, ENT_XML1, 'UTF-8') ?>/</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
        <lastmod><?= date('Y-m-d') ?></lastmod>
    </url>
    <url>
        <loc><?= htmlspecialchars(SITE_URL, ENT_XML1, 'UTF-8') ?>/#urunler</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars(SITE_URL, ENT_XML1, 'UTF-8') ?>/#hakkimda</loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars(SITE_URL, ENT_XML1, 'UTF-8') ?>/#iletisim</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
</urlset>
