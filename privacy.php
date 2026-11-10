<?php
// privacy.php
require_once __DIR__ . '/includes/db.php';
$S = load_settings($pdo);

$pageTitle       = 'Privacy Policy — ' . ($S['site_title'] ?? 'ConverterTool.in');
$pageDescription = 'How ConverterTool.in handles your data: your files never leave your device, we do not store uploads, and we minimize what we collect. Full policy inside.';
$canonical       = SITE_URL . '/privacy.php';

$lastUpdated = date('Y-m-d', filemtime(__FILE__)); // ← update this string when you change the policy

$schema = [
    '@context' => 'https://schema.org',
    '@type'    => 'WebPage',
    'name'     => 'Privacy Policy',
    'url'      => $canonical,
    'description' => $pageDescription,
    'dateModified' => $lastUpdated,
    'publisher' => [
        '@type' => 'Organization',
        'name'  => $S['site_title'] ?? 'ConverterTool.in',
        'url'   => SITE_URL,
    ],
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
            <span aria-current="page">Privacy Policy</span>
        </nav>
        <h1>Privacy Policy</h1>
        <p class="page-lead">
            Your files never leave your device. This page explains exactly what
            we collect, what we don't, and your rights.
        </p>
        <p style="color:var(--color-muted);font-size:0.9rem;margin-top:0.5rem;">
            Last updated: <time datetime="<?= e($lastUpdated) ?>"><?= date('F j, Y', strtotime($lastUpdated)) ?></time>
        </p>
    </div>
</section>

<section class="section">
    <div class="container prose">

        <h2>1. The short version</h2>
        <ul>
            <li><strong>Your files never leave your device.</strong> All conversions run inside your browser. No file is uploaded to any server.</li>
            <li><strong>We do not store your files.</strong> No server-side storage, no logging of file contents, no retention.</li>
            <li><strong>We do not require an account.</strong> No email, password, or personal details needed to use any tool.</li>
            <li><strong>We collect minimal technical data.</strong> Standard web server logs (IP, user agent, timestamp) for security and reliability.</li>
            <li><strong>We do not sell your data.</strong> Ever.</li>
        </ul>
        <p>
            The longer version below explains each of these points in detail,
            including your rights under GDPR, CCPA, and similar regulations.
        </p>

        <h2>2. How the tools work — no uploads</h2>
        <p>
            Every tool on ConverterTool.in performs its conversion <strong>locally in
            your browser</strong>, using JavaScript libraries such as pdf-lib,
            SheetJS, mammoth.js, and MediaPipe. When you select a file:
        </p>
        <ul>
            <li>Your browser reads the file from your device using the <code>FileReader</code> API.</li>
            <li>The conversion is processed in your browser's memory.</li>
            <li>The output file is saved back to your device.</li>
        </ul>
        <p>
            At no point is your file transmitted over the network to our server
            or to any third party. You can verify this by opening your browser's
            <strong>Network</strong> tab (F12 → Network) while using any tool — you
            will see no upload requests.
        </p>

        <h2>3. What we collect</h2>

        <h3>Server access logs</h3>
        <p>
            Like almost every website, our web server automatically records
            technical information each time you visit a page. This typically includes:
        </p>
        <ul>
            <li>IP address (may be truncated or anonymized)</li>
            <li>Browser type and version (user agent)</li>
            <li>Operating system</li>
            <li>Referring page (if any)</li>
            <li>Date and time of access</li>
            <li>Requested URL and HTTP status code</li>
        </ul>
        <p>
            We use these logs <strong>only</strong> for security (detecting abuse),
            reliability (troubleshooting), and aggregate statistics. They are not
            used to build profiles, they are not shared with advertisers, and they
            are rotated and deleted automatically (typically within 30 days).
        </p>

        <h3>Cookies</h3>
        <p>
            We use <strong>one functional cookie</strong> — the PHP session cookie —
            which allows the admin panel to remember that you are logged in. This
            cookie is only set for administrators. Public visitors do not receive
            any cookie from ConverterTool.in.
        </p>
        <p>
            If we add analytics or third-party scripts in the future, this section
            will be updated to disclose them and their cookie behavior. You will
            see a notice before any such change takes effect.
        </p>

        <h3>Contact form</h3>
        <p>
            When you use our <a href="<?= BASE_PATH ?>contact.php">contact page</a>,
            your message is sent through <strong>your own email app</strong> (via a
            <code>mailto:</code> link) directly to
            <a href="mailto:kundan@convertertool.in">kundan@convertertool.in</a>.
            Nothing is transmitted to our server. We keep the email you send us
            only long enough to respond, then delete it once the conversation is closed.
        </p>

        <h3>What we do NOT collect</h3>
        <ul>
            <li>No file contents — your files never reach our server.</li>
            <li>No account information — we don't have accounts.</li>
            <li>No payment information — all tools are free.</li>
            <li>No tracking pixels or advertising IDs on public pages.</li>
            <li>No fingerprinting, no behavioral profiling.</li>
        </ul>

        <h2>4. Third-party services</h2>
        <p>
            Some tools load open-source JavaScript libraries from public CDNs
            (primarily <a href="https://www.jsdelivr.com/" target="_blank" rel="noopener">jsDelivr</a> and
            <a href="https://fonts.google.com/" target="_blank" rel="noopener">Google Fonts</a>).
            These CDNs may log the IP address of the browser that requests the files,
            which is standard for any resource served over the internet. They do not
            receive any information about your files or conversion activity.
        </p>
        <p>
            If you prefer to avoid CDN requests entirely, all tools will still function
            after the page has loaded once (the browser caches the libraries).
        </p>

        <h2>5. Legal basis for processing (GDPR)</h2>
        <p>
            If you are located in the European Economic Area, the United Kingdom, or
            Switzerland, our legal basis for processing the limited data we collect is:
        </p>
        <ul>
            <li><strong>Legitimate interest</strong> (Art. 6(1)(f) GDPR) — for
                server access logs used to prevent abuse and maintain security.</li>
            <li><strong>Consent</strong> (Art. 6(1)(a) GDPR) — for any email you
                choose to send us via the contact form.</li>
        </ul>
        <p>
            We do not process your personal data for marketing, profiling, or any
            purpose beyond what is described on this page.
        </p>

        <h2>6. Your rights</h2>
        <p>
            Depending on your jurisdiction (GDPR, CCPA, LGPD, PIPEDA, and similar
            laws), you may have the right to:
        </p>
        <ul>
            <li><strong>Access</strong> — request a copy of any personal data we hold about you.</li>
            <li><strong>Rectification</strong> — ask us to correct inaccurate data.</li>
            <li><strong>Erasure</strong> — ask us to delete your data (we usually have none to delete).</li>
            <li><strong>Restriction</strong> — ask us to limit processing.</li>
            <li><strong>Portability</strong> — receive your data in a machine-readable format.</li>
            <li><strong>Objection</strong> — object to processing based on legitimate interest.</li>
            <li><strong>Complaint</strong> — lodge a complaint with your local data protection authority.</li>
        </ul>
        <p>
            To exercise any of these rights, email
            <a href="mailto:kundan@convertertool.in">kundan@convertertool.in</a>.
            We respond to all requests within 30 days.
        </p>

        <h2>7. Children's privacy</h2>
        <p>
            ConverterTool.in is not directed at children under 13 (or under 16 in
            jurisdictions with a higher age of digital consent). We do not knowingly
            collect personal data from children. If you believe a child has sent us
            an email via the contact form, please let us know and we will delete it.
        </p>

        <h2>8. Data security</h2>
        <p>
            Our servers use HTTPS (TLS encryption) for all connections. Because we
            don't store user files or accounts, the impact of any server compromise
            is limited by design. Admin access is protected with hashed passwords
            and CSRF protection. However, no online service can be guaranteed 100%
            secure — please don't send passwords, financial details, or government ID
            numbers through the contact form.
        </p>

        <h2>9. International data transfers</h2>
        <p>
            Server access logs may be stored on servers located in the European
            Union, the United States, or other regions where our hosting provider
            operates. Where required by GDPR, transfers outside the EEA use Standard
            Contractual Clauses or equivalent safeguards.
        </p>

        <h2>10. Changes to this policy</h2>
        <p>
            We may update this Privacy Policy from time to time to reflect changes
            in our practices or the law. When we do, we update the "Last updated"
            date at the top of this page. If a change is significant (for example,
            if we ever begin collecting more data), we will display a notice on the
            homepage for at least 30 days.
        </p>

        <h2>11. Contact</h2>
        <p>
            Questions about this policy? Email us at
            <a href="mailto:kundan@convertertool.in">kundan@convertertool.in</a>,
            or use our <a href="<?= BASE_PATH ?>contact.php">contact form</a>.
        </p>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>