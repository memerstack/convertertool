<?php
// terms.php
require_once __DIR__ . '/includes/db.php';
$S = load_settings($pdo);

$pageTitle       = 'Terms of Service — ' . ($S['site_title'] ?? 'ConverterTool.in');
$pageDescription = 'The terms that govern your use of ConverterTool.in. Free browser-based tools, provided as-is, no warranty, no data collection. Read the full terms.';
$canonical       = SITE_URL . '/terms.php';

$lastUpdated = date('Y-m-d', filemtime(__FILE__)); // ← update this string when you change the terms

$schema = [
    '@context' => 'https://schema.org',
    '@type'    => 'WebPage',
    'name'     => 'Terms of Service',
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
            <span aria-current="page">Terms of Service</span>
        </nav>
        <h1>Terms of Service</h1>
        <p class="page-lead">
            Clear, plain-English terms for using ConverterTool.in. Short version:
            use it freely, don't abuse it, and understand the limitations.
        </p>
        <p style="color:var(--color-muted);font-size:0.9rem;margin-top:0.5rem;">
            Last updated: <time datetime="<?= e($lastUpdated) ?>"><?= date('F j, Y', strtotime($lastUpdated)) ?></time>
        </p>
    </div>
</section>

<section class="section">
    <div class="container prose legal-prose">

        <h2>1. Acceptance of terms</h2>
        <p>
            By using ConverterTool.in (the "Service"), you agree to these Terms of
            Service. If you do not agree, please do not use the Service. These terms
            apply to every visitor, whether or not you use any specific tool.
        </p>

        <h2>2. What the Service is</h2>
        <p>
            ConverterTool.in provides free, browser-based file conversion tools.
            All conversions run entirely on your device — no file is uploaded to
            our servers. The Service is provided free of charge, without registration,
            and without any file storage on our side.
        </p>

        <h2>3. Permitted use</h2>
        <p>You may use the Service for:</p>
        <ul>
            <li>Personal file conversions of any kind.</li>
            <li>Commercial use of the converted output — files you convert are yours to use freely.</li>
            <li>Educational, research, and non-profit purposes.</li>
            <li>Automated access via the browser (e.g., opening the site on many devices) as long as it does not degrade service for others.</li>
        </ul>

        <h2>4. Prohibited use</h2>
        <p>You agree <strong>not</strong> to:</p>
        <ul>
            <li>Attempt to reverse-engineer, decompile, or otherwise extract source code from the Service beyond what is publicly available in the browser.</li>
            <li>Scrape, crawl, or otherwise automatically access the Service in a way that burdens our servers (standard search-engine indexing is fine).</li>
            <li>Use the Service for any illegal purpose, including but not limited to processing illegal content, infringing copyrighted works, or violating any applicable law.</li>
            <li>Attempt to gain unauthorized access to our servers, admin panel, or any other system.</li>
            <li>Interfere with the Service's operation or availability for other users.</li>
            <li>Use the Service to process content that depicts minors in a sexual manner, malware, or any material that violates applicable law.</li>
        </ul>

        <h2>5. No warranty — provided "as is"</h2>
        <p>
            The Service is provided <strong>"as is"</strong> and <strong>"as available"</strong>,
            without warranty of any kind, express or implied, including but not limited
            to warranties of merchantability, fitness for a particular purpose,
            non-infringement, or accuracy.
        </p>
        <p>
            We make no guarantee that:
        </p>
        <ul>
            <li>Conversions will be error-free or produce output identical to what the original file contained.</li>
            <li>The Service will be available continuously or without interruption.</li>
            <li>Any specific file format, size, or edge case will work.</li>
        </ul>
        <p>
            <strong>Critical note:</strong> If your file is important (a legal contract,
            a medical record, a signed document), always keep a backup of the
            original and verify the output carefully before relying on it.
        </p>

        <h2>6. Limitation of liability</h2>
        <p>
            To the maximum extent permitted by law, ConverterTool.in and its operators
            shall not be liable for any indirect, incidental, special, consequential,
            or punitive damages, or any loss of data, profits, or business opportunity,
            arising out of or related to your use of the Service — even if we have
            been advised of the possibility of such damages.
        </p>
        <p>
            Because conversions run on your device, any defects in output are
            attributable to the browser, the underlying libraries, or the input file
            itself — not to any server we control.
        </p>

        <h2>7. Intellectual property</h2>
        <p>
            The Service's design, code (other than open-source components), branding,
            and content are owned by ConverterTool.in. You may not copy, modify, or
            redistribute them without our written permission.
        </p>
        <p>
            Files you process through the Service remain <strong>entirely yours</strong>.
            We do not claim any ownership, license, or rights over your input files
            or the converted output.
        </p>
        <p>
            The Service is built on open-source libraries (pdf-lib, mammoth.js,
            SheetJS, MediaPipe, PHPMailer, and others) — each of which is used
            under its respective license. Those licenses remain in force.
        </p>

        <h2>8. Privacy</h2>
        <p>
            Your use of the Service is also governed by our
            <a href="<?= BASE_PATH ?>privacy.php">Privacy Policy</a>, which explains
            what (little) data we collect and how we handle it.
        </p>

        <h2>9. Third-party links and services</h2>
        <p>
            The Service may link to third-party websites or load open-source
            libraries from CDNs. We are not responsible for the content, terms, or
            privacy practices of any third-party site or service. Accessing them is
            at your own risk.
        </p>

        <h2>10. Modifications to the Service</h2>
        <p>
            We may add, modify, or remove tools and features at any time without
            notice. We may also suspend the Service temporarily for maintenance or
            permanently, at our sole discretion. We will try to give reasonable
            notice of any significant permanent change.
        </p>

        <h2>11. Modifications to these Terms</h2>
        <p>
            We may update these Terms from time to time. When we do, we update the
            "Last updated" date at the top of this page. Continued use of the Service
            after any change constitutes acceptance of the new Terms.
        </p>

        <h2>12. Governing law</h2>
        <p>
            These Terms are governed by the laws of India, without regard to its
            conflict-of-law principles. Any dispute arising from or related to these
            Terms or the Service will be subject to the exclusive jurisdiction of the
            courts of India.
        </p>

        <h2>13. Severability</h2>
        <p>
            If any provision of these Terms is found to be unenforceable, the
            remaining provisions will remain in full force and effect.
        </p>

        <h2>14. Contact</h2>
        <p>
            Questions about these Terms? Email us at
            <a href="mailto:kundan@convertertool.in">kundan@convertertool.in</a>,
            or use our <a href="<?= BASE_PATH ?>contact.php">contact form</a>.
        </p>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>