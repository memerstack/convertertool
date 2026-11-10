<?php
// tools.php — full tools directory
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/icons.php';
$S = load_settings($pdo);

/* ── Fetch all active tools ───────────────────────── */
$tools = $pdo->query(
    "SELECT slug, title, short_desc, category, icon
       FROM tools WHERE is_active = 1
      ORDER BY category ASC, sort_order ASC, title ASC"
)->fetchAll();

/* ── Group by category (preserving order) ─────────── */
$byCategory = [];
foreach ($tools as $t) {
    $byCategory[$t['category']][] = $t;
}

/* ── Counts for the filter bar ────────────────────── */
$total = count($tools);
$catCounts = [];
foreach ($byCategory as $cat => $list) {
    $catCounts[$cat] = count($list);
}

/* ── Page-specific SEO ────────────────────────────── */
$pageTitle       = 'All Free Tools — ' . ($S['site_title'] ?? 'ConverterTool.in');
$pageDescription = 'Browse all ' . $total . ' free browser-based file converters — PDF, Word, Excel, CSV, and images. No uploads, no signup, 100% private.';
$canonical       = SITE_URL . '/tools.php';

/* ── CollectionPage + ItemList JSON-LD ────────────── */
$schemaItems = [];
$position = 1;
foreach ($tools as $t) {
    $schemaItems[] = [
        '@type'    => 'ListItem',
        'position' => $position++,
        'url'      => SITE_URL . '/tool.php?t=' . $t['slug'],
        'name'     => $t['title'],
    ];
}
$schema = [
    '@context'        => 'https://schema.org',
    '@type'           => 'CollectionPage',
    'name'            => 'All Free Tools',
    'url'             => $canonical,
    'description'     => $pageDescription,
    'mainEntity'      => [
        '@type'           => 'ItemList',
        'numberOfItems'   => $total,
        'itemListElement' => $schemaItems,
    ],
];
$extraHead = '<script type="application/ld+json">'
           . json_encode($schema, JSON_UNESCAPED_SLASHES)
           . '</script>';

$iconLabels = ['doc'=>'DOC','pdf'=>'PDF','xls'=>'XLS','csv'=>'CSV','img'=>'IMG','file'=>'FILE'];

require __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════ PAGE HERO ═══════════════ -->
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= BASE_PATH ?>">Home</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">All Tools</span>
        </nav>
        <h1>All Free Tools</h1>
        <p class="page-lead">
            <?= $total ?> browser-based converters that run entirely on your device.
            No uploads, no account, no watermarks — pick a tool and get started.
        </p>
    </div>
</section>

<!-- ═══════════════ FILTER BAR ═══════════════ -->
<section class="tools-toolbar">
    <div class="container tools-toolbar-inner">
        <div class="tools-search">
            <label for="toolSearch" class="sr-only">Search tools</label>
            <svg class="tools-search-icon" width="18" height="18" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/>
                <path d="m20 20-3.5-3.5"/>
            </svg>
            <input type="search"
                   id="toolSearch"
                   placeholder="Search tools — e.g. 'pdf', 'image', 'word'…"
                   autocomplete="off"
                   aria-describedby="toolSearchHint">
            <span id="toolSearchHint" class="sr-only">
                Results filter as you type.
            </span>
        </div>

        <div class="tools-filters" role="group" aria-label="Filter by category">
            <button type="button" class="filter-chip is-active" data-category="all">
                All <span class="filter-count"><?= $total ?></span>
            </button>
            <?php foreach ($catCounts as $cat => $count): ?>
                <button type="button" class="filter-chip" data-category="<?= e($cat) ?>">
                    <?= e($cat) ?> <span class="filter-count"><?= $count ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════ TOOL LIST ═══════════════ -->
