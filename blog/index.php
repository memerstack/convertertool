<?php
// blog/index.php — blog listing
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/icons.php';
$S = load_settings($pdo);

$posts = $pdo->query(
    "SELECT id, slug, title, excerpt, content, cover_image, tags, published_at
       FROM blog_posts
      WHERE is_published = 1
        AND (published_at IS NULL OR published_at <= NOW())
      ORDER BY published_at DESC, id DESC"
)->fetchAll();

$pageTitle       = 'Blog — ' . ($S['site_title'] ?? 'ConverterTool.in');
$pageDescription = 'Guides, tips, and tutorials about file conversion, privacy, and document formats — from the team behind ConverterTool.in.';
$canonical       = SITE_URL . '/blog/';

$schemaBlog = [
    '@context' => 'https://schema.org',
    '@type'    => 'Blog',
    'name'     => 'ConverterTool.in Blog',
    'url'      => $canonical,
    'description' => $pageDescription,
    'publisher' => [
        '@type' => 'Organization',
        'name'  => $S['site_title'] ?? 'ConverterTool.in',
        'url'   => SITE_URL,
    ],
    'blogPost' => array_map(fn($p) => [
        '@type' => 'BlogPosting',
        'headline' => $p['title'],
        'url'      => SITE_URL . '/blog/post.php?slug=' . urlencode($p['slug']),
        'datePublished' => $p['published_at'] ? date('c', strtotime($p['published_at'])) : null,
    ], array_slice($posts, 0, 10)),
];
$extraHead = '<script type="application/ld+json">'
           . json_encode($schemaBlog, JSON_UNESCAPED_SLASHES)
           . '</script>';

function split_tags(?string $s): array {
    if (!$s) return [];
    return array_filter(array_map('trim', explode(',', $s)));
}

require __DIR__ . '/../includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= BASE_PATH ?>">Home</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Blog</span>
        </nav>
        <h1>The ConverterTool Blog</h1>
        <p class="page-lead">
            Guides, format explainers, and privacy tips — written by the people
            who build ConverterTool.in.
        </p>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php if (empty($posts)): ?>
            <div class="blog-empty">
                <h2>No posts yet</h2>
                <p>Check back soon — we are writing guides about file conversion, privacy, and document formats.</p>
            </div>
        <?php else: ?>

            <div class="blog-grid">
                <?php foreach ($posts as $p): ?>
                    <a class="blog-card" href="<?= BASE_PATH ?>blog/post.php?slug=<?= urlencode($p['slug']) ?>">
                        <?php if (!empty($p['cover_image'])): ?>
                            <div class="blog-card-cover">
                                <img src="<?= BASE_PATH ?>assets/img/blog/<?= e($p['cover_image']) ?>"
                                     alt=""
                                     loading="lazy"
                                     decoding="async">
                            </div>
                        <?php else: ?>
                            <div class="blog-card-cover blog-card-cover-placeholder">
                                <span><?= strtoupper(mb_substr($p['title'], 0, 1)) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="blog-card-body">
                            <?php if ($p['published_at']): ?>
                                <time class="blog-card-date"
                                      datetime="<?= e(date('Y-m-d', strtotime($p['published_at']))) ?>">
                                    <?= e(date('M j, Y', strtotime($p['published_at']))) ?>
                                </time>
                            <?php endif; ?>
                            <h2 class="blog-card-title"><?= e($p['title']) ?></h2>
                            <p class="blog-card-excerpt"><?= e($p['excerpt'] ?: mb_substr(strip_tags($p['content'] ?? ''), 0, 160)) ?></p>

                            <?php $tags = split_tags($p['tags'] ?? ''); if (!empty($tags)): ?>
                                <div class="blog-card-tags">
                                    <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                                        <span class="blog-tag"><?= e($tag) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>