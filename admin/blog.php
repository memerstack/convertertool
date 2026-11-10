<?php
// admin/blog.php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$S = load_settings($pdo);
$editing = null;
$error   = '';

/* ═══════════════════════════════════════════════════════════
   Paths & limits
   ═══════════════════════════════════════════════════════════ */
$uploadDir     = __DIR__ . '/../assets/img/blog/';
$uploadUrlBase = '/assets/img/blog/';
$maxBytes      = 3 * 1024 * 1024;   // 3 MB
$allowedTypes  = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

/**
 * Handle an uploaded cover image. Returns the filename on success,
 * or null if no file was uploaded. Throws on validation error.
 */
function handle_cover_upload(array $file, string $uploadDir, int $maxBytes, array $allowedTypes): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // user didn't select a new file — keep existing
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (error code ' . $file['error'] . ').');
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('Image is too large. Maximum is ' . round($maxBytes / 1048576, 1) . ' MB.');
    }
    // Trust the actual file contents, not the browser-supplied MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!isset($allowedTypes[$mime])) {
        throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed (detected: ' . $mime . ').');
    }
    $ext = $allowedTypes[$mime];
    $safeName = 'cover-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = rtrim($uploadDir, '/') . '/' . $safeName;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException('Could not create upload directory.');
    }
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }
    return $safeName;
}

/* ═══════════════════════════════════════════════════════════
   Save (create or update)
   ═══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_post') {
    csrf_check();
    $id          = (int)($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $slug        = trim($_POST['slug'] ?? '');
    $excerpt     = trim($_POST['excerpt'] ?? '');
    $content     = $_POST['content'] ?? '';
    $seoTitle    = trim($_POST['seo_title'] ?? '');
    $seoDesc     = trim($_POST['seo_description'] ?? '');
    $tags        = trim($_POST['tags'] ?? '');
    $relatedTool = trim($_POST['related_tools'] ?? '');
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $removeCover = !empty($_POST['remove_cover']);

    if ($slug === '') {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $title)), '-'));
    }

    if ($title === '')       $error = 'Title is required.';
    elseif ($slug === '')    $error = 'Slug could not be generated — please enter one.';
    elseif ($content === '') $error = 'Content is required.';

    if (!$error) {
        $check = $pdo->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id <> ?");
        $check->execute([$slug, $id]);
        if ($check->fetch()) $error = 'That slug is already used by another post.';
    }

    // Determine final cover_image value
    $newCover = null; // null = keep existing
    if (!$error) {
        try {
            $uploaded = handle_cover_upload($_FILES['cover_file'] ?? [], $uploadDir, $maxBytes, $allowedTypes);
            if ($uploaded !== null) {
                $newCover = $uploaded;
            }
        } catch (RuntimeException $ex) {
            $error = $ex->getMessage();
        }
    }

    if (!$error) {
        // Load the current cover so we can delete the old file after update
        $oldCover = null;
        if ($id > 0) {
            $c = $pdo->prepare("SELECT cover_image FROM blog_posts WHERE id = ?");
            $c->execute([$id]);
            $oldCover = $c->fetchColumn() ?: null;
        }

        if ($id > 0) {
            // UPDATE
            $finalCover = $newCover !== null
                ? $newCover
                : ($removeCover ? null : $oldCover);

            $stmt = $pdo->prepare(
                "UPDATE blog_posts SET
                    title = ?, slug = ?, excerpt = ?, content = ?, cover_image = ?,
                    seo_title = ?, seo_description = ?, tags = ?, related_tools = ?,
                    is_published = ?,
                    published_at = CASE
                        WHEN ? = 1 AND published_at IS NULL THEN NOW()
                        WHEN ? = 0 THEN NULL
                        ELSE published_at
                    END
                 WHERE id = ?"
            );
            $stmt->execute([
                $title, $slug, $excerpt, $content, $finalCover,
                $seoTitle, $seoDesc, $tags, $relatedTool,
                $isPublished, $isPublished, $isPublished,
                $id,
            ]);

            // Clean up the replaced file
            if ($oldCover && $oldCover !== $finalCover && is_file($uploadDir . $oldCover)) {
                @unlink($uploadDir . $oldCover);
            }
        } else {
            // INSERT
            $stmt = $pdo->prepare(
                "INSERT INTO blog_posts
                    (title, slug, excerpt, content, cover_image,
                     seo_title, seo_description, tags, related_tools,
                     is_published, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $title, $slug, $excerpt, $content, $newCover,
                $seoTitle, $seoDesc, $tags, $relatedTool,
                $isPublished, $isPublished ? date('Y-m-d H:i:s') : null,
            ]);
        }

        flash('success', $id > 0 ? 'Post updated.' : 'Post created.');
        header('Location: blog.php');
        exit;
    }
}

/* ═══════════════════════════════════════════════════════════
   Toggle publish
   ═══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_publish') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare(
        "UPDATE blog_posts SET
            is_published = 1 - is_published,
            published_at = CASE WHEN is_published = 0 THEN NOW() ELSE published_at END
         WHERE id = ?"
    )->execute([$id]);
    flash('success', 'Publish state updated.');
    header('Location: blog.php');
    exit;
}

/* ═══════════════════════════════════════════════════════════
   Delete — also remove the cover file
   ═══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $c = $pdo->prepare("SELECT cover_image FROM blog_posts WHERE id = ?");
    $c->execute([$id]);
    $cover = $c->fetchColumn();
    $pdo->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$id]);
    if ($cover && is_file($uploadDir . $cover)) {
        @unlink($uploadDir . $cover);
    }
    flash('success', 'Post deleted.');
    header('Location: blog.php');
    exit;
}

/* ═══════════════════════════════════════════════════════════
   Load editor
   ═══════════════════════════════════════════════════════════ */
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id === 0) {
        $editing = [
            'id' => 0, 'slug' => '', 'title' => '', 'excerpt' => '',
            'content' => '', 'cover_image' => '', 'seo_title' => '',
            'seo_description' => '', 'tags' => '', 'related_tools' => '',
            'is_published' => 1, 'published_at' => null,
        ];
    } else {
        $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
        $stmt->execute([$id]);
        $editing = $stmt->fetch();
        if (!$editing) { header('Location: blog.php'); exit; }
    }
}

