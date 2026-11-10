<?php
// tool.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/icons.php';
$S = load_settings($pdo);

$registry = require __DIR__ . '/includes/tools-meta.php';

/* ── Validate slug ────────────────────────────── */
$slug = isset($_GET['t']) ? preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['t'])) : '';
if ($slug === '' || !isset($registry[$slug])) {
    http_response_code(404);
    $pageTitle       = 'Tool not found — ' . ($S['site_title'] ?? 'ConverterTool.in');
    $pageDescription = 'The tool you are looking for does not exist. Browse all available tools instead.';
    $canonical       = SITE_URL . '/tools.php';
    $robots          = 'noindex, follow';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= BASE_PATH ?>">Home</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Not found</span>
            </nav>
            <h1>Tool not found</h1>
            <p class="page-lead">That tool does not exist. Browse all available tools below.</p>
        </div>
    </section>
    <section class="section">
        <div class="container">
            <div class="faq-cta">
                <h2>Browse all tools</h2>
                <p>Pick a converter from the full directory.</p>
                <a href="<?= BASE_PATH ?>tools.php" class="btn btn-primary">All tools →</a>
            </div>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$T = $registry[$slug];

/* ── DB overrides (admin-editable) ─────────────────────────────
   The admin panel can override these fields. When a DB value is
   non-empty, it wins over the registry default.
   ──────────────────────────────────────────────────────────── */
$dbRow = null;
try {
    $stmt = $pdo->prepare(
        "SELECT title, short_desc, seo_title, seo_description, is_active
           FROM tools WHERE slug = ? LIMIT 1"
    );
    $stmt->execute([$slug]);
    $dbRow = $stmt->fetch() ?: null;
} catch (PDOException $e) {
    error_log('tool.php DB override error: ' . $e->getMessage());
}

if ($dbRow) {
    // Hidden by admin? Respect it — 404 instead of showing.
    if (isset($dbRow['is_active']) && (int)$dbRow['is_active'] === 0) {
        http_response_code(404);
        $pageTitle       = 'Tool not available — ' . ($S['site_title'] ?? 'ConverterTool.in');
        $pageDescription = 'This tool is currently unavailable.';
        $canonical       = SITE_URL . '/tools.php';
        $robots          = 'noindex, follow';
        require __DIR__ . '/includes/header.php';
        ?>
        <section class="page-hero">
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?= BASE_PATH ?>">Home</a>
                    <span aria-hidden="true">/</span>
                    <a href="<?= BASE_PATH ?>tools.php">All Tools</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">Unavailable</span>
                </nav>
                <h1>This tool is currently unavailable</h1>
                <p class="page-lead">The tool exists but has been hidden by an administrator.</p>
            </div>
        </section>
        <section class="section">
            <div class="container">
                <div class="faq-cta">
                    <h2>Try another tool</h2>
                    <p>Browse the full directory to find what you need.</p>
                    <a href="<?= BASE_PATH ?>tools.php" class="btn btn-primary">All tools →</a>
                </div>
            </div>
        </section>
        <?php
        require __DIR__ . '/includes/footer.php';
        exit;
    }

    // Merge overrides — DB value wins if non-empty
    if (!empty($dbRow['title']))           $T['title']       = $dbRow['title'];
    if (!empty($dbRow['short_desc']))      $T['short']       = $dbRow['short_desc'];
    if (!empty($dbRow['seo_title']))       $T['seo_title']   = $dbRow['seo_title'];
    if (!empty($dbRow['seo_description'])) $T['description'] = $dbRow['seo_description'];
}

/* ── Page-specific SEO ────────────────────────── */
$pageTitle       = $T['seo_title'] . ' — ' . ($S['site_name'] ?? 'ConverterTool');
$pageDescription = $T['description'];
$canonical       = SITE_URL . '/tool.php?t=' . $slug;
$pageUrl         = $canonical;

/* ── JSON-LD ──────────────────────────────────── */
$schemaApp = [
    '@context'    => 'https://schema.org',
    '@type'       => 'SoftwareApplication',
    'name'        => $T['title'],
    'description' => $T['description'],
    'url'         => $canonical,
    'applicationCategory' => 'UtilityApplication',
    'operatingSystem'     => 'Any (Web)',
    'browserRequirements' => 'Requires a modern browser',
    'offers'      => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
    'isAccessibleForFree' => true,
    'featureList' => $T['short'],
    'publisher'   => [
        '@type' => 'Organization',
        'name'  => $S['site_title'] ?? 'ConverterTool.in',
        'url'   => SITE_URL,
    ],
];
$schemaFaq = [
    '@context'  => 'https://schema.org',
    '@type'     => 'FAQPage',
    'mainEntity'=> array_map(fn($f) => [
        '@type' => 'Question',
        'name'  => $f['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
    ], $T['faqs']),
];
$schemaBreadcrumb = [
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',      'item' => SITE_URL . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'All Tools', 'item' => SITE_URL . '/tools.php'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $T['title'], 'item' => $canonical],
    ],
];

$extraHead = '<script type="application/ld+json">' . json_encode($schemaApp,        JSON_UNESCAPED_SLASHES) . '</script>' . "\n"
           . '<script type="application/ld+json">' . json_encode($schemaFaq,        JSON_UNESCAPED_SLASHES) . '</script>' . "\n"
           . '<script type="application/ld+json">' . json_encode($schemaBreadcrumb, JSON_UNESCAPED_SLASHES) . '</script>';

/* ── Related tools ────────────────────────────── */
$related = [];
foreach ($T['related'] as $relSlug) {
    if (isset($registry[$relSlug])) {
        $related[] = array_merge($registry[$relSlug], ['slug' => $relSlug]);
    }
}

$iconLabels = ['doc'=>'DOC','pdf'=>'PDF','xls'=>'XLS','csv'=>'CSV','img'=>'IMG','file'=>'FILE'];
$accept     = $T['accept']      ?? '';

/* ── Library loading logic ────────────────────── */
$needsMammoth = in_array($slug, ['word-to-pdf'], true);
$needsXlsx    = in_array($slug, ['excel-to-csv', 'csv-to-excel'], true);
$needsPdfLib  = in_array($slug, ['image-to-pdf', 'merge-pdf', 'split-pdf', 'compress-pdf'], true);
$needsPdfJs   = in_array($slug, ['pdf-to-word', 'pdf-to-image'], true);
$needsDocx    = in_array($slug, ['pdf-to-word'], true);

require __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════ PAGE HERO ═══════════════ -->
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= BASE_PATH ?>">Home</a>
            <span aria-hidden="true">/</span>
            <a href="<?= BASE_PATH ?>tools.php">All Tools</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= e($T['title']) ?></span>
        </nav>
        <div class="tool-hero-icon"><?= tool_icon($slug, 72) ?></div>
<h1><?= e($T['h1']) ?></h1>
        <p class="page-lead"><?= e($T['intro']) ?></p>
    </div>
</section>

<!-- ═══════════════ TOOL INTERFACE ═══════════════ -->
<section class="section tool-workspace" data-tool="<?= e($slug) ?>">
    <div class="container">
        <div class="tool-workspace-inner">

            <div class="tool-panel">
                <div class="tool-panel-head">
                    <span class="tool-badge">
                        <span class="tool-badge-dot" aria-hidden="true"></span>
                        Local processing — no upload
                    </span>
                </div>

                <?php if ($slug === 'pdf-to-word'): ?>
                    <p class="tool-hint">
                        <strong>Heads up:</strong> this tool works on <em>text-based</em> PDFs.
                        Scanned PDFs and PDFs with outlined text contain no extractable text —
                        they will produce an empty document.
                    </p>
                <?php endif; ?>

                <input type="file"
                    id="fileInput"
                    class="sr-only"
                    accept="<?= e($accept) ?>"
                    multiple>

                <label class="drop-zone" id="dropZone" for="fileInput" tabindex="0">
                    <span class="drop-zone-icon" aria-hidden="true">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                    </span>
                    <span class="drop-zone-title">Choose files or drag &amp; drop</span>
                    <span class="drop-zone-hint">
                        Accepted: <code><?= e($accept) ?></code> · Multiple files allowed
                    </span>
                </label>

                <div id="fileList" class="file-list-wrap" hidden>
                    <div class="file-list-head">
                        <span class="file-list-count" id="fileListCount">0 files selected</span>
                        <button type="button" class="file-list-clear" id="clearAllBtn">
                            Clear all
                        </button>
                    </div>
                    <ul class="file-list" id="fileListUl"></ul>
                </div>

                <!-- Cross-promotion hints -->
                <?php if ($slug === 'background-remover'): ?>
                    <div class="tool-hint tool-hint-link">
                        <strong>💡 Just removing a person's background?</strong>
                        Try our <a href="<?= BASE_PATH ?>tool.php?t=portrait-background-remover">Portrait Background Remover</a>
                        — a <strong>2 MB</strong> model that loads instantly and works in under a second.
                    </div>
                <?php elseif ($slug === 'portrait-background-remover'): ?>
                    <div class="tool-hint tool-hint-link">
                        <strong>💡 Need to remove the background from a product, animal, or object?</strong>
                        Use the <a href="<?= BASE_PATH ?>tool.php?t=background-remover">General Background Remover</a>
                        instead — this tool is optimized for people only.
                    </div>
                <?php elseif ($slug === 'passport-photo'): ?>
                    <div class="tool-hint tool-hint-link">
                        <strong>💡 Need a transparent background instead?</strong>
                        Try the <a href="<?= BASE_PATH ?>tool.php?t=portrait-background-remover">Portrait Background Remover</a>
                        — same fast model, different output.
                    </div>
                <?php elseif ($slug === 'heic-to-jpg'): ?>
                    <div class="tool-hint tool-hint-link">
                        <strong>💡 Got a lot of HEIC photos?</strong>
                        After converting, use our <a href="<?= BASE_PATH ?>tool.php?t=image-compress">Image Compressor</a>
                        to shrink the JPGs before sharing, or
                        <a href="<?= BASE_PATH ?>tool.php?t=image-to-pdf">combine them into a PDF</a>.
                    </div>
                <?php endif; ?>

                <?php /* ── Tool-specific configuration ─────────────── */ ?>

                <?php if ($slug === 'image-compress'): ?>
                    <div class="tool-config">
                        <label for="qualityRange">Quality: <strong id="qualityVal">75%</strong></label>
                        <input type="range" id="qualityRange" min="10" max="100" value="75" step="5">
                    </div>

                <?php elseif ($slug === 'image-to-pdf'): ?>
                    <div class="tool-config">
                        <label for="pdfPageMode">Page size</label>
                        <select id="pdfPageMode">
                            <option value="format" selected>Fit each image to a page format (recommended)</option>
                            <option value="native">Native size — each image as-is</option>
                        </select>
                    </div>

                    <div class="tool-config" id="pdfFormatGroup">
                        <label for="pdfPaperSize">Page format</label>
                        <select id="pdfPaperSize">
                            <option value="A4" selected>A4 — 210 × 297 mm</option>
                            <option value="A3">A3 — 297 × 420 mm</option>
                            <option value="A5">A5 — 148 × 210 mm</option>
                            <option value="A2">A2 — 420 × 594 mm</option>
                            <option value="A1">A1 — 594 × 841 mm</option>
                            <option value="A0">A0 — 841 × 1189 mm</option>
                            <option value="A6">A6 — 105 × 148 mm</option>
                            <option value="LETTER">US Letter — 8.5 × 11 in</option>
                            <option value="LEGAL">US Legal — 8.5 × 14 in</option>
                            <option value="TABLOID">US Tabloid — 11 × 17 in</option>
                            <option value="B5">B5 — 176 × 250 mm</option>
                        </select>
                    </div>

                    <div class="tool-config tool-config-row" id="pdfOrientationGroup">
                        <label>
                            Orientation
                            <select id="pdfOrientation">
                                <option value="auto" selected>Auto (match image)</option>
                                <option value="portrait">Portrait</option>
                                <option value="landscape">Landscape</option>
                            </select>
                        </label>
                        <label>
                            Margin
                            <select id="pdfMargin">
                                <option value="0">None</option>
                                <option value="24" selected>Medium (8.5 mm)</option>
                                <option value="48">Large (17 mm)</option>
                            </select>
                        </label>
                    </div>

                <?php elseif ($slug === 'image-resize'): ?>
                    <div class="tool-config tool-config-row">
                        <label>
                            Width (px)
                            <input type="number" id="resizeWidth" min="16" max="8000" value="1200">
                        </label>
                        <label>
                            Height (px)
                            <input type="number" id="resizeHeight" min="16" max="8000" value="0" placeholder="auto">
                        </label>
                    </div>

                <?php elseif ($slug === 'split-pdf'): ?>
                    <div class="tool-config tool-config-row">
                        <label>
                            From page
                            <input type="number" id="splitStart" min="1" value="1">
                        </label>
                        <label>
                            To page
                            <input type="number" id="splitEnd" min="1" value="1">
                        </label>
                    </div>

                <?php elseif ($slug === 'passport-photo'): ?>
                    <div class="tool-config">
                        <label for="passportCountry">Passport format</label>
                        <select id="passportCountry">
                            <option value="us">United States — 2×2 in</option>
                            <option value="uk">United Kingdom — 35×45 mm</option>
                            <option value="eu">EU / Schengen — 35×45 mm</option>
                            <option value="in">India — 35×45 mm</option>
                            <option value="ca">Canada — 50×70 mm</option>
                            <option value="au">Australia — 35×45 mm</option>
                        </select>
                    </div>

                    <div class="tool-config tool-config-row">
                        <label>
                            Background Color
                            <div style="display:flex; gap:10px; margin-top:5px; align-items:center;">
                                <input type="color" id="passportBgColor" value="#FFFFFF" style="width:44px; height:44px; padding:2px; cursor:pointer; border:1px solid var(--color-border); border-radius:var(--radius-sm);">
                                <span id="bgColorHint" style="font-size:0.85rem; color:var(--color-muted);">Default is white for official use</span>
                            </div>
                        </label>
                    </div>

                    <div class="tool-config" style="display:flex; flex-direction:column; gap:12px; margin-top: 1rem;">
                        <div style="display:flex; gap:12px; align-items:center;">
                            <input type="checkbox" id="passportKeepBg" style="width:18px; height:18px; cursor:pointer;">
                            <label for="passportKeepBg" style="margin:0; font-weight:400; cursor:pointer;">
                                Keep original background (skip AI removal)
                            </label>
                        </div>
                        <div style="display:flex; gap:12px; align-items:center;">
                            <input type="checkbox" id="passportPrintSheet" checked style="width:18px; height:18px; cursor:pointer;">
                            <label for="passportPrintSheet" style="margin:0; font-weight:400; cursor:pointer;">
                                Generate print sheet
                            </label>
                        </div>
                    </div>

                    <div class="tool-config" id="printSheetConfig" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--color-border);">
                        <label for="passportPaperSize">Print sheet paper size</label>
                        <select id="passportPaperSize">
                            <option value="4x6">4×6 inch (Standard photo print)</option>
                            <option value="a4">A4 (210 × 297 mm)</option>
                            <option value="letter">US Letter (8.5 × 11 in)</option>
                            <option value="5x7">5×7 inch (Large photo print)</option>
                        </select>
                    </div>

                    <div id="passportPreviewWrap" class="tool-config" hidden style="background: var(--color-bg); border: 1px solid var(--color-border); padding: 1rem; border-radius: var(--radius);">
                        <p style="margin: 0 0 0.75rem 0; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-muted);">Live Preview</p>
                        <div style="display: flex; flex-direction: column; align-items: center;">
                            <div id="passportPreviewContainer" style="box-shadow: var(--shadow); background: #fff; line-height: 0;"></div>
                            <p id="passportPreviewMsg" style="margin-top: 1rem; font-size: 0.85rem; color: var(--color-muted); text-align: center;"></p>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="button" id="convertBtn" class="btn btn-primary btn-block" disabled>
                    Convert
                </button>

                <div id="status" class="tool-status" role="status" aria-live="polite"></div>
            </div>

            <aside class="tool-sidebar">
                <div class="sidebar-card">
                    <h3>Privacy</h3>
                    <p>
                        This tool runs <strong>entirely in your browser</strong>. Your files
                        are never uploaded — there is no server that receives them.
                    </p>
                </div>
                <div class="sidebar-card">
                    <h3>Need help?</h3>
                    <p>
                        See the <a href="#tool-faq">FAQ</a> below, or
                        <a href="<?= BASE_PATH ?>contact.php">contact us</a>
                        if something is not working.
                    </p>
                </div>
            </aside>

        </div>
    </div>
