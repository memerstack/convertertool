<?php
// contact.php — mailto: based form (no server-side email)
require_once __DIR__ . '/includes/db.php';

$S = load_settings($pdo);

/* ── Page-specific SEO ────────────────────────── */
$pageTitle       = 'Contact — ' . ($S['site_title'] ?? 'ConverterTool.in');
$pageDescription = 'Get in touch with ConverterTool.in. Report a bug, request a new tool, or ask a question — we reply within a couple of days.';
$canonical       = SITE_URL . '/contact.php';

/* ── ContactPage JSON-LD ──────────────────────── */
$schemaContact = [
    '@context' => 'https://schema.org',
    '@type'    => 'ContactPage',
    'name'     => 'Contact ConverterTool.in',
    'url'      => $canonical,
    'description' => $pageDescription,
    'mainEntity' => [
        '@type' => 'Organization',
        'name'  => $S['site_title'] ?? 'ConverterTool.in',
        'url'   => SITE_URL,
        'email' => 'kundan@convertertool.in',
    ],
];
$extraHead = '<script type="application/ld+json">'
           . json_encode($schemaContact, JSON_UNESCAPED_SLASHES)
           . '</script>';

/* ── Prefill from query params (e.g. tool=word-to-pdf) ── */
$subject = isset($_GET['subject']) ? trim($_GET['subject']) : '';
$body    = '';

if (isset($_GET['tool'])) {
    $toolName = trim($_GET['tool']);
    $subject  = $subject !== '' ? $subject : ('Issue with ' . $toolName);
    $body     = "Tool: {$toolName}\n\n"
              . "What happened:\n\n\n"
              . "Steps to reproduce:\n\n\n"
              . "Browser/device:\n";
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= BASE_PATH ?>">Home</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Contact</span>
        </nav>
        <h1>Contact Us</h1>
        <p class="page-lead">
            Found a bug, want a new tool, or just want to say hi? Fill in the
            form below and we'll reply within a couple of days.
        </p>
    </div>
</section>

<section class="section">
    <div class="container prose">

        <div class="contact-info contact-info-top">
            <h2>How this works</h2>
            <p style="margin:0;">
                When you click <strong>Send message</strong>, your email app
                (Gmail, Outlook, Apple Mail, or the default app on your phone)
                will open with the message already written and addressed to
                <a href="mailto:kundan@convertertool.in">kundan@convertertool.in</a>.
                Just press <strong>Send</strong> in that app to deliver it.
            </p>
            <p style="margin:0.75rem 0 0;">
                Nothing is sent to any server until you press Send in your
                email app — so your message stays entirely between you and us.
            </p>
        </div>

        <form class="contact-form" id="contactForm" novalidate>

            <div class="contact-row">
                <label class="contact-field">
                    <span class="contact-label">Your name</span>
                    <input type="text"
                           id="contactName"
                           class="contact-input"
                           maxlength="100"
                           autocomplete="name"
                           placeholder="Jane Doe">
                </label>

                <label class="contact-field">
                    <span class="contact-label">Your email (optional)</span>
                    <input type="email"
                           id="contactEmail"
                           class="contact-input"
                           maxlength="200"
                           autocomplete="email"
                           placeholder="you@example.com">
                    <span class="contact-help">Included in the message body so we can reply.</span>
                </label>
            </div>

            <label class="contact-field">
                <span class="contact-label">Subject</span>
                <input type="text"
                       id="contactSubject"
                       class="contact-input"
                       maxlength="150"
                       value="<?= e($subject) ?>"
                       placeholder="e.g. Bug report, Feature request, General question">
            </label>

            <label class="contact-field">
                <span class="contact-label">Message</span>
                <textarea id="contactMessage"
                          class="contact-textarea"
                          rows="8"
                          maxlength="5000"
                          placeholder="Tell us as much as you can — the more detail, the faster we can help."><?= e($body) ?></textarea>
                <span class="contact-help">
                    Max 5000 characters. Please don't share passwords or private documents.
                </span>
            </label>

            <div class="contact-actions">
                <button type="submit" class="btn btn-primary" id="contactSubmit">
                    Open email app &amp; send
                </button>
                <button type="button" class="btn btn-ghost" id="contactCopyBtn">
                    Or copy email address
                </button>
            </div>

            <p class="contact-help" style="margin-top:1rem;" id="contactStatus" aria-live="polite"></p>
        </form>

        <div class="contact-info">
            <h2>Other ways to reach us</h2>
            <ul>
                <li><strong>Email:</strong> <a href="mailto:kundan@convertertool.in">kundan@convertertool.in</a></li>
                <li><strong>Response time:</strong> Usually within 48 hours (Mon–Fri)</li>
                <li><strong>For bugs:</strong> Please mention the tool name (e.g. "Image to PDF") and what happened</li>
                <li><strong>Please don't:</strong> Send files — we never need them. Describe the problem instead</li>
            </ul>
        </div>

    </div>
</section>

<script>
(function () {
    'use strict';

    const TO_EMAIL = 'kundan@convertertool.in';
    const form     = document.getElementById('contactForm');
    const nameEl   = document.getElementById('contactName');
    const emailEl  = document.getElementById('contactEmail');
    const subjEl   = document.getElementById('contactSubject');
    const msgEl    = document.getElementById('contactMessage');
    const status   = document.getElementById('contactStatus');
    const copyBtn  = document.getElementById('contactCopyBtn');

    function setStatus(text, kind) {
        status.textContent = text;
        status.style.color = kind === 'error' ? '#DC2626'
                          : kind === 'success' ? '#16A34A'
                          : '';
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();

        const name    = nameEl.value.trim();
        const email   = emailEl.value.trim();
        const subject = subjEl.value.trim();
        const message = msgEl.value.trim();

        // Basic validation
        if (subject === '') {
            setStatus('Please add a subject before sending.', 'error');
            subjEl.focus();
            return;
        }
        if (message === '') {
            setStatus('Please write a message before sending.', 'error');
            msgEl.focus();
            return;
        }

        // Compose the email body
        const bodyLines = [];
        if (name)  bodyLines.push('Name: ' + name);
        if (email) bodyLines.push('Email: ' + email);
        if (name || email) bodyLines.push('');
        bodyLines.push(message);

        const bodyText = bodyLines.join('\n');

        // Build the mailto: URL with proper encoding
        const mailtoUrl = 'mailto:' + encodeURIComponent(TO_EMAIL)
                        + '?subject=' + encodeURIComponent(subject)
                        + '&body='    + encodeURIComponent(bodyText);

        // If it's too long, some clients truncate. Warn the user.
        if (mailtoUrl.length > 1800) {
            setStatus(
                'Your message is quite long — some email apps may cut it off. '
                + 'You can copy the email address and send it manually for the best result.',
                'error'
            );
        } else {
            setStatus('Opening your email app…', 'success');
        }

        // Try to open the mail client
        window.location.href = mailtoUrl;
    });

    // Copy email address fallback
    copyBtn.addEventListener('click', async function () {
        try {
            await navigator.clipboard.writeText(TO_EMAIL);
            setStatus('Email address copied — paste it into any email app.', 'success');
        } catch (err) {
            // Older browsers / insecure contexts
            const ta = document.createElement('textarea');
            ta.value = TO_EMAIL;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                setStatus('Email address copied — paste it into any email app.', 'success');
            } catch (e2) {
                setStatus('Could not copy. Email us at ' + TO_EMAIL, 'error');
            }
            document.body.removeChild(ta);
        }
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>