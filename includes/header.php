<?php
// includes/header.php
// Expects: $pdo, $S, $pageTitle, $pageDescription, $canonical
// Optional: $extraHead, $extraCssVars, $robots

$pageTitle       = $pageTitle       ?? (($S['site_title'] ?? 'ConverterTool.in'));
$pageDescription = $pageDescription ?? ($S['site_description'] ?? '');
$canonical       = $canonical       ?? (SITE_URL . $_SERVER['REQUEST_URI']);
$robots          = $robots          ?? 'index, follow, max-image-preview:large';
$extraHead       = $extraHead       ?? '';
$ogImage         = SITE_URL . '/assets/img/og-default.jpg';
$siteName        = $S['site_name']  ?? 'ConverterTool';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="robots" content="<?= e($robots) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta name="theme-color" content="<?= e($S['color_primary'] ?? '#F5B100') ?>">

    <?php if (!empty($S['meta_keywords'])): ?>
        <meta name="keywords" content="<?= e($S['meta_keywords']) ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($S['site_title'] ?? '') ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:locale" content="en_US">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">
    <?php if (!empty($S['twitter_handle'])): ?>
        <meta name="twitter:site" content="<?= e($S['twitter_handle']) ?>">
    <?php endif; ?>

    <!-- Performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <!-- Admin-controlled theme overrides -->
    <style>
        :root {
            --color-primary:      <?= e($S['color_primary'] ?? '#F5B100') ?>;
            --color-primary-dark: <?= e($S['color_primary_dark'] ?? '#D99A00') ?>;
            --color-accent:       <?= e($S['color_accent'] ?? '#1F1B16') ?>;
            --color-bg:           <?= e($S['color_bg'] ?? '#FFFCF3') ?>;
            --color-surface:      <?= e($S['color_surface'] ?? '#FFFFFF') ?>;
            --color-muted:        <?= e($S['color_muted'] ?? '#7A7267') ?>;
            --color-border:       <?= e($S['color_border'] ?? '#EDE3CC') ?>;
        }
    </style>

    <!-- THE one stylesheet -->
    <link rel="stylesheet" href="<?= BASE_PATH ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">

    <!-- PWA manifest -->
<link rel="manifest" href="<?= BASE_PATH ?>manifest.json">

<!-- Apple touch icon (iOS home screen) -->
<link rel="apple-touch-icon" href="<?= BASE_PATH ?>assets/img/icon-192.png">

<!-- Apple mobile web app meta -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="ConverterTool">

<!-- Mobile theme color (matches manifest) -->
<meta name="theme-color" content="<?= e($S['color_primary'] ?? '#2563EB') ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_PATH ?>assets/img/favicon.svg">
    <link rel="alternate icon" href="<?= BASE_PATH ?>assets/img/favicon.svg" sizes="any">

    <?= $extraHead /* caller-injected head content */ ?>
</head>
<body>

<a href="#main" class="skip-link">Skip to content</a>

<header class="site-header" role="banner">
    <div class="container header-inner">


        <a href="<?= BASE_PATH ?>" class="brand" aria-label="<?= e($S['site_title'] ?? 'Home') ?> home">
            <img
                src="<?= BASE_PATH ?>assets/img/icon-192.png"
                alt=""
                class="brand-logo"
                width="32"
                height="32"
                decoding="async"
            >
            <span><?= e($siteName) ?><span style="color:var(--color-primary)">.in</span></span>
        </a>

         <!-- ═══ NEW: PWA install + online status ═══ -->
        <div class="header-pwa">
            <button type="button"
                    id="pwaInstallBtn"
                    class="pwa-install-btn"
                    hidden
                    title="Install ConverterTool as an app on your device">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <span>Install app</span>
            </button>

            <button type="button"
                    id="pwaOnlineStatus"
                    class="pwa-status is-online"
                    title="Connection status — click to re-check">
                <span id="pwaOnlineDot" class="pwa-dot" aria-hidden="true"></span>
                <span id="pwaOnlineText" class="pwa-text">Online</span>
            </button>

            <button type="button"
                    id="pwaCheckBtn"
                    class="pwa-check-btn"
                    title="Re-check connection">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="23 4 23 10 17 10"/>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                </svg>
            </button>
        </div>

        <nav class="nav" aria-label="Primary">
            <a href="<?= BASE_PATH ?>tools.php"<?= nav_current('tools.php') ?>>All Tools</a>
            <a href="<?= BASE_PATH ?>how-it-works.php"<?= nav_current('how-it-works.php') ?>>How It Works</a>
            <a href="<?= BASE_PATH ?>about.php"<?= nav_current('about.php') ?>>About</a>
            <a href="<?= BASE_PATH ?>faq.php"<?= nav_current('faq.php') ?>>FAQ</a>
        </nav>

        <a href="<?= BASE_PATH ?>#tools" class="header-cta">Browse Tools</a>
    </div>
</header>

<main id="main"></main>