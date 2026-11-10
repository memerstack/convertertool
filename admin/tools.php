<?php
// admin/tools.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_admin();

$S = load_settings($pdo);
$registry = require __DIR__ . '/../includes/tools-meta.php';

$editing = null;
$saved   = false;
$error   = '';

/* ── Save a tool ───────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_tool') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);

    $title     = trim($_POST['title'] ?? '');
    $slug      = trim($_POST['slug'] ?? '');
    $category  = trim($_POST['category'] ?? '');
    $shortDesc = trim($_POST['short_desc'] ?? '');
    $icon      = trim($_POST['icon'] ?? 'file');
    $isActive  = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    // Optional SEO overrides
    $seoTitle  = trim($_POST['seo_title'] ?? '');
    $seoDesc   = trim($_POST['seo_description'] ?? '');

    if ($title === '' || $slug === '' || $category === '') {
        $error = 'Title, slug, and category are required.';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE tools SET
                title = ?, slug = ?, category = ?, short_desc = ?, icon = ?,
                seo_title = ?, seo_description = ?,
                is_active = ?, sort_order = ?
                WHERE id = ?");
            $stmt->execute([
                $title, $slug, $category, $shortDesc, $icon,
                $seoTitle, $seoDesc,
                $isActive, $sortOrder, $id,
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO tools
                (title, slug, category, short_desc, icon, seo_title, seo_description, is_active, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $title, $slug, $category, $shortDesc, $icon,
                $seoTitle, $seoDesc,
                $isActive, $sortOrder,
            ]);
        }
        $saved = true;
    }
}

/* ── Toggle visibility ─────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE tools SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    flash('success', 'Tool visibility updated.');
    header('Location: tools.php');
    exit;
}

/* ── Delete a tool ─────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM tools WHERE id = ?")->execute([$id]);
    flash('success', 'Tool deleted.');
    header('Location: tools.php');
    exit;
}

/* ── Load editing tool ─────────────────────────── */
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id === 0) {
        // Creating new tool
        $editing = [
            'id' => 0, 'slug' => '', 'title' => '', 'short_desc' => '',
            'category' => 'Image', 'icon' => 'img', 'is_active' => 1, 'sort_order' => 0,
            'seo_title' => '', 'seo_description' => '',
        ];
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tools WHERE id = ?");
        $stmt->execute([$id]);
        $editing = $stmt->fetch();
        if (!$editing) {
            header('Location: tools.php');
            exit;
        }
    }
}

/* ── Full list ─────────────────────────────────── */
$tools = $pdo->query("SELECT * FROM tools ORDER BY category, sort_order, title")->fetchAll();
$flashSuccess = flash('success');

$pageTitle = $editing ? ($editing['id'] ? 'Edit tool' : 'New tool') : 'Tools';
require __DIR__ . '/includes/header.php';
?>