</section>

<!-- ═══════════════ HOW TO USE ═══════════════ -->
<section class="section" id="how-to">
    <div class="container">
        <div class="section-head">
            <h2>How to use this tool</h2>
            <p>Three steps. Nothing uploaded, nothing stored.</p>
        </div>
        <ol class="steps">
            <?php foreach ($T['howto'] as $stepText): ?>
                <li class="step">
                    <h3><?= e($T['title']) ?></h3>
                    <p><?= e($stepText) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<!-- ═══════════════ FAQ ═══════════════ -->
<section class="section section-alt" id="tool-faq">
    <div class="container">
        <div class="section-head">
            <h2>Frequently asked questions</h2>
            <p>About <?= e(strtolower($T['title'])) ?> and how it handles your files.</p>
        </div>
        <div class="faq">
            <?php foreach ($T['faqs'] as $f): ?>
                <details>
                    <summary><?= e($f['q']) ?></summary>
                    <p><?= e($f['a']) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════ RELATED TOOLS ═══════════════ -->
<?php if (!empty($related)): ?>
<section class="section" id="related">
    <div class="container">
        <div class="section-head">
            <h2>Related tools</h2>
            <p>Other converters that pair well with <?= e(strtolower($T['title'])) ?>.</p>
        </div>
        <div class="tool-grid">
            <?php foreach ($related as $R): ?>
                <a class="tool-card" href="<?= BASE_PATH ?>tool.php?t=<?= e($R['slug']) ?>">
                    <span class="tool-icon"><?= tool_icon($R['slug']) ?></span>
                    <h3><?= e($R['title']) ?></h3>
                    <p><?= e($R['short']) ?></p>
                    <span class="tool-arrow">Open tool</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════ TOOL CONFIG (runs after DOM is parsed) ═══════════════ -->
