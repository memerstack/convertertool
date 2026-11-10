<?php
// admin/settings.php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$S = load_settings($pdo);
$saved = false;
$error = '';

/* Editable settings — extend as needed */
$fields = [
    'site_title'        => ['label' => 'Site title',         'type' => 'text',  'help' => 'Full site name shown in browser tabs'],
    'site_name'         => ['label' => 'Site name (short)',  'type' => 'text',  'help' => 'Brand shown in the header/footer'],
    'site_tagline'      => ['label' => 'Tagline',            'type' => 'text',  'help' => 'Shown under the site title on the homepage'],
    'site_description'  => ['label' => 'Meta description',   'type' => 'textarea','help' => 'Used as the default meta description'],
    'meta_keywords'     => ['label' => 'Meta keywords',      'type' => 'text',  'help' => 'Comma-separated (legacy, harmless)'],
    'twitter_handle'    => ['label' => 'Twitter / X handle', 'type' => 'text',  'help' => 'e.g. @convertertool'],

    'color_primary'      => ['label' => 'Primary color',      'type' => 'color', 'help' => 'Accent buttons, links, highlights'],
    'color_primary_dark' => ['label' => 'Primary dark',       'type' => 'color', 'help' => 'Hover state for primary'],
    'color_accent'       => ['label' => 'Text / accent',      'type' => 'color', 'help' => 'Headings, strong text'],
    'color_bg'           => ['label' => 'Page background',    'type' => 'color', 'help' => 'Overall page background'],
    'color_surface'      => ['label' => 'Surface (cards)',    'type' => 'color', 'help' => 'Cards, panels, header'],
    'color_muted'        => ['label' => 'Muted text',         'type' => 'color', 'help' => 'Secondary text, hints'],
    'color_border'       => ['label' => 'Border',             'type' => 'color', 'help' => 'Hairline borders'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $stmt = $pdo->prepare(
        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    foreach ($fields as $key => $meta) {
        $val = $_POST[$key] ?? '';
        // Basic sanitization — trim text, keep hex for colors
        if ($meta['type'] !== 'color') $val = trim($val);
        $stmt->execute([$key, $val]);
    }
    flash('success', 'Settings saved.');
    header('Location: settings.php');
    exit;
}

$flashSuccess = flash('success');

$pageTitle = 'Site settings';
require __DIR__ . '/includes/header.php';
?>

<?php if ($flashSuccess): ?>
    <div class="admin-alert admin-alert-success"><?= e($flashSuccess) ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

    <div class="admin-card">
        <h2>General</h2>
        <?php foreach (['site_title','site_name','site_tagline','meta_keywords','twitter_handle'] as $k): ?>
            <?php $meta = $fields[$k]; ?>
            <div class="admin-field">
                <label class="admin-label" for="<?= e($k) ?>"><?= e($meta['label']) ?></label>
                <input type="text" id="<?= e($k) ?>" name="<?= e($k) ?>" class="admin-input"
                       value="<?= e($S[$k] ?? '') ?>">
                <p class="admin-help"><?= e($meta['help']) ?></p>
            </div>
        <?php endforeach; ?>

        <?php $meta = $fields['site_description']; ?>
        <div class="admin-field">
            <label class="admin-label" for="site_description"><?= e($meta['label']) ?></label>
            <textarea id="site_description" name="site_description" class="admin-textarea"><?= e($S['site_description'] ?? '') ?></textarea>
            <p class="admin-help"><?= e($meta['help']) ?></p>
        </div>
    </div>

    <div class="admin-card">
        <h2>Theme colors</h2>
        <p class="admin-help" style="margin-bottom:1.5rem;">
            These values override the CSS variables in <code>assets/css/style.css</code>.
            Changes take effect immediately across all pages.
        </p>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;">
            <?php foreach ($fields as $k => $meta): if ($meta['type'] !== 'color') continue; ?>
                <div class="admin-field" style="margin-bottom:0;">
                    <label class="admin-label" for="<?= e($k) ?>"><?= e($meta['label']) ?></label>
                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <input type="color" id="<?= e($k) ?>" name="<?= e($k) ?>"
                               value="<?= e($S[$k] ?? '#000000') ?>"
                               style="width:44px;height:44px;padding:2px;border:1px solid #E2E8F0;border-radius:6px;cursor:pointer;background:#fff;">
                        <input type="text" name="<?= e($k) ?>_text" class="admin-input"
                               value="<?= e($S[$k] ?? '#000000') ?>"
                               style="font-family:monospace;font-size:0.85rem;"
                               oninput="document.getElementById('<?= e($k) ?>').value=this.value;"
                               readonly>
                    </div>
                    <p class="admin-help"><?= e($meta['help']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="display:flex;gap:0.75rem;">
        <button type="submit" class="btn btn-primary" style="padding:0.65rem 1.25rem;">Save settings</button>
        <a href="dashboard.php" class="btn btn-ghost" style="padding:0.65rem 1.25rem;">Back to dashboard</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>