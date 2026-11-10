<?php
// admin/includes/header.php
// Requires: $pageTitle (string)
if (!function_exists('e')) {
    require_once __DIR__ . '/auth.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle ?? 'Admin') ?> — <?= e($S['site_name'] ?? 'ConverterTool') ?> Admin</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>assets/css/style.css">
    <style>
        /* ── Admin-only layout (scoped, doesn't affect public site) ── */
        body { background: #F1F5F9; }
        .admin-shell { display: flex; min-height: 100vh; }

        .admin-sidebar {
            width: 240px;
            background: #0F172A;
            color: #94A3B8;
            padding: 1.5rem 1rem;
            flex-shrink: 0;
        }
        .admin-sidebar .brand {
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 2rem;
            display: block;
            text-decoration: none;
        }
        .admin-sidebar .brand span { color: #2563EB; }
        .admin-sidebar nav a {
            display: block;
            padding: 0.55rem 0.75rem;
            border-radius: 6px;
            color: #94A3B8;
            font-size: 0.9rem;
            text-decoration: none;
            margin-bottom: 0.25rem;
        }
        .admin-sidebar nav a:hover,
        .admin-sidebar nav a.is-active { background: #1E293B; color: #fff; }

        .admin-main {
            flex: 1;
            padding: 2rem;
            min-width: 0;
        }
        .admin-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #E2E8F0;
        }
        .admin-topbar h1 {
            font-size: 1.4rem;
            margin: 0;
        }
        .admin-card {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .admin-card h2 {
            font-size: 1.1rem;
            margin: 0 0 1rem;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 1.25rem;
        }
        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0F172A;
        }
        .stat-card .stat-label {
            color: #64748B;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .admin-table th {
            text-align: left;
            padding: 0.6rem 0.75rem;
            background: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
            font-weight: 600;
            color: #475569;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .admin-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #E2E8F0;
        }
        .admin-table tr:hover td { background: #F8FAFC; }

        .admin-input,
        .admin-textarea,
        .admin-select {
            width: 100%;
            padding: 0.6rem 0.75rem;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.92rem;
            background: #fff;
            color: #0F172A;
        }
        .admin-input:focus,
        .admin-textarea:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .admin-textarea { min-height: 100px; resize: vertical; }

        .admin-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
        }
        .admin-field { margin-bottom: 1.25rem; }
        .admin-help {
            font-size: 0.78rem;
            color: #64748B;
            margin-top: 0.35rem;
        }

        .admin-alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .admin-alert-success { background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; }
        .admin-alert-error   { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
        .admin-alert-info    { background: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD; }

        @media (max-width: 768px) {
            .admin-shell { flex-direction: column; }
            .admin-sidebar { width: 100%; padding: 1rem; }
            .admin-sidebar .brand { margin-bottom: 1rem; }
            .admin-main { padding: 1rem; }
        }
        .admin-tool-icon {
            display: inline-grid;
            place-items: center;
            width: 36px;
            height: 36px;
            filter: drop-shadow(0 1px 2px rgba(15, 23, 42, 0.12));
            transition: transform 0.15s ease;
        }
        .admin-table tr:hover .admin-tool-icon {
            transform: scale(1.1);
        }
        .admin-tool-icon svg {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
<div class="admin-shell">

    <aside class="admin-sidebar">
        <a href="dashboard.php" class="brand" style="display:flex;align-items:center;gap:0.5rem;">
            <img
                src="<?= BASE_PATH ?>assets/img/icon-192.png"
                alt=""
                width="24"
                height="24"
                class="brand-logo"
                style="border-radius:6px;"
                decoding="async"
            >
            <span style="color:#fff;">ConverterTool</span><span style="color:#2563EB;">.in</span>
            <small style="color:#64748B;font-weight:400;font-size:0.75rem;margin-left:0.25rem;">Admin</small>
        </a>
        <nav>
            <a href="dashboard.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'dashboard.php' ? 'is-active' : '' ?>">Dashboard</a>
            <a href="tools.php"     class="<?= basename($_SERVER['SCRIPT_NAME']) === 'tools.php'     ? 'is-active' : '' ?>">Tools</a>
            <a href="settings.php"  class="<?= basename($_SERVER['SCRIPT_NAME']) === 'settings.php'  ? 'is-active' : '' ?>">Site settings</a>
            <a href="blog.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'blog.php' ? 'is-active' : '' ?>">Blog</a>
            <a href="<?= SITE_URL ?>" target="_blank" rel="noopener">View site ↗</a>
            <a href="logout.php" style="margin-top:2rem;color:#F87171;">Log out</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <h1><?= e($pageTitle ?? '') ?></h1>
        </div>