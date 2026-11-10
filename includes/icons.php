<?php
// includes/icons.php
// Inline 3D-styled SVG icons for each tool.
// Uses CSS variables (--color-primary, --color-primary-soft) so they
// re-color automatically when the theme changes in admin.

/**
 * Return an inline SVG icon for a tool slug.
 *
 * @param string $slug  Tool slug (e.g. 'word-to-pdf')
 * @param int    $size  Pixel size (rendered via width/height attributes)
 */
function tool_icon(string $slug, int $size = 48): string {

    // Every icon shares the same 64x64 viewBox for consistent visual weight
    $common = sprintf(
        'width="%d" height="%d" viewBox="0 0 64 64" fill="none" '
      . 'xmlns="http://www.w3.org/2000/svg" aria-hidden="true" '
      . 'style="display:block;"',
        $size, $size
    );

    // Gradient IDs must be unique per render to avoid collisions if
    // multiple icons appear on the same page.
    $uid = 'g' . substr(md5($slug . $size), 0, 8);

    switch ($slug) {

        /* ═══ Word to PDF ═══ */
        case 'word-to-pdf':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#4A90E2"/>
      <stop offset="1" stop-color="#2563EB"/>
    </linearGradient>
    <linearGradient id="{$uid}b" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#F87171"/>
      <stop offset="1" stop-color="#DC2626"/>
    </linearGradient>
  </defs>
  <rect x="8" y="6" width="34" height="46" rx="4" fill="url(#{$uid}a)"/>
  <rect x="8" y="6" width="34" height="8" rx="4" fill="#1E40AF" opacity=".4"/>
  <path d="M14 22h22M14 28h22M14 34h14" stroke="#fff" stroke-width="2.2" stroke-linecap="round" opacity=".9"/>
  <path d="M46 44l-8 12h10l8-12z" fill="url(#{$uid}b)" opacity=".9"/>
  <rect x="44" y="40" width="18" height="22" rx="3" fill="url(#{$uid}b)"/>
  <text x="53" y="55" text-anchor="middle" font-family="Inter,sans-serif" font-size="8" font-weight="700" fill="#fff">PDF</text>
</svg>
SVG;

        /* ═══ PDF to Word ═══ */
        case 'pdf-to-word':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#F87171"/>
      <stop offset="1" stop-color="#DC2626"/>
    </linearGradient>
    <linearGradient id="{$uid}b" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#4A90E2"/>
      <stop offset="1" stop-color="#2563EB"/>
    </linearGradient>
  </defs>
  <rect x="8" y="6" width="34" height="46" rx="4" fill="url(#{$uid}a)"/>
  <rect x="8" y="6" width="34" height="8" rx="4" fill="#991B1B" opacity=".4"/>
  <text x="25" y="38" text-anchor="middle" font-family="Inter,sans-serif" font-size="9" font-weight="700" fill="#fff">PDF</text>
  <path d="M46 44l-8 12h10l8-12z" fill="url(#{$uid}b)" opacity=".9"/>
  <rect x="44" y="40" width="18" height="22" rx="3" fill="url(#{$uid}b)"/>
  <text x="53" y="54" text-anchor="middle" font-family="Inter,sans-serif" font-size="7" font-weight="700" fill="#fff">DOC</text>
</svg>
SVG;

        /* ═══ Excel to CSV ═══ */
        case 'excel-to-csv':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#34D399"/>
      <stop offset="1" stop-color="#059669"/>
    </linearGradient>
  </defs>
  <rect x="8" y="8" width="44" height="44" rx="6" fill="url(#{$uid}a)"/>
  <rect x="8" y="8" width="44" height="8" rx="6" fill="#047857" opacity=".35"/>
  <path d="M16 24h28M16 32h28M16 40h28M24 20v24M40 20v24" stroke="#fff" stroke-width="1.8" opacity=".85"/>
  <path d="M44 52l6 6-6 2z" fill="#065F46" opacity=".3"/>
</svg>
SVG;

        /* ═══ CSV to Excel ═══ */
        case 'csv-to-excel':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#FBBF24"/>
      <stop offset="1" stop-color="#D97706"/>
    </linearGradient>
  </defs>
  <rect x="8" y="8" width="44" height="44" rx="6" fill="url(#{$uid}a)"/>
  <rect x="8" y="8" width="44" height="8" rx="6" fill="#92400E" opacity=".35"/>
  <path d="M18 20h10l4 24H12z" fill="#fff" opacity=".9"/>
  <path d="M26 20l-6 24M34 20h10l-6 24" stroke="#fff" stroke-width="1.8" opacity=".7"/>
</svg>
SVG;

        /* ═══ Image to PDF ═══ */
        case 'image-to-pdf':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#A78BFA"/>
      <stop offset="1" stop-color="#7C3AED"/>
    </linearGradient>
    <linearGradient id="{$uid}b" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#F87171"/>
      <stop offset="1" stop-color="#DC2626"/>
    </linearGradient>
  </defs>
  <rect x="6" y="10" width="34" height="30" rx="4" fill="url(#{$uid}a)"/>
  <circle cx="18" cy="22" r="4" fill="#FDE68A"/>
  <path d="M10 36l10-10 8 8 5-4 7 6z" fill="#fff" opacity=".85"/>
  <rect x="26" y="30" width="26" height="26" rx="4" fill="url(#{$uid}b)"/>
  <text x="39" y="47" text-anchor="middle" font-family="Inter,sans-serif" font-size="8" font-weight="700" fill="#fff">PDF</text>
</svg>
SVG;

        /* ═══ PDF to Image ═══ */
        case 'pdf-to-image':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#F87171"/>
      <stop offset="1" stop-color="#DC2626"/>
    </linearGradient>
    <linearGradient id="{$uid}b" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#A78BFA"/>
      <stop offset="1" stop-color="#7C3AED"/>
    </linearGradient>
  </defs>
  <rect x="6" y="10" width="34" height="30" rx="4" fill="url(#{$uid}a)"/>
  <text x="23" y="30" text-anchor="middle" font-family="Inter,sans-serif" font-size="8" font-weight="700" fill="#fff">PDF</text>
  <rect x="26" y="30" width="26" height="26" rx="4" fill="url(#{$uid}b)"/>
  <circle cx="35" cy="40" r="3" fill="#FDE68A"/>
  <path d="M30 50l7-7 4 4 3-2 3 5z" fill="#fff" opacity=".85"/>
</svg>
SVG;

        /* ═══ Image Compressor ═══ */
        case 'image-compress':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#A78BFA"/>
      <stop offset="1" stop-color="#7C3AED"/>
    </linearGradient>
  </defs>
  <rect x="6" y="10" width="44" height="32" rx="4" fill="url(#{$uid}a)"/>
  <circle cx="18" cy="22" r="3.5" fill="#FDE68A"/>
  <path d="M10 36l10-10 8 8 6-5 8 7z" fill="#fff" opacity=".85"/>
  <rect x="16" y="44" width="32" height="14" rx="3" fill="#0F172A"/>
  <path d="M22 50h20M22 54h12" stroke="#60A5FA" stroke-width="1.8" stroke-linecap="round"/>
</svg>
SVG;

        /* ═══ Image Resizer ═══ */
        case 'image-resize':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#A78BFA"/>
      <stop offset="1" stop-color="#7C3AED"/>
    </linearGradient>
  </defs>
  <rect x="6" y="10" width="32" height="24" rx="3" fill="url(#{$uid}a)" opacity=".5"/>
  <rect x="14" y="18" width="34" height="34" rx="3" fill="url(#{$uid}a)"/>
  <circle cx="24" cy="28" r="3" fill="#FDE68A"/>
  <path d="M18 44l8-8 6 6 4-3 6 5z" fill="#fff" opacity=".85"/>
  <path d="M48 18l8 8M48 26h8v-8" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG;

        /* ═══ PNG to JPG ═══ */
        case 'png-to-jpg':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#A78BFA"/>
      <stop offset="1" stop-color="#7C3AED"/>
    </linearGradient>
  </defs>
  <rect x="6" y="14" width="26" height="26" rx="3" fill="url(#{$uid}a)"/>
  <text x="19" y="32" text-anchor="middle" font-family="Inter,sans-serif" font-size="7" font-weight="700" fill="#fff">PNG</text>
  <rect x="32" y="24" width="26" height="26" rx="3" fill="#FBBF24"/>
  <text x="45" y="42" text-anchor="middle" font-family="Inter,sans-serif" font-size="7" font-weight="700" fill="#78350F">JPG</text>
  <path d="M30 28l4-4 4 4M34 24v8" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
</svg>
SVG;

        /* ═══ Merge PDF ═══ */
        case 'merge-pdf':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#F87171"/>
      <stop offset="1" stop-color="#DC2626"/>
    </linearGradient>
  </defs>
  <rect x="4" y="10" width="20" height="28" rx="3" fill="url(#{$uid}a)" opacity=".7"/>
  <rect x="12" y="18" width="20" height="28" rx="3" fill="url(#{$uid}a)" opacity=".85"/>
  <rect x="20" y="26" width="20" height="28" rx="3" fill="url(#{$uid}a)"/>
  <text x="30" y="44" text-anchor="middle" font-family="Inter,sans-serif" font-size="7" font-weight="700" fill="#fff">PDF</text>
  <path d="M44 32l8 0M48 28l4 4-4 4" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
</svg>
SVG;

        /* ═══ Split PDF ═══ */
        case 'split-pdf':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#F87171"/>
      <stop offset="1" stop-color="#DC2626"/>
    </linearGradient>
  </defs>
  <rect x="20" y="10" width="22" height="44" rx="3" fill="url(#{$uid}a)"/>
  <text x="31" y="36" text-anchor="middle" font-family="Inter,sans-serif" font-size="8" font-weight="700" fill="#fff">PDF</text>
  <path d="M12 24h-4M8 24l4-4M8 24l4 4" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
  <path d="M52 40h4M56 40l-4-4M56 40l-4 4" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
</svg>
SVG;

        /* ═══ Compress PDF ═══ */
        case 'compress-pdf':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#F87171"/>
      <stop offset="1" stop-color="#DC2626"/>
    </linearGradient>
  </defs>
  <rect x="14" y="8" width="36" height="48" rx="3" fill="url(#{$uid}a)"/>
  <text x="32" y="34" text-anchor="middle" font-family="Inter,sans-serif" font-size="9" font-weight="700" fill="#fff">PDF</text>
  <path d="M22 44h20M22 49h14" stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".7"/>
  <path d="M52 14l-8 0M44 14l4-4M44 14l4 4" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" transform="rotate(180 48 14)"/>
</svg>
SVG;

        /* ═══ HEIC to JPG ═══ */
        case 'heic-to-jpg':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#F472B6"/>
      <stop offset="1" stop-color="#DB2777"/>
    </linearGradient>
  </defs>
  <rect x="6" y="12" width="28" height="32" rx="3" fill="url(#{$uid}a)"/>
  <circle cx="20" cy="24" r="3" fill="#FDE68A"/>
  <path d="M10 40l8-8 6 6 4-3 6 5z" fill="#fff" opacity=".85"/>
  <rect x="32" y="22" width="26" height="26" rx="3" fill="#FBBF24"/>
  <text x="45" y="40" text-anchor="middle" font-family="Inter,sans-serif" font-size="7" font-weight="700" fill="#78350F">JPG</text>
  <path d="M30 28l4-4 4 4M34 24v8" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
</svg>
SVG;

        /* ═══ Portrait Background Remover ═══ */
        case 'portrait-background-remover':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#60A5FA"/>
      <stop offset="1" stop-color="#2563EB"/>
    </linearGradient>
    <linearGradient id="{$uid}b" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#FDE68A"/>
      <stop offset="1" stop-color="#F59E0B"/>
    </linearGradient>
  </defs>
  <circle cx="32" cy="26" r="10" fill="url(#{$uid}b)"/>
  <path d="M14 52c0-10 8-16 18-16s18 6 18 16z" fill="url(#{$uid}a)"/>
  <path d="M8 8l4 4M56 8l-4 4M8 56l4-4M56 56l-4-4" stroke="#0F172A" stroke-width="2" stroke-linecap="round" opacity=".6"/>
</svg>
SVG;

        /* ═══ General Background Remover ═══ */
        case 'background-remover':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#60A5FA"/>
      <stop offset="1" stop-color="#2563EB"/>
    </linearGradient>
    <linearGradient id="{$uid}b" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#FDE68A"/>
      <stop offset="1" stop-color="#F59E0B"/>
    </linearGradient>
  </defs>
  <path d="M20 20a12 12 0 0 1 24 0v6a12 12 0 0 1-24 0z" fill="url(#{$uid}b)" opacity=".9"/>
  <path d="M12 54c0-12 10-20 20-20s20 8 20 20z" fill="url(#{$uid}a)"/>
  <path d="M4 4l10 10M60 4L50 14M4 60l10-10M60 60L50 50" stroke="#0F172A" stroke-width="2.2" stroke-linecap="round" opacity=".55"/>
</svg>
SVG;

        /* ═══ Passport Photo ═══ */
        case 'passport-photo':
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#A78BFA"/>
      <stop offset="1" stop-color="#7C3AED"/>
    </linearGradient>
  </defs>
  <rect x="10" y="6" width="44" height="52" rx="4" fill="url(#{$uid}a)"/>
  <rect x="10" y="6" width="44" height="10" rx="4" fill="#1E40AF" opacity=".35"/>
  <circle cx="32" cy="28" r="9" fill="#FDE68A"/>
  <path d="M18 50c0-8 6-13 14-13s14 5 14 13z" fill="#FDE68A" opacity=".85"/>
  <circle cx="46" cy="12" r="3" fill="#FDE68A"/>
</svg>
SVG;

        /* ═══ Default fallback ═══ */
        default:
            return <<<SVG
<svg {$common}>
  <defs>
    <linearGradient id="{$uid}a" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#94A3B8"/>
      <stop offset="1" stop-color="#475569"/>
    </linearGradient>
  </defs>
  <rect x="12" y="8" width="40" height="48" rx="4" fill="url(#{$uid}a)"/>
  <path d="M20 20h24M20 28h24M20 36h16" stroke="#fff" stroke-width="2.2" stroke-linecap="round" opacity=".85"/>
</svg>
SVG;
    }
}