<script>
    // Image → PDF: hide/show format options based on "Page size" mode
    (function () {
        const mode = document.getElementById('pdfPageMode');
        const fmtGroup = document.getElementById('pdfFormatGroup');
        const oriGroup = document.getElementById('pdfOrientationGroup');
        if (!mode || !fmtGroup || !oriGroup) return;

        function update() {
            const hide = mode.value === 'native';
            fmtGroup.style.display = hide ? 'none' : '';
            oriGroup.style.display = hide ? 'none' : '';
        }
        mode.addEventListener('change', update);
        update();
    })();
</script>

<!-- ═══════════════ TOOL ENGINE CONFIG ═══════════════ -->
<script>
    window.CONVERTER_TOOL = {
        slug:      <?= json_encode($slug) ?>,
        accept:    <?= json_encode($accept) ?>,
        outputExt: <?= json_encode($T['output_ext'] ?? '') ?>,
        multiple:  true
    };
</script>

<!-- 1. External libraries (loaded only when the current tool needs them) -->
<?php if ($needsMammoth): ?>
    <script src="https://cdn.jsdelivr.net/npm/mammoth@1.8.0/mammoth.browser.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js" defer></script>
<?php endif; ?>

<?php if ($needsXlsx): ?>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js" defer></script>
<?php endif; ?>

<?php if ($needsPdfLib): ?>
    <script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js" defer></script>
<?php endif; ?>

<?php if ($needsPdfJs): ?>
    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js" defer></script>
<?php endif; ?>

<?php if ($needsDocx): ?>
    <script src="https://cdn.jsdelivr.net/npm/docx@8.5.0/build/index.umd.js" defer></script>
<?php endif; ?>

<!-- 2. Tool engine (defines window.runConversion) -->
<script src="<?= BASE_PATH ?>assets/js/tools/<?= e($T['js']) ?>" defer></script>

<!-- 3. Shared UI controller (must be LAST — wires UI to runConversion) -->
<script src="<?= BASE_PATH ?>assets/js/tools/_shared.js" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>