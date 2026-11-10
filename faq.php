<?php
// faq.php
require_once __DIR__ . '/includes/db.php';
$S = load_settings($pdo);

/* ── Page-specific SEO ─────────────────────────── */
$pageTitle       = 'FAQ — ' . ($S['site_title'] ?? 'ConverterTool.in');
$pageDescription = 'Answers to common questions about ConverterTool.in — how the tools work, whether files are uploaded, which formats are supported, and more.';
$canonical       = SITE_URL . '/faq.php';

/* ── Grouped FAQ content ───────────────────────── */
$faqGroups = [
    'Privacy & Security' => [
        [
            'q' => 'Are my files uploaded to a server?',
            'a' => 'No. Every conversion runs entirely inside your browser using modern JavaScript. Your file is read from your device, processed locally, and the output is saved back to your device. Nothing is transmitted to any server.',
        ],
        [
            'q' => 'Do you store my files anywhere?',
            'a' => 'No. There is no file upload endpoint on this site, and no server-side file storage. When you close the tab, the file is gone from memory — we never had a copy.',
        ],
        [
            'q' => 'Do you log what I convert?',
            'a' => 'We do not log file contents, file names, or conversion types. Like any website, basic server access logs may record the page you visited — but never what you converted.',
        ],
        [
            'q' => 'Is it safe for confidential documents?',
            'a' => 'Yes. Since files never leave your device, the same privacy you have working on your own computer applies here. This is the same reason browser-based converters are preferred over upload-based ones for contracts, invoices, medical records, and financial documents.',
        ],
    ],
    'Using the Tools' => [
        [
            'q' => 'Is ConverterTool.in really free?',
            'a' => 'Yes. All tools are 100% free, with no signup, no watermarks, and no per-file limits imposed by us. The only limit is the memory of your browser and device.',
        ],
        [
            'q' => 'Do I need to create an account?',
            'a' => 'No. There is no account system. Open a tool, drop your file, download the result.',
        ],
        [
            'q' => 'Does it work on mobile and tablets?',
            'a' => 'Yes. The site is fully responsive and works in any modern mobile or tablet browser. No app install needed. Every conversion runs on the same in-browser engine.',
        ],
        [
            'q' => 'Which file formats are supported?',
            'a' => 'Currently: Word (.docx), Excel (.xlsx), CSV, PDF, and the most common image formats (JPG, PNG, WEBP). We add new formats regularly.',
        ],
        [
            'q' => 'Can I batch-convert multiple files at once?',
            'a' => 'Yes, where the tool supports it. If a tool accepts multiple files, its drop zone will say so. Batch conversion is processed one file at a time — all still locally.',
        ],
    ],
    'Troubleshooting' => [
        [
            'q' => 'Why is my conversion slow?',
            'a' => 'Large files or complex PDFs can take longer in the browser than on a server because your device is doing the work directly. For very large documents, close other heavy tabs to free up memory.',
        ],
        [
            'q' => 'The output looks different from my original. What happened?',
            'a' => 'Some formatting features — like complex headers, footnotes, or unusual fonts — may render slightly differently depending on the file type. Every browser-based converter has minor differences like this. If a specific file fails, try re-saving it in the original app first.',
        ],
        [
            'q' => 'Can I use ConverterTool.in offline?',
            'a' => 'After your first visit, the app itself is cached in your browser, so most tools continue to work with no internet connection. Only the conversion engine needs to load once.',
        ],
        [
            'q' => 'A tool is not working. How do I report it?',
            'a' => 'Use the contact page and describe the tool and file type. Please do not attach the file — since we do not accept uploads, we only need to know what went wrong.',
        ],
    ],
];

/* ── FAQPage JSON-LD for Google rich results ───── */
$schemaEntities = [];
foreach ($faqGroups as $items) {
    foreach ($items as $item) {
        $schemaEntities[] = [
            '@type' => 'Question',
            'name'  => $item['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
        ];
    }
}
$schemaFaq = [
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => $schemaEntities,
];
$extraHead = '<script type="application/ld+json">'
           . json_encode($schemaFaq, JSON_UNESCAPED_SLASHES)
           . '</script>';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= BASE_PATH ?>">Home</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">FAQ</span>
        </nav>
        <h1>Frequently Asked Questions</h1>
        <p class="page-lead">
            Everything you need to know about how ConverterTool.in handles
            privacy, which formats are supported, and how to get the best
            results from each tool.
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="faq-groups">

            <?php foreach ($faqGroups as $groupTitle => $items): ?>
                <div class="faq-group">
                    <h2 class="faq-group-title"><?= e($groupTitle) ?></h2>
                    <div class="faq">
                        <?php foreach ($items as $item): ?>
                            <details>
                                <summary><?= e($item['q']) ?></summary>
                                <p><?= e($item['a']) ?></p>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

        <div class="faq-cta">
            <h2>Still have a question?</h2>
            <p>We read every message and reply within a couple of days.</p>
            <a href="<?= BASE_PATH ?>contact.php" class="btn btn-primary">Contact us</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>