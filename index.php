<?php
// index.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/icons.php';

$S = load_settings($pdo);

// Fetch tools
$tools = $pdo->query(
    "SELECT slug, title, short_desc, category, icon
       FROM tools WHERE is_active = 1
      ORDER BY sort_order ASC, title ASC"
)->fetchAll();

$byCategory = [];
foreach ($tools as $t) {
    $byCategory[$t['category']][] = $t;
}

/* ── Page-specific SEO ─────────────────────────────── */
$pageTitle       = ($S['site_title'] ?? 'ConverterTool.in') . ' — ' . ($S['site_tagline'] ?? 'Free Online File Converters');
$pageDescription = $S['site_description'] ?? 'Free, private, browser-based file converters.';
$canonical       = SITE_URL . '/';

/* ── Homepage-specific JSON-LD (WebSite, Org, FAQ) ─── */
$schemaWebSite = [
    '@context' => 'https://schema.org',
    '@type'    => 'WebSite',
    'name'     => $S['site_title'] ?? 'ConverterTool.in',
    'url'      => SITE_URL,
    'potentialAction' => [
        '@type'       => 'SearchAction',
        'target'      => SITE_URL . '/search?q={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
];
$schemaOrg = [
    '@context' => 'https://schema.org',
    '@type'    => 'Organization',
    'name'     => $S['site_title'] ?? 'ConverterTool.in',
    'url'      => SITE_URL,
    'logo'     => SITE_URL . '/assets/img/logo.png',
];

$faqs = [
    ['q' => 'Are my files uploaded to a server?',
     'a' => 'No. Every conversion runs entirely inside your browser. Your files never leave your device.'],
    ['q' => 'Is ConverterTool.in really free?',
     'a' => 'Yes. All tools are 100% free with no signup, no watermarks.'],
    ['q' => 'Does it work on mobile and tablets?',
     'a' => 'Absolutely. The site is fully responsive and works in any modern mobile browser.'],
    ['q' => 'Which formats are supported?',
     'a' => 'Word (.docx), Excel (.xlsx), CSV, PDF, and JPG/PNG/WEBP images.'],
    ['q' => 'Can I use it offline?',
     'a' => 'After the first visit, most tools keep working offline because the app is cached in your browser.'],
];
$schemaFaq = [
    '@context'  => 'https://schema.org',
    '@type'     => 'FAQPage',
    'mainEntity'=> array_map(fn($f) => [
        '@type' => 'Question',
        'name'  => $f['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
    ], $faqs),
];

$extraHead = '<script type="application/ld+json">' . json_encode($schemaWebSite, JSON_UNESCAPED_SLASHES) . '</script>' . "\n"
           . '<script type="application/ld+json">' . json_encode($schemaOrg,     JSON_UNESCAPED_SLASHES) . '</script>' . "\n"
           . '<script type="application/ld+json">' . json_encode($schemaFaq,     JSON_UNESCAPED_SLASHES) . '</script>';

$iconLabels = ['doc'=>'DOC','pdf'=>'PDF','xls'=>'XLS','csv'=>'CSV','img'=>'IMG','file'=>'FILE'];

require __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════════ HERO ═══════════════════ -->
<section class="hero" aria-labelledby="hero-title">
    <div class="container hero-inner">
        <div>
            <span class="hero-eyebrow">100% Browser-Based · No Uploads</span>
            <h1 id="hero-title">
                Convert files privately —<br>
                <span style="color:var(--color-primary-dark)">right in your browser.</span>
            </h1>
            <p class="hero-lead">
                Free tools to convert PDF, Word, Excel, and images. Everything runs on your device —
                your files never leave your computer, phone, or tablet.
            </p>
            <div class="hero-actions">
                <a href="<?= BASE_PATH ?>tools.php" class="btn btn-primary">
                    Explore Free Tools <span aria-hidden="true">→</span>
                </a>
                <a href="#how-it-works" class="btn btn-ghost">How It Works</a>
            </div>
            <ul class="hero-trust" aria-label="Key benefits">
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> No sign-up required</li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Files stay on your device</li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Works on mobile & desktop</li>
            </ul>
        </div>

        <aside class="hero-visual" aria-label="Example conversions">
            <h3>Popular conversions</h3>
            <div class="file-row">
                <span class="file-icon" aria-hidden="true">DOC</span>
                <div><div class="file-name">report.docx</div><div class="file-meta">to PDF · 0.9 MB</div></div>
                <span class="file-arrow" aria-hidden="true">→</span>
            </div>
            <div class="file-row">
                <span class="file-icon" aria-hidden="true">XLS</span>
                <div><div class="file-name">sales.xlsx</div><div class="file-meta">to CSV · 2.1 MB</div></div>
                <span class="file-arrow" aria-hidden="true">→</span>
            </div>
            <div class="file-row">
                <span class="file-icon" aria-hidden="true">IMG</span>
                <div><div class="file-name">photo.png</div><div class="file-meta">to JPG · 3.4 MB</div></div>
                <span class="file-arrow" aria-hidden="true">→</span>
            </div>
        </aside>
    </div>
</section>

<!-- ═══════════════════ TOOLS ═══════════════════ -->
<section class="section" id="tools" aria-labelledby="tools-title">
    <div class="container">
        <div class="section-head">
            <h2 id="tools-title">All Free Tools</h2>
            <p>Pick a converter, drop your file, and download the result. No install, no upload.</p>
        </div>

        <?php foreach ($byCategory as $category => $list): ?>
            <h3 class="sr-only"><?= e($category) ?> tools</h3>
            <div class="tool-grid">
                <?php foreach ($list as $t): ?>
                    <a class="tool-card" href="<?= BASE_PATH ?>tool.php?t=<?= e($t['slug']) ?>">
                        <span class="tool-icon"><?= tool_icon($t['slug']) ?></span>
                        <h3><?= e($t['title']) ?></h3>
                        <p><?= e($t['short_desc']) ?></p>
                        <span class="tool-arrow">Open tool</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <p style="text-align:center;margin-top:var(--space-5);">
            <a href="<?= BASE_PATH ?>tools.php" class="btn btn-ghost">View all tools →</a>
        </p>
    </div>
</section>

<!-- ═══════════════════ HOW IT WORKS ═══════════════════ -->
<section class="section" id="how-it-works" aria-labelledby="how-title">
    <div class="container">
        <div class="section-head">
            <h2 id="how-title">How It Works</h2>
            <p>Three simple steps. Nothing to install, nothing uploaded.</p>
        </div>
        <ol class="steps">
            <li class="step"><h3>Choose a tool</h3><p>Pick from PDF, Word, Excel, image, or spreadsheet converters above.</p></li>
            <li class="step"><h3>Drop your file</h3><p>Your file is read directly by the browser — no server involved.</p></li>
            <li class="step"><h3>Download the result</h3><p>The converted file is created locally and saved straight to your device.</p></li>
        </ol>
    </div>
</section>

<!-- ═══════════════════ PRIVACY ═══════════════════ -->
<section class="section" id="privacy" aria-labelledby="privacy-title">
    <div class="container">
        <div class="privacy-banner">
            <div>
                <h2 id="privacy-title">Your files never leave your device.</h2>
                <p>Unlike other converters, ConverterTool.in does not upload your documents anywhere. All processing happens locally — no server storage, no retention, no leaks.</p>
            </div>
            <span class="privacy-badge">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
                Local processing
            </span>
        </div>
    </div>
</section>

<!-- ═══════════════════ FAQ ═══════════════════ -->
<section class="section" id="faq" aria-labelledby="faq-title">
    <div class="container">
        <div class="section-head">
            <h2 id="faq-title">Frequently Asked Questions</h2>
            <p>Everything you need to know about how the tools work.</p>
        </div>
        <div class="faq">
            <?php foreach ($faqs as $f): ?>
                <details>
                    <summary><?= e($f['q']) ?></summary>
                    <p><?= e($f['a']) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>