<?php if ($flashSuccess): ?>
    <div class="admin-alert admin-alert-success"><?= e($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($saved): ?>
    <div class="admin-alert admin-alert-success">Changes saved.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="admin-alert admin-alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($editing): ?>

    <!-- ═══════════════ EDITOR ═══════════════ -->
    <div class="admin-card">
        <h2 style="display:flex;align-items:center;gap:0.75rem;margin:0 0 1.5rem;">
    <?php if ($editing['id'] && !empty($editing['slug'])): ?>
        <span class="admin-tool-icon" style="width:32px;height:32px;">
            <?= tool_icon($editing['slug'], 32) ?>
        </span>
    <?php endif; ?>
    <?= $editing['id'] ? 'Edit "' . e($editing['title']) . '"' : 'New tool' ?>
</h2>

        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_tool">
            <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">

            <div class="admin-field">
                <label class="admin-label" for="title">Title</label>
                <input type="text" id="title" name="title" class="admin-input"
                       value="<?= e($editing['title']) ?>" required>
            </div>

            <div class="admin-field">
                <label class="admin-label" for="slug">Slug (URL identifier)</label>
                <input type="text" id="slug" name="slug" class="admin-input"
                       value="<?= e($editing['slug']) ?>" required
                       pattern="[a-z0-9\-]+">
                <p class="admin-help">Used in /tool.php?t=slug — lowercase, hyphens only</p>
            </div>

            <div class="admin-field">
                <label class="admin-label" for="short_desc">Short description</label>
                <textarea id="short_desc" name="short_desc" class="admin-textarea"
                          style="min-height:70px;"><?= e($editing['short_desc']) ?></textarea>
                <p class="admin-help">One-line tagline shown on cards. Max 255 characters.</p>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="admin-field">
                    <label class="admin-label" for="category">Category</label>
                    <select id="category" name="category" class="admin-select">
                        <?php foreach (['Image','PDF','Document','Spreadsheet','Audio','Video','Other'] as $c): ?>
                            <option value="<?= e($c) ?>" <?= $editing['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-field">
                    <label class="admin-label" for="icon">Icon</label>
                    <select id="icon" name="icon" class="admin-select">
                        <?php foreach (['img'=>'IMG','pdf'=>'PDF','doc'=>'DOC','xls'=>'XLS','csv'=>'CSV','file'=>'FILE'] as $k=>$v): ?>
                            <option value="<?= e($k) ?>" <?= $editing['icon'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="admin-field">
                    <label class="admin-label" for="sort_order">Sort order</label>
                    <input type="number" id="sort_order" name="sort_order" class="admin-input"
                           value="<?= (int)$editing['sort_order'] ?>">
                </div>
                <div class="admin-field">
                    <label class="admin-label">
                        <input type="checkbox" name="is_active" value="1" <?= $editing['is_active'] ? 'checked' : '' ?>>
                        Visible on site
                    </label>
                </div>
            </div>

            <hr style="border:none;border-top:1px solid #E2E8F0;margin:1.5rem 0;">

            <h3 style="font-size:1rem;margin:0 0 1rem;">SEO overrides (optional)</h3>
            <p class="admin-help" style="margin-bottom:1rem;">
                These fields are stored in the DB but your public tool page currently reads SEO from
                <code>includes/tools-meta.php</code>. Use them as notes for now; edit the meta file to
                publish SEO changes.
            </p>

            <div class="admin-field">
                <label class="admin-label" for="seo_title">SEO title override</label>
                <input type="text" id="seo_title" name="seo_title" class="admin-input"
                    value="<?= e($editing['seo_title'] ?? '') ?>"
                    placeholder="Leave blank to use tools-meta.php default">>
            </div>

            <div class="admin-field">
                <label class="admin-label" for="seo_description">Meta description override</label>
                <textarea id="seo_description" name="seo_description" class="admin-textarea"
                    style="min-height:80px;"
                    placeholder="Leave blank to use tools-meta.php default"><?= e($editing['seo_description'] ?? '') ?></textarea>
            </div>

            <div style="display:flex;gap:0.75rem;margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary" style="padding:0.65rem 1.25rem;">Save tool</button>
                <a href="tools.php" class="btn btn-ghost" style="padding:0.65rem 1.25rem;">Cancel</a>
            </div>
        </form>
    </div>

<?php else: ?>

    <!-- ═══════════════ LIST ═══════════════ -->
    <div class="admin-card" style="display:flex;justify-content:space-between;align-items:center;">
        <div>
            <h2 style="margin:0;">All tools</h2>
            <p class="admin-help" style="margin:0;"><?= count($tools) ?> tools in database</p>
        </div>
        <a href="tools.php?id=0" class="btn btn-primary" style="padding:0.55rem 1rem;font-size:0.88rem;">+ Add tool</a>
    </div>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:56px;"></th>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th style="width:180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tools as $t): ?>
                <tr>
                    <td>
                        <span class="admin-tool-icon">
                            <?= tool_icon($t['slug'], 36) ?>
                        </span>
                    </td>
                    <td><strong><?= e($t['title']) ?></strong></td>
                    <td><code style="font-size:0.8rem;"><?= e($t['slug']) ?></code></td>
                    <td><?= e($t['category']) ?></td>
                    <td>
                        <?php if ($t['is_active']): ?>
                            <span style="color:#166534;font-size:0.85rem;">● Active</span>
                        <?php else: ?>
                            <span style="color:#991B1B;font-size:0.85rem;">● Hidden</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$t['sort_order'] ?></td>
                    <td>
                        <a href="tools.php?id=<?= (int)$t['id'] ?>"
                        style="font-size:0.85rem;margin-right:0.75rem;color:#2563EB;">Edit</a>

                        <form method="post" style="display:inline;">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                            <button type="submit"
                                    style="background:none;border:none;padding:0;font-size:0.85rem;color:#64748B;cursor:pointer;margin-right:0.75rem;">
                                <?= $t['is_active'] ? 'Hide' : 'Show' ?>
                            </button>
                        </form>

                        <form method="post" style="display:inline;"
                            onsubmit="return confirm('Delete this tool permanently?');">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                            <button type="submit"
                                    style="background:none;border:none;padding:0;font-size:0.85rem;color:#DC2626;cursor:pointer;">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>