/* ═══════════════════════════════════════════════════════════
   Full list
   ═══════════════════════════════════════════════════════════ */
$posts = $pdo->query(
    "SELECT id, slug, title, is_published, published_at, updated_at, cover_image
       FROM blog_posts
      ORDER BY COALESCE(published_at, created_at) DESC"
)->fetchAll();

$flashSuccess = flash('success');

$pageTitle = $editing ? ($editing['id'] ? 'Edit post' : 'New post') : 'Blog posts';
require __DIR__ . '/includes/header.php';
?>

<?php if ($flashSuccess): ?>
    <div class="admin-alert admin-alert-success"><?= e($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="admin-alert admin-alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($editing): ?>

    <!-- ═══════════════ EDITOR ═══════════════ -->
    <div class="admin-card">
        <h2><?= $editing['id'] ? 'Edit post' : 'New blog post' ?></h2>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_post">
            <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">

            <div class="admin-field">
                <label class="admin-label" for="title">Title</label>
                <input type="text" id="title" name="title" class="admin-input"
                       value="<?= e($editing['title']) ?>" required>
            </div>

            <div class="admin-field">
                <label class="admin-label" for="slug">Slug</label>
                <input type="text" id="slug" name="slug" class="admin-input"
                       value="<?= e($editing['slug']) ?>"
                       pattern="[a-z0-9\-]+"
                       placeholder="auto-generated from title if left blank">
                <p class="admin-help">Used in /blog/post.php?slug=… — lowercase, hyphens only</p>
            </div>

            <!-- ═══ Cover image ═══ -->
            <div class="admin-field">
                <label class="admin-label" for="cover_file">Cover image</label>
                <?php if (!empty($editing['cover_image'])): ?>
                    <div style="display:flex;gap:1rem;align-items:center;margin-bottom:0.75rem;padding:0.75rem;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;">
                        <img src="<?= SITE_URL ?>/assets/img/blog/<?= e($editing['cover_image']) ?>?v=<?= time() ?>"
                            alt="Current cover"
                             style="max-width:180px;max-height:100px;border-radius:6px;border:1px solid #E2E8F0;background:#fff;object-fit:cover;">
                        <div>
                            <p style="margin:0 0 0.5rem;font-size:0.85rem;color:#64748B;">
                                Current: <code><?= e($editing['cover_image']) ?></code>
                            </p>
                            <label style="font-size:0.85rem;color:#DC2626;cursor:pointer;">
                                <input type="checkbox" name="remove_cover" value="1">
                                Remove this image
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" id="cover_file" name="cover_file"
                       accept="image/jpeg,image/png,image/webp"
                       style="display:block;font-size:0.9rem;">
                <p class="admin-help">
                    Max 3 MB. JPG, PNG, or WEBP.
                    <?= !empty($editing['cover_image']) ? 'Uploading a new image replaces the current one.' : 'Recommended: 1200 × 675 (16:9).' ?>
                </p>
            </div>

            <div class="admin-field">
                <label class="admin-label" for="excerpt">Excerpt (optional)</label>
                <textarea id="excerpt" name="excerpt" class="admin-textarea" style="min-height:70px;"><?= e($editing['excerpt']) ?></textarea>
                <p class="admin-help">Short preview shown on the blog list. If blank, taken from the start of the content.</p>
            </div>

            <div class="admin-field">
                <label class="admin-label" for="content">Content (HTML allowed)</label>
                <textarea id="content" name="content" class="admin-textarea"
                          style="min-height:400px;font-family:ui-monospace,monospace;font-size:0.88rem;"
                          required><?= e($editing['content']) ?></textarea>
                <p class="admin-help">
                    Use HTML tags: <code>&lt;h2&gt;…&lt;/h2&gt;</code>,
                    <code>&lt;p&gt;…&lt;/p&gt;</code>,
                    <code>&lt;ul&gt;&lt;li&gt;…&lt;/li&gt;&lt;/ul&gt;</code>,
                    <code>&lt;a href="/tool.php?t=word-to-pdf"&gt;…&lt;/a&gt;</code>
                </p>
            </div>

            <hr style="border:none;border-top:1px solid #E2E8F0;margin:1.5rem 0;">

            <h3 style="font-size:1rem;margin:0 0 1rem;">SEO (optional)</h3>
            <div class="admin-field">
                <label class="admin-label" for="seo_title">SEO title override</label>
                <input type="text" id="seo_title" name="seo_title" class="admin-input"
                       value="<?= e($editing['seo_title']) ?>"
                       placeholder="Leave blank to use the post title">
            </div>

            <div class="admin-field">
                <label class="admin-label" for="seo_description">Meta description override</label>
                <textarea id="seo_description" name="seo_description" class="admin-textarea"
                          style="min-height:70px;"
                          placeholder="Leave blank to use the excerpt"><?= e($editing['seo_description']) ?></textarea>
            </div>

            <hr style="border:none;border-top:1px solid #E2E8F0;margin:1.5rem 0;">

            <h3 style="font-size:1rem;margin:0 0 1rem;">Metadata</h3>

            <div class="admin-field">
                <label class="admin-label" for="tags">Tags (comma-separated)</label>
                <input type="text" id="tags" name="tags" class="admin-input"
                       value="<?= e($editing['tags']) ?>"
                       placeholder="pdf,heic,iphone,privacy">
            </div>

            <div class="admin-field">
                <label class="admin-label" for="related_tools">Related tools (comma-separated slugs)</label>
                <input type="text" id="related_tools" name="related_tools" class="admin-input"
                       value="<?= e($editing['related_tools']) ?>"
                       placeholder="heic-to-jpg,image-compress">
                <p class="admin-help">Comma-separated tool slugs. They will appear as cards below the post.</p>
            </div>

            <div class="admin-field">
                <label class="admin-label">
                    <input type="checkbox" name="is_published" value="1" <?= $editing['is_published'] ? 'checked' : '' ?>>
                    Published
                </label>
            </div>

            <div style="display:flex;gap:0.75rem;margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary" style="padding:0.65rem 1.25rem;">
                    <?= $editing['id'] ? 'Save changes' : 'Create post' ?>
                </button>
                <a href="blog.php" class="btn btn-ghost" style="padding:0.65rem 1.25rem;">Cancel</a>
                <?php if ($editing['id'] && $editing['is_published'] && $editing['slug']): ?>
                    <a href="<?= SITE_URL ?>/blog/post.php?slug=<?= urlencode($editing['slug']) ?>"
                        target="_blank" rel="noopener"
                       class="btn btn-ghost" style="padding:0.65rem 1.25rem;margin-left:auto;">
                        View live ↗
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

<?php else: ?>

    <!-- ═══════════════ LIST ═══════════════ -->
    <div class="admin-card" style="display:flex;justify-content:space-between;align-items:center;">
        <div>
            <h2 style="margin:0;">Blog posts</h2>
            <p class="admin-help" style="margin:0;"><?= count($posts) ?> posts in database</p>
        </div>
        <a href="blog.php?id=0" class="btn btn-primary" style="padding:0.55rem 1rem;font-size:0.88rem;">+ New post</a>
    </div>

    <?php if (empty($posts)): ?>
        <div class="admin-card" style="text-align:center;padding:3rem 1rem;">
            <p style="color:#64748B;margin:0;">No blog posts yet.</p>
            <p style="color:#64748B;font-size:0.9rem;margin-top:0.75rem;">
                Click <strong>+ New post</strong> above to write your first one.
            </p>
        </div>
    <?php else: ?>
        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:60px;"></th>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Published</th>
                        <th style="width:200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($posts as $p): ?>
                    <tr>
                        <td>
                            <?php if (!empty($p['cover_image'])): ?>
                                <img src="<?= SITE_URL ?>/assets/img/blog/<?= e($p['cover_image']) ?>"
                                    alt=""
                                     style="width:48px;height:36px;object-fit:cover;border-radius:4px;border:1px solid #E2E8F0;">
                            <?php else: ?>
                                <div style="width:48px;height:36px;border-radius:4px;border:1px dashed #E2E8F0;display:grid;place-items:center;color:#94A3B8;font-size:0.65rem;">
                                    —
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= e($p['title']) ?></strong></td>
                        <td><code style="font-size:0.8rem;"><?= e($p['slug']) ?></code></td>
                        <td>
                            <?php if ($p['is_published']): ?>
                                <span style="color:#166534;font-size:0.85rem;">● Published</span>
                            <?php else: ?>
                                <span style="color:#92400E;font-size:0.85rem;">● Draft</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $p['published_at'] ? date('M j, Y', strtotime($p['published_at'])) : '—' ?></td>
                        <td>
                            <a href="blog.php?id=<?= (int)$p['id'] ?>"
                               style="font-size:0.85rem;margin-right:0.75rem;color:#2563EB;">Edit</a>

                            <form method="post" style="display:inline;">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle_publish">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit"
                                        style="background:none;border:none;padding:0;font-size:0.85rem;color:#64748B;cursor:pointer;margin-right:0.75rem;">
                                    <?= $p['is_published'] ? 'Unpublish' : 'Publish' ?>
                                </button>
                            </form>

                            <form method="post" style="display:inline;"
                                  onsubmit="return confirm('Delete this post permanently?');">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>