<?php
// sitemap.php — dynamic sitemap.xml generator
// Reads live data from the DB so newly added tools and blog posts
// appear automatically. Served as /sitemap.xml via .htaccess rewrite.

require_once __DIR__ . '/includes/db.php';

/* ═══════════════════════════════════════════════════════════
   1. Configuration
   ═══════════════════════════════════════════════════════════ */

$staticPages = [
    ['url' => '/',                 'priority' => '1.0', 'changefreq' => 'weekly'],
    ['url' => '/tools.php',        'priority' => '0.9', 'changefreq' => 'weekly'],
    ['url' => '/blog/',            'priority' => '0.7', 'changefreq' => 'weekly'],
    ['url' => '/about.php',        'priority' => '0.5', 'changefreq' => 'monthly'],
    ['url' => '/faq.php',          'priority' => '0.6', 'changefreq' => 'monthly'],
    ['url' => '/how-it-works.php', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['url' => '/contact.php',      'priority' => '0.4', 'changefreq' => 'yearly'],
    ['url' => '/privacy.php',      'priority' => '0.3', 'changefreq' => 'yearly'],
    ['url' => '/terms.php',        'priority' => '0.3', 'changefreq' => 'yearly'],
];

/* ═══════════════════════════════════════════════════════════
   2. Fetch all active tools from the DB
   ═══════════════════════════════════════════════════════════ */

$tools = [];
try {
    $stmt = $pdo->query(
        "SELECT slug, title, is_active
           FROM tools
          WHERE is_active = 1
          ORDER BY sort_order ASC, title ASC"
    );
    $tools = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('sitemap.php DB error: ' . $e->getMessage());
    $registry = require __DIR__ . '/includes/tools-meta.php';
    foreach (array_keys($registry) as $slug) {
        $tools[] = ['slug' => $slug, 'title' => $registry[$slug]['title'] ?? $slug, 'is_active' => 1];
    }
}

/* ═══════════════════════════════════════════════════════════
   3. Determine per-URL last-modified date for tools
   ═══════════════════════════════════════════════════════════ */

$toolLastMod = [];
try {
    $rows = $pdo->query(
        "SELECT slug, GREATEST(
                    COALESCE(updated_at, '2000-01-01'),
                    COALESCE((SELECT MAX(updated_at) FROM site_content
                              WHERE block_key = CONCAT('tool_', tools.slug)), '2000-01-01')
                 ) AS last_mod
           FROM tools
          WHERE is_active = 1"
    )->fetchAll();
    foreach ($rows as $r) {
        $toolLastMod[$r['slug']] = $r['last_mod'];
    }
} catch (PDOException $e) {
    error_log('sitemap.php lastmod error: ' . $e->getMessage());
}

$siteLastMod = date('Y-m-d');

/* ═══════════════════════════════════════════════════════════
   4. Output the XML
   ═══════════════════════════════════════════════════════════ */

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' . "\n";
echo '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
echo '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
echo '                            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

/* ── Static pages ────────────────────────────────────────── */
foreach ($staticPages as $p) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars(SITE_URL . $p['url'], ENT_XML1, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>" . $siteLastMod . "</lastmod>\n";
    echo "    <changefreq>" . $p['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $p['priority'] . "</priority>\n";
    echo "  </url>\n";
}

/* ── Tool pages ──────────────────────────────────────────── */
foreach ($tools as $t) {
    $url = SITE_URL . '/tool.php?t=' . $t['slug'];

    $lastmod = $toolLastMod[$t['slug']] ?? $siteLastMod;
    if ($lastmod && $lastmod !== '2000-01-01') {
        $lastmod = date('Y-m-d', strtotime($lastmod));
    } else {
        $lastmod = $siteLastMod;
    }

    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}

/* ── Blog posts ──────────────────────────────────────────── */
$blogPosts = [];
try {
    $blogPosts = $pdo->query(
        "SELECT slug, published_at, updated_at, cover_image
           FROM blog_posts
          WHERE is_published = 1
            AND (published_at IS NULL OR published_at <= NOW())
          ORDER BY published_at DESC
          LIMIT 500"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('sitemap blog query error: ' . $e->getMessage());
}

// Individual blog posts
if (!empty($blogPosts)) {
    foreach ($blogPosts as $bp) {
        // Prefer updated_at, fall back to published_at, then to today
        $rawDate = $bp['updated_at'] ?: ($bp['published_at'] ?: date('Y-m-d H:i:s'));
        $lastmod = date('Y-m-d', strtotime($rawDate));
        $url     = SITE_URL . '/blog/post.php?slug=' . urlencode($bp['slug']);

        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";
        echo "    <lastmod>" . $lastmod . "</lastmod>\n";
        echo "    <changefreq>monthly</changefreq>\n";
        echo "    <priority>0.6</priority>\n";

        // Image sitemap extension — only if the post has a cover image
        // DB stores just the filename (e.g. "cover-20260115-143022-a1b2c3d4.jpg"),
        // so we must prefix the folder path.
        if (!empty($bp['cover_image'])) {
            $imgUrl = SITE_URL . '/assets/img/blog/' . $bp['cover_image'];
            echo "    <image:image>\n";
            echo "      <image:loc>" . htmlspecialchars($imgUrl, ENT_XML1, 'UTF-8') . "</image:loc>\n";
            echo "    </image:image>\n";
        }

        echo "  </url>\n";
    }
}

echo '</urlset>' . "\n";