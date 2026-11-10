<?php
// includes/footer.php
// Expects: $S (site settings)
$siteName  = $S['site_name'] ?? 'ConverterTool';
$siteTitle = $S['site_title'] ?? 'ConverterTool.in';
$year      = date('Y');
?>
</main><!-- /#main -->

<footer class="site-footer" role="contentinfo">
    <div class="container">
        <div class="footer-grid">
            <div>
                <span class="footer-brand" style="display:inline-flex;align-items:center;gap:0.5rem;">
                    <img
                        src="<?= BASE_PATH ?>assets/img/icon-192.png"
                        alt=""
                        width="24"
                        height="24"
                        style="border-radius:6px;opacity:0.9;"
                        decoding="async"
                    >
                    <?= e($siteName) ?><span style="color:#fff">.in</span>
                </span>
                <p class="footer-desc">
                    Free, private, browser-based file converters. No uploads, no account, no limits.
                </p>
            </div>

            <div class="footer-col">
                <h4>Popular</h4>
                <ul>
                    <li><a href="<?= BASE_PATH ?>tools.php">All Tools</a></li>
                    <li><a href="<?= BASE_PATH ?>tool.php?t=word-to-pdf">Word to PDF</a></li>
                    <li><a href="<?= BASE_PATH ?>tool.php?t=pdf-to-word">PDF to Word</a></li>
                    <li><a href="<?= BASE_PATH ?>tool.php?t=image-to-pdf">Image to PDF</a></li>
                    <li><a href="<?= BASE_PATH ?>tool.php?t=merge-pdf">Merge PDF</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="<?= BASE_PATH ?>about.php">About</a></li>
                    <li><a href="<?= BASE_PATH ?>contact.php">Contact</a></li>
                    <li><a href="<?= BASE_PATH ?>privacy.php">Privacy</a></li>
                    <li><a href="<?= BASE_PATH ?>terms.php">Terms</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Resources</h4>
                <ul>
                    <li><a href="<?= BASE_PATH ?>how-it-works.php">How It Works</a></li>
                    <li><a href="<?= BASE_PATH ?>faq.php">FAQ</a></li>
                    <li><a href="<?= BASE_PATH ?>blog/">Blog</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= $year ?> <?= e($siteTitle) ?>. All rights reserved.</span>
            <span>Made for people who value their privacy.</span>
        </div>
    </div>
</footer>
<script src="<?= BASE_PATH ?>assets/js/pwa.js" defer></script>
</body>
</html>