<?php
// how-it-works.php
require_once __DIR__ . '/includes/db.php';
$S = load_settings($pdo);

/* ── Page-specific SEO ─────────────────────────── */
$pageTitle       = 'How It Works — ' . ($S['site_title'] ?? 'ConverterTool.in');
$pageDescription = 'How browser-based file conversion works on ConverterTool.in — three simple steps, no uploads, no account, no server processing of your files.';
$canonical       = SITE_URL . '/how-it-works.php';

/* ── HowTo JSON-LD (great for featured snippets) ─ */
$howToSchema = [
    '@context' => 'https://schema.org',
    '@type'    => 'HowTo',
    'name'     => 'How to convert a file privately on ConverterTool.in',
    'description' => 'Convert PDF, Word, Excel, or image files entirely inside your browser without uploading them to a server.',
    'totalTime' => 'PT1M',
    'step' => [
        [
            '@type' => 'HowToStep',
            'position' => 1,
            'name' => 'Choose a tool',
            'text' => 'Browse the tools on the homepage and pick the converter you need — for example, Word to PDF or Image to JPG.',
        ],
        [
            '@type' => 'HowToStep',
            'position' => 2,
            'name' => 'Select your file',
            'text' => 'Drag your file into the drop zone or click to browse. The file is read directly from your device by the browser.',
        ],
        [
            '@type' => 'HowToStep',
            'position' => 3,
            'name' => 'Download the result',
            'text' => 'Your browser performs the conversion locally. The converted file downloads automatically to your device.',
        ],
    ],
];
$extraHead = '<script type="application/ld+json">'
           . json_encode($howToSchema, JSON_UNESCAPED_SLASHES)
           . '</script>';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= BASE_PATH ?>">Home</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">How It Works</span>
        </nav>
        <h1>How It Works</h1>
        <p class="page-lead">
            Three simple steps, and your file never leaves your device. Here is
            exactly what happens under the hood — and why it matters.
        </p>
    </div>
</section>

<!-- ═════════════ 3 Steps ═════════════ -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Three steps — no install, no upload</h2>
            <p>The whole process takes under a minute and works on any modern browser.</p>
        </div>

        <ol class="steps">
            <li class="step">
                <h3>Choose a tool</h3>
                <p>Pick the converter you need from the homepage — for example, Word to PDF or Image to JPG.</p>
            </li>
            <li class="step">
                <h3>Drop your file</h3>
                <p>Your browser reads the file directly from disk using the FileReader API — the same technology used by offline apps.</p>
            </li>
            <li class="step">
                <h3>Download the result</h3>
                <p>The conversion runs locally in your browser, then the finished file is saved straight back to your device.</p>
            </li>
        </ol>
    </div>
</section>

<!-- ═════════════ Under the hood ═════════════ -->
<section class="section section-alt">
    <div class="container prose">

        <h2>What actually happens under the hood</h2>
        <p>
            When you drop a file into one of our tools, three things happen — all
            inside your browser, all without a network request:
        </p>

        <h3>1. Your browser reads the file</h3>
        <p>
            Modern browsers ship with a <code>FileReader</code> API and an
            <code>ArrayBuffer</code> object that let JavaScript read a file's raw
            bytes without ever sending them anywhere. This is the same capability
            that lets web apps work offline — it is a first-class browser feature,
            not a hack.
        </p>

        <h3>2. A JavaScript library performs the conversion</h3>
        <p>
            Each tool loads a small, open-source conversion engine directly into
            the page. For Word to PDF, that engine parses the <code>.docx</code>
            XML and renders it as a PDF. For Excel to CSV, it reads the
            <code>.xlsx</code> spreadsheet cells and serialises them. For image
            conversions, the browser's built-in Canvas API does the pixel work.
            Every library runs on your device.
        </p>

        <h3>3. The result is saved to your device</h3>
        <p>
            When the engine finishes, the browser generates a download and saves
            the file wherever you tell it to — exactly like saving a document
            from a text editor. Nothing is transmitted, and the file you converted
            is never seen by any server.
        </p>

    </div>
</section>

<!-- ═════════════ Comparison ═════════════ -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Server-based vs. browser-based conversion</h2>
            <p>Same output, very different privacy guarantees.</p>
        </div>

        <div class="compare">
            <div class="compare-col">
                <h3>Typical online converter</h3>
                <ul class="compare-list">
                    <li><span class="compare-x" aria-hidden="true">✗</span> File is uploaded to a server</li>
                    <li><span class="compare-x" aria-hidden="true">✗</span> Stored on disk for minutes or hours</li>
                    <li><span class="compare-x" aria-hidden="true">✗</span> Possibly queued for processing</li>
                    <li><span class="compare-x" aria-hidden="true">✗</span> Requires trusting the site's privacy policy</li>
                    <li><span class="compare-x" aria-hidden="true">✗</span> Often capped by download speed</li>
                </ul>
            </div>
            <div class="compare-col compare-col-good">
                <h3>ConverterTool.in</h3>
                <ul class="compare-list">
                    <li><span class="compare-check" aria-hidden="true">✓</span> File never leaves your device</li>
                    <li><span class="compare-check" aria-hidden="true">✓</span> Nothing stored, nothing logged</li>
                    <li><span class="compare-check" aria-hidden="true">✓</span> Instant — no queue, no upload</li>
                    <li><span class="compare-check" aria-hidden="true">✓</span> Privacy by architecture, not by policy</li>
                    <li><span class="compare-check" aria-hidden="true">✓</span> Works offline after first visit</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ═════════════ Limitations ═════════════ -->
<section class="section section-alt">
    <div class="container prose">

        <h2>What are the trade-offs?</h2>
        <p>
            Browser-based conversion is excellent for privacy and speed, but it
            does have two honest limitations:
        </p>

        <h3>Very large files may be slower</h3>
        <p>
            Because your device does the work, a 200-page PDF will take longer to
            convert on a phone than on a server with dedicated CPUs. For everyday
            documents — contracts, spreadsheets, photos, invoices — this is
            unnoticeable. For very large documents, use a desktop browser with
            plenty of free memory.
        </p>

        <h3>Complex formatting may differ slightly</h3>
        <p>
            Some advanced document features — floating text boxes, unusual fonts,
            embedded scripts — may not translate perfectly. This is true of every
            converter, but it applies to server-based and browser-based equally.
            If a specific file fails, try re-saving it in the original app first.
        </p>

    </div>
</section>

<!-- ═════════════ CTA ═════════════ -->
<section class="section">
    <div class="container">
        <div class="faq-cta">
            <h2>Ready to try it?</h2>
            <p>Pick any tool on the homepage — no account, no upload, no cost.</p>
            <a href="<?= BASE_PATH ?>#tools" class="btn btn-primary">Explore free tools</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>