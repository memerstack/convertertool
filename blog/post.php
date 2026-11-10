<?php
// blog/post.php — single blog post
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/icons.php';
$S = load_settings($pdo);

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

/* ── Load the post ───────────────────────────────── */
$stmt = $pdo->prepare(
    "SELECT * FROM blog_posts
      WHERE slug = ? AND is_published = 1
        AND (published_at IS NULL OR published_at <= NOW())
      LIMIT 1"
);
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $pageTitle       = 'Post not found — ' . ($S['site_title'] ?? 'ConverterTool.in');
    $pageDescription = 'This blog post does not exist.';
    $canonical       = SITE_URL . '/blog/';
    $robots          = 'noindex, follow';
    require __DIR__ . '/../includes/header.php';
    ?>
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= BASE_PATH ?>">Home</a>
                <span aria-hidden="true">/</span>
                <a href="<?= BASE_PATH ?>blog/">Blog</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Not found</span>
            </nav>
            <h1>Post not found</h1>
            <p class="page-lead">That post doesn't exist or has been removed.</p>
            <a href="<?= BASE_PATH ?>blog/" class="btn btn-primary">Back to blog</a>
        </div>
    </section>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

/* ── SEO ─────────────────────────────────────────── */
$pageTitle       = ($post['seo_title'] ?: $post['title']) . ' — ' . ($S['site_name'] ?? 'ConverterTool');
$pageDescription = $post['seo_description'] ?: ($post['excerpt'] ?: mb_substr(strip_tags($post['content']), 0, 155));
$canonical       = SITE_URL . '/blog/post.php?slug=' . urlencode($post['slug']);

/* ── Resolve related tools ───────────────────────── */
$relatedTools = [];
if (!empty($post['related_tools'])) {
    $registry = require __DIR__ . '/../includes/tools-meta.php';
    $slugs = array_filter(array_map('trim', explode(',', $post['related_tools'])));
    foreach ($slugs as $relSlug) {
        if (isset($registry[$relSlug])) {
            $relatedTools[] = array_merge($registry[$relSlug], ['slug' => $relSlug]);
        }
    }
}

/* ── Cover image: filename → full URL ────────────── */
// The DB stores just the filename (e.g. "cover-20260115-143022-a1b2c3d4.jpg").
// Build the display URL and JSON-LD URL from the standard blog image folder.
$coverUrl = null;
$coverFile = null;
if (!empty($post['cover_image'])) {
    $coverFile = $post['cover_image'];
    $coverUrl  = SITE_URL . '/assets/img/blog/' . $coverFile;
}

/* ── JSON-LD BlogPosting ─────────────────────────── */
$schemaPost = [
    '@context'      => 'https://schema.org',
    '@type'         => 'BlogPosting',
    'headline'      => $post['title'],
    'description'   => $pageDescription,
    'url'           => $canonical,
    'datePublished' => $post['published_at'] ? date('c', strtotime($post['published_at'])) : null,
    'dateModified'  => date('c', strtotime($post['updated_at'] ?: ($post['published_at'] ?: 'now'))),
    'author' => [
        '@type' => 'Organization',
        'name'  => $S['site_title'] ?? 'ConverterTool.in',
        'url'   => SITE_URL,
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name'  => $S['site_title'] ?? 'ConverterTool.in',
        'url'   => SITE_URL,
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id'   => $canonical,
    ],
];
if ($coverUrl) {
    $schemaPost['image'] = $coverUrl;
}
$schemaBreadcrumb = [
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => SITE_URL . '/blog/'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $canonical],
    ],
];
$extraHead = '<script type="application/ld+json">' . json_encode($schemaPost, JSON_UNESCAPED_SLASHES) . '</script>' . "\n"
           . '<script type="application/ld+json">' . json_encode($schemaBreadcrumb, JSON_UNESCAPED_SLASHES) . '</script>';

require __DIR__ . '/../includes/header.php';
?>

<article class="blog-article">
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= BASE_PATH ?>">Home</a>
                <span aria-hidden="true">/</span>
                <a href="<?= BASE_PATH ?>blog/">Blog</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page"><?= e($post['title']) ?></span>
            </nav>
            <h1><?= e($post['title']) ?></h1>
            <?php if ($post['published_at']): ?>
                <p style="color:var(--color-muted);font-size:0.9rem;margin-top:0.5rem;">
                    Published on <time datetime="<?= e(date('Y-m-d', strtotime($post['published_at']))) ?>">
                        <?= e(date('F j, Y', strtotime($post['published_at']))) ?>
                    </time>
                </p>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($coverUrl): ?>
        <div class="container" style="margin-top:var(--space-4);">
            <img src="<?= e($coverUrl) ?>"
                 alt=""
                 style="width:100%;max-width:900px;margin:0 auto;display:block;border-radius:var(--radius);box-shadow:var(--shadow);">
        </div>
    <?php endif; ?>

    <section class="section">
        <div class="container">
            <div class="prose blog-prose">
                <?= $post['content'] /* trusted HTML from admin only */ ?>
            </div>

            <?php if (!empty($relatedTools)): ?>
                <div class="blog-related-tools">
                    <h2>Try the tools mentioned above</h2>
                    <div class="tool-grid">
                        <?php foreach ($relatedTools as $R): ?>
                            <a class="tool-card" href="<?= BASE_PATH ?>tool.php?t=<?= e($R['slug']) ?>">
                                <span class="tool-icon"><?= tool_icon($R['slug']) ?></span>
                                <h3><?= e($R['title']) ?></h3>
                                <p><?= e($R['short']) ?></p>
                                <span class="tool-arrow">Open tool</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="blog-back">
                <a href="<?= BASE_PATH ?>blog/" class="btn btn-ghost">← Back to all posts</a>
            </div>
        </div>
    </section>
</article>

<?php require __DIR__ . '/../includes/footer.php'; ?>