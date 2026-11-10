<?php
// about.php
require_once __DIR__ . '/includes/db.php';
$S = load_settings($pdo);

/* ── Page-specific SEO ─────────────────────────── */
$pageTitle       = 'About — ' . ($S['site_title'] ?? 'ConverterTool.in');
$pageDescription = 'Learn about ConverterTool.in — a privacy-first, browser-based file converter. No uploads, no account, no data stored on any server.';
$canonical       = SITE_URL . '/about.php';

/* ── AboutPage structured data ─────────────────── */
$schema = [
    '@context' => 'https://schema.org',
    '@type'    => 'AboutPage',
    'name'     => 'About ConverterTool.in',
    'url'      => $canonical,
    'description' => $pageDescription,
];
$extraHead = '<script type="application/ld+json">'
           . json_encode($schema, JSON_UNESCAPED_SLASHES)
           . '</script>';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= BASE_PATH ?>">Home</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">About</span>
        </nav>
        <h1>About ConverterTool.in</h1>
        <p class="page-lead">
            A file converter that respects your privacy — because your files
            should never have to leave your device to be useful.
        </p>
    </div>
</section>

<section class="section">
    <div class="container prose">

        <h2>Why we built this</h2>
        <p>
            Most online converters work the same way: you upload a file to a
            server, wait for it to be processed, and download the result. That
            model has two problems. First, your documents — often containing
            personal, financial, or business information — sit on someone else's
            server for minutes, hours, or indefinitely. Second, the site needs
            to store what you uploaded, which means it needs to disclose how,
            where, and how long.
        </p>
        <p>
            We took a different route. Modern browsers can do almost everything a
            server can do — including converting documents, reading PDFs,
            manipulating images, and extracting spreadsheets. So we removed the
            server from the equation entirely. Every tool on ConverterTool.in runs
            <strong>inside your browser</strong>. Your file is read from your
            device, processed locally, and the output is saved straight back to
            your device. Nothing is transmitted, stored, or logged.
        </p>

        <h2>How it works, in one sentence</h2>
        <p>
            Your file never leaves your device — we just run the conversion math
            inside your browser instead of on a server.
        </p>

        <h2>What you get</h2>
        <ul>
            <li><strong>Privacy by design.</strong> No uploads, no server storage, no logging of file contents.</li>
            <li><strong>Speed.</strong> Local conversions start instantly — no round-trip to a server.</li>
            <li><strong>No account needed.</strong> No email, no password, no "sign up to continue".</li>
            <li><strong>Free, no watermarks.</strong> The output is yours — clean, complete, unmarked.</li>
            <li><strong>Works everywhere.</strong> Desktop, tablet, and mobile browsers, no app install.</li>
        </ul>

        <h2>What we do not do</h2>
        <ul>
            <li>We do not upload your files.</li>
            <li>We do not store your files on any server.</li>
            <li>We do not ask for an account or email.</li>
            <li>We do not watermark or alter the output beyond the conversion you asked for.</li>
            <li>We do not track what you convert.</li>
        </ul>

        <h2>Get in touch</h2>
        <p>
            Found a bug, or want a tool we do not offer yet? We would love to
            hear from you. Reach out via the <a href="<?= BASE_PATH ?>contact.php">contact page</a>.
        </p>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>