<section class="section" aria-labelledby="tools-list-title">
    <div class="container">
        <h2 id="tools-list-title" class="sr-only">Tool directory</h2>

        <div id="toolDirectory">
            <?php if (empty($byCategory)): ?>
                <p class="tools-empty">No tools available yet.</p>
            <?php else: ?>
                <?php foreach ($byCategory as $category => $list): ?>
                    <div class="tool-category"
                         data-category="<?= e($category) ?>">
                        <div class="tool-category-head">
                            <h2 class="tool-category-title"><?= e($category) ?> Tools</h2>
                            <span class="tool-category-count"><?= count($list) ?></span>
                        </div>

                        <div class="tool-grid">
                            <?php foreach ($list as $t): ?>
                                <a class="tool-card"
                                   href="<?= BASE_PATH ?>tool.php?t=<?= e($t['slug']) ?>"
                                   data-tool-title="<?= e(strtolower($t['title'])) ?>"
                                   data-tool-desc="<?= e(strtolower($t['short_desc'])) ?>"
                                   data-tool-slug="<?= e(strtolower($t['slug'])) ?>">
                                    <span class="tool-icon"><?= tool_icon($t['slug']) ?></span>
                                    <h3><?= e($t['title']) ?></h3>
                                    <p><?= e($t['short_desc']) ?></p>
                                    <span class="tool-arrow">Open tool</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <p id="toolEmptyState" class="tools-empty" hidden>
            No tools match your search. Try a different keyword or category.
        </p>
    </div>
</section>

<!-- ═══════════════ CTA ═══════════════ -->
<section class="section">
    <div class="container">
        <div class="faq-cta">
            <h2>Cannot find the tool you need?</h2>
            <p>Tell us what conversion you would like to see and we will consider adding it.</p>
            <a href="<?= BASE_PATH ?>contact.php" class="btn btn-primary">Request a tool</a>
        </div>
    </div>
</section>

<!-- ═══════════════ FILTER SCRIPT ═══════════════ -->
<script>
(function () {
    'use strict';

    const searchInput = document.getElementById('toolSearch');
    const chips       = Array.from(document.querySelectorAll('.filter-chip'));
    const categories  = Array.from(document.querySelectorAll('.tool-category'));
    const cards       = Array.from(document.querySelectorAll('.tool-card'));
    const emptyState  = document.getElementById('toolEmptyState');

    let activeCategory = 'all';
    let activeQuery    = '';

    function normalise(str) {
        return (str || '').toLowerCase().trim();
    }

    function applyFilters() {
        const query = normalise(activeQuery);
        let visibleTotal = 0;

        categories.forEach(cat => {
            const catName      = cat.getAttribute('data-category');
            const categoryOk   = (activeCategory === 'all' || catName === activeCategory);
            const catCards     = Array.from(cat.querySelectorAll('.tool-card'));
            let catVisible     = 0;

            catCards.forEach(card => {
                const title = card.getAttribute('data-tool-title') || '';
                const desc  = card.getAttribute('data-tool-desc')  || '';
                const slug  = card.getAttribute('data-tool-slug')  || '';
                const haystack = title + ' ' + desc + ' ' + slug;
                const matchesQuery = !query || haystack.indexOf(query) !== -1;

                const visible = categoryOk && matchesQuery;
                card.hidden = !visible;
                if (visible) catVisible++;
            });

            cat.hidden = (catVisible === 0);
            visibleTotal += catVisible;
        });

        emptyState.hidden = (visibleTotal !== 0);
    }

    // Category chip clicks
    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            chips.forEach(c => c.classList.remove('is-active'));
            chip.classList.add('is-active');
            activeCategory = chip.getAttribute('data-category');
            applyFilters();
        });
    });

    // Search input (debounced)
    let debounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            activeQuery = searchInput.value;
            applyFilters();
        }, 120);
    });

    // Re-apply if browser restores search on bfcache
    window.addEventListener('pageshow', () => {
        if (searchInput.value) {
            activeQuery = searchInput.value;
            applyFilters();
        }
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>