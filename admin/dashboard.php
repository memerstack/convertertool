<?php
// admin/dashboard.php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$S = load_settings($pdo);

/* ── Stats ─────────────────────────────────────── */
$toolCount    = (int) $pdo->query("SELECT COUNT(*) FROM tools")->fetchColumn();
$activeCount  = (int) $pdo->query("SELECT COUNT(*) FROM tools WHERE is_active = 1")->fetchColumn();
$adminCount   = (int) $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();

$lastLogin    = $pdo->query("SELECT MAX(last_login) FROM admins")->fetchColumn();

/* ── Recent tools (by ID desc) ─────────────────── */
$recentTools = $pdo->query(
    "SELECT id, slug, title, category, is_active
       FROM tools ORDER BY id DESC LIMIT 8"
)->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-grid">
    <div class="stat-card">
        <div class="stat-value"><?= $toolCount ?></div>
        <div class="stat-label">Total tools</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $activeCount ?></div>
        <div class="stat-label">Active tools</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $adminCount ?></div>
        <div class="stat-label">Admin users</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="font-size:1rem;">
            <?= $lastLogin ? date('M j, Y', strtotime($lastLogin)) : '—' ?>
        </div>
        <div class="stat-label">Last login</div>
    </div>
</div>

<div class="admin-card">
    <h2>Recent tools</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th>Category</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recentTools as $t): ?>
            <tr>
                <td><strong><?= e($t['title']) ?></strong></td>
                <td><code><?= e($t['slug']) ?></code></td>
                <td><?= e($t['category']) ?></td>
                <td>
                    <?php if ($t['is_active']): ?>
                        <span style="color:#166534;">● Active</span>
                    <?php else: ?>
                        <span style="color:#991B1B;">● Hidden</span>
                    <?php endif; ?>
                </td>
                <td><a href="tools.php?id=<?= (int)$t['id'] ?>">Edit →</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="admin-card">
    <h2>Quick links</h2>
    <p style="color:#64748B;font-size:0.9rem;margin-bottom:1rem;">
        Common tasks to keep your site fresh.
    </p>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
        <a href="settings.php" class="btn btn-primary" style="padding:0.55rem 1rem;font-size:0.88rem;">Edit theme colors</a>
        <a href="tools.php"    class="btn btn-ghost"   style="padding:0.55rem 1rem;font-size:0.88rem;">Manage tools</a>
        <a href="<?= SITE_URL ?>/tools.php" target="_blank" class="btn btn-ghost" style="padding:0.55rem 1rem;font-size:0.88rem;">View public directory</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>