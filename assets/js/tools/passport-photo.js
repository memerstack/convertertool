// assets/js/tools/passport-photo.js

const MEDIAPIPE_CDN = 'https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation@0.1/';
let selfieSegmentation = null;

const COUNTRY_FORMATS = {
    'us': { label: 'United States', widthMm: 51, heightMm: 51, px: 600,  headMinPct: 0.50, headMaxPct: 0.69 },
    'uk': { label: 'United Kingdom', widthMm: 35, heightMm: 45, px: 413,  headMinPct: 0.64, headMaxPct: 0.72 },
    'eu': { label: 'EU / Schengen',  widthMm: 35, heightMm: 45, px: 413,  headMinPct: 0.64, headMaxPct: 0.72 },
    'in': { label: 'India',          widthMm: 35, heightMm: 45, px: 413,  headMinPct: 0.64, headMaxPct: 0.72 },
    'ca': { label: 'Canada',         widthMm: 50, heightMm: 70, px: 591,  headMinPct: 0.45, headMaxPct: 0.60 },
    'au': { label: 'Australia',      widthMm: 35, heightMm: 45, px: 413,  headMinPct: 0.64, headMaxPct: 0.72 },
};

// Paper dimensions in INCHES at 300 DPI
const PAPER_SIZES = {
    '4x6':    { w: 4,   h: 6,   dpi: 300 },
    '5x7':    { w: 5,   h: 7,   dpi: 300 },
    'a4':     { w: 8.27, h: 11.69, dpi: 300 }, // 210 x 297 mm
    'letter': { w: 8.5, h: 11,  dpi: 300 },
};

async function ensureModel(onProgress) {
    if (selfieSegmentation) {
        if (onProgress) onProgress({ status: 'ready' });
        return;
    }
    if (onProgress) onProgress({ status: 'loading', message: 'Loading 2 MB portrait model…' });

    if (!window.SelfieSegmentation) {
        await new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = MEDIAPIPE_CDN + 'selfie_segmentation.js';
            s.crossOrigin = 'anonymous';
            s.onload = resolve;
            s.onerror = () => reject(new Error('Failed to load MediaPipe.'));
            document.head.appendChild(s);
        });
    }

    selfieSegmentation = new window.SelfieSegmentation({
        locateFile: (file) => MEDIAPIPE_CDN + file,
    });
    selfieSegmentation.setOptions({ modelSelection: 1 });

    await selfieSegmentation.initialize();
    if (onProgress) onProgress({ status: 'ready' });
}

function maskToCanvas(segmentationMask, width, height) {
    if (!segmentationMask) throw new Error('No segmentation mask returned.');
    const c = document.createElement('canvas');
    c.width = width;
    c.height = height;
    c.getContext('2d').drawImage(segmentationMask, 0, 0, width, height);
    return c;
}

function hasPerson(maskCanvas) {
    const { width, height } = maskCanvas;
    const data = maskCanvas.getContext('2d').getImageData(0, 0, width, height).data;
    let opaque = 0;
    for (let i = 3; i < data.length; i += 4) {
        if (data[i] > 128) opaque++;
    }
    const ratio = opaque / (width * height);
    return ratio > 0.03 && ratio < 0.97;
}

async function extractImage(file, keepBg, onStatus) {
    const img = await new Promise((res, rej) => {
        const i = new Image();
        i.onload = () => res(i);
        i.onerror = () => rej(new Error('Could not load image.'));
        i.src = URL.createObjectURL(file);
    });

    const W = img.naturalWidth;
    const H = img.naturalHeight;

    if (keepBg) {
        const canvas = document.createElement('canvas');
        canvas.width = W;
        canvas.height = H;
        canvas.getContext('2d').drawImage(img, 0, 0);
        URL.revokeObjectURL(img.src);
        return canvas;
    }

    if (onStatus) onStatus('Running AI background removal…');
    const results = await new Promise((resolve) => {
        selfieSegmentation.onResults(resolve);
        selfieSegmentation.send({ image: img });
    });

    if (!results || !results.segmentationMask) {
        URL.revokeObjectURL(img.src);
        throw new Error('Segmentation failed — no mask.');
    }

    const maskCanvas = maskToCanvas(results.segmentationMask, W, H);
    if (!hasPerson(maskCanvas)) {
        URL.revokeObjectURL(img.src);
        throw new Error('No person detected. If you just want to crop the image as-is, check "Keep original background".');
    }

    const personOnly = document.createElement('canvas');
    personOnly.width = W;
    personOnly.height = H;
    const ctx = personOnly.getContext('2d');
    ctx.drawImage(img, 0, 0);
    ctx.globalCompositeOperation = 'destination-in';
    ctx.drawImage(maskCanvas, 0, 0);
    ctx.globalCompositeOperation = 'source-over';

    URL.revokeObjectURL(img.src);
    return personOnly;
}

function buildPassportPhoto(personCanvas, format, bgColor) {
    const targetW = format.px;
    const targetH = Math.round(targetW * (format.heightMm / format.widthMm));

    const srcAspect = personCanvas.width / personCanvas.height;
    const tgtAspect = targetW / targetH;

    let sx, sy, sw, sh;
    if (srcAspect > tgtAspect) {
        sh = personCanvas.height;
        sw = personCanvas.height * tgtAspect;
        sx = (personCanvas.width - sw) / 2;
        sy = 0;
    } else {
        sw = personCanvas.width;
        sh = personCanvas.width / tgtAspect;
        sx = 0;
        sy = Math.max(0, (personCanvas.height - sh) * 0.25);
    }

    const out = document.createElement('canvas');
    out.width = targetW;
    out.height = targetH;
    const ctx = out.getContext('2d');

    if (bgColor) {
        ctx.fillStyle = bgColor;
        ctx.fillRect(0, 0, targetW, targetH);
    }

    ctx.drawImage(personCanvas, sx, sy, sw, sh, 0, 0, targetW, targetH);
    return out;
}

// Calculates the best grid layout for the given paper size and photo aspect ratio
function calculateBestGrid(paperW, paperH, photoW, photoH, margin, gap) {
    const availW = paperW - (margin * 2);
    const availH = paperH - (margin * 2);

    // Try different column/row combinations and pick the one that fits the most
    let best = { cols: 0, rows: 0, count: 0 };

    // Assume a reasonable maximum (photos are usually no smaller than 300px wide on paper)
    const maxCols = Math.max(1, Math.floor(availW / (photoW + gap)));
    const maxRows = Math.max(1, Math.floor(availH / (photoH + gap)));

    for (let cols = 1; cols <= maxCols + 1; cols++) {
        for (let rows = 1; rows <= maxRows + 1; rows++) {
            const cellW = (availW - (gap * (cols - 1))) / cols;
            const cellH = (availH - (gap * (rows - 1))) / rows;

            // Check if the photo fits in the cell (preserving aspect ratio)
            const photoAspect = photoW / photoH;
            const cellAspect = cellW / cellH;
            
            let drawW, drawH;
            if (photoAspect > cellAspect) {
                drawW = cellW;
                drawH = cellW / photoAspect;
            } else {
                drawH = cellH;
                drawW = cellH * photoAspect;
            }

            if (drawW <= cellW + 0.5 && drawH <= cellH + 0.5) {
                const count = cols * rows;
                if (count > best.count) {
                    best = { cols, rows, count };
                }
            }
        }
    }

    // Fallback: at least 1 photo
    if (best.count === 0) {
        best = { cols: 1, rows: 1, count: 1 };
    }

    return best;
}

function buildPrintSheet(photoCanvas, paperKey) {
    const paper = PAPER_SIZES[paperKey] || PAPER_SIZES['4x6'];
    const DPI = paper.dpi || 300;
    const SHEET_W = Math.round(paper.w * DPI);
    const SHEET_H = Math.round(paper.h * DPI);
    const MARGIN = Math.round(0.25 * DPI); // 0.25 inch margin
    const GAP = Math.round(0.1 * DPI);    // 0.1 inch gap

    const sheet = document.createElement('canvas');
    sheet.width = SHEET_W;
    sheet.height = SHEET_H;
    const ctx = sheet.getContext('2d');

    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, SHEET_W, SHEET_H);

    const grid = calculateBestGrid(SHEET_W, SHEET_H, photoCanvas.width, photoCanvas.height, MARGIN, GAP);

    const availW = SHEET_W - (MARGIN * 2);
    const availH = SHEET_H - (MARGIN * 2);
    const cellW = (availW - (GAP * (grid.cols - 1))) / grid.cols;
    const cellH = (availH - (GAP * (grid.rows - 1))) / grid.rows;

    const photoAspect = photoCanvas.width / photoCanvas.height;
    const cellAspect = cellW / cellH;
    
    let drawW, drawH;
    if (photoAspect > cellAspect) {
        drawW = cellW;
        drawH = cellW / photoAspect;
    } else {
        drawH = cellH;
        drawW = cellH * photoAspect;
    }

    for (let row = 0; row < grid.rows; row++) {
        for (let col = 0; col < grid.cols; col++) {
            const cellX = MARGIN + col * (cellW + GAP);
            const cellY = MARGIN + row * (cellH + GAP);
            const offsetX = cellX + (cellW - drawW) / 2;
            const offsetY = cellY + (cellH - drawH) / 2;

            ctx.strokeStyle = '#CCCCCC';
            ctx.lineWidth = 1;
            ctx.setLineDash([6, 6]);
            ctx.strokeRect(offsetX, offsetY, drawW, drawH);
            ctx.setLineDash([]);

            ctx.drawImage(photoCanvas, offsetX, offsetY, drawW, drawH);
        }
    }

    // Return sheet + metadata for display
    return { sheet, count: grid.count, cols: grid.cols, rows: grid.rows, paperKey };
}

// ── Preview Logic ─────────────────────────────────────────────

let previewState = { file: null, personCanvas: null, photoCanvas: null, lastKeepBg: null };

async function updatePreview() {
    const wrap = document.getElementById('passportPreviewWrap');
    const container = document.getElementById('passportPreviewContainer');
    const msg = document.getElementById('passportPreviewMsg');
    const countrySelect = document.getElementById('passportCountry');
    const bgColorInput = document.getElementById('passportBgColor');
    const printCheck = document.getElementById('passportPrintSheet');
    const keepBgCheck = document.getElementById('passportKeepBg');
    const paperSelect = document.getElementById('passportPaperSize');
    const bgColorHint = document.getElementById('bgColorHint');

    if (!wrap || !container) return;

    if (!previewState.file) {
        wrap.hidden = true;
        return;
    }

    wrap.hidden = false;
    container.innerHTML = '<p style="padding:2rem;color:#64748B;font-size:0.9rem;">Generating preview…</p>';
    msg.textContent = '';
    msg.style.color = 'var(--color-muted)';

    try {
        const keepBg = keepBgCheck && keepBgCheck.checked;

        if (bgColorHint) {
            bgColorHint.textContent = keepBg 
                ? "Ignored (original background is kept)" 
                : "Default is white for official use";
            bgColorInput.disabled = keepBg;
            bgColorInput.style.opacity = keepBg ? "0.4" : "1";
        }

        if (!keepBg && !selfieSegmentation) {
            container.innerHTML = '<p style="padding:2rem;color:#64748B;font-size:0.9rem;">Loading AI model (2 MB, one-time)…</p>';
            await ensureModel();
        }

        if (!previewState.personCanvas || previewState.lastKeepBg !== keepBg) {
            previewState.personCanvas = await extractImage(previewState.file, keepBg);
            previewState.lastKeepBg = keepBg;
        }

        const format = COUNTRY_FORMATS[countrySelect.value] || COUNTRY_FORMATS['us'];
        const bgColor = keepBg ? null : (bgColorInput.value || '#FFFFFF');

        previewState.photoCanvas = buildPassportPhoto(previewState.personCanvas, format, bgColor);

        const showPrintSheet = printCheck && printCheck.checked;
        let displayCanvas, previewText;

        if (showPrintSheet) {
            const paperKey = paperSelect ? paperSelect.value : '4x6';
            const result = buildPrintSheet(previewState.photoCanvas, paperKey);
            displayCanvas = result.sheet;
            previewText = `Print sheet: ${result.count} photos on ${paperKey.toUpperCase()} (${result.cols}×${result.rows}) — ready to cut.`;
        } else {
            displayCanvas = previewState.photoCanvas;
            previewText = `Single photo: ${format.widthMm}×${format.heightMm} mm at ${format.px}px wide.`;
        }

        const maxWidth = 280;
        const scale = Math.min(1, maxWidth / displayCanvas.width);
        const previewCanvas = document.createElement('canvas');
        previewCanvas.width  = Math.round(displayCanvas.width  * scale);
        previewCanvas.height = Math.round(displayCanvas.height * scale);
        const pctx = previewCanvas.getContext('2d');
        pctx.imageSmoothingQuality = 'high';
        pctx.drawImage(displayCanvas, 0, 0, previewCanvas.width, previewCanvas.height);

        container.innerHTML = '';
        container.appendChild(previewCanvas);

        msg.textContent = previewText;
    } catch (err) {
        container.innerHTML = '';
        msg.textContent = err.message || 'Preview failed.';
        msg.style.color = 'var(--color-warning)';
    }
}

function wirePreviewListeners() {
    const countrySelect = document.getElementById('passportCountry');
    const bgColorInput = document.getElementById('passportBgColor');
    const printCheck = document.getElementById('passportPrintSheet');
    const keepBgCheck = document.getElementById('passportKeepBg');
    const paperSelect = document.getElementById('passportPaperSize');

    if (countrySelect) {
        countrySelect.addEventListener('change', () => {
            previewState.personCanvas = null; 
            updatePreview();
        });
    }
    if (bgColorInput) {
        bgColorInput.addEventListener('input', updatePreview);
    }
    if (printCheck) {
        printCheck.addEventListener('change', () => {
            // Show/hide paper size selector
            const cfg = document.getElementById('printSheetConfig');
            if (cfg) cfg.style.display = printCheck.checked ? '' : 'none';
            updatePreview();
        });
    }
    if (keepBgCheck) {
        keepBgCheck.addEventListener('change', () => {
            previewState.personCanvas = null;
            updatePreview();
        });
    }
    if (paperSelect) {
        paperSelect.addEventListener('change', updatePreview);
    }
}

// ── Main Entry Point ──────────────────────────────────────────

window.runConversion = async function (files) {
    const status = document.getElementById('status');
    const countrySelect = document.getElementById('passportCountry');
    const bgColorInput = document.getElementById('passportBgColor');
    const printCheck = document.getElementById('passportPrintSheet');
    const keepBgCheck = document.getElementById('passportKeepBg');
    const paperSelect = document.getElementById('passportPaperSize');

    const format = COUNTRY_FORMATS[countrySelect.value] || COUNTRY_FORMATS['us'];
    const keepBg = keepBgCheck && keepBgCheck.checked;
    const bgColor = keepBg ? null : (bgColorInput.value || '#FFFFFF');
    const wantPrintSheet = printCheck.checked;
    const paperKey = paperSelect ? paperSelect.value : '4x6';

    if (!keepBg) {
        status.textContent = 'Loading portrait model (2 MB, one-time)…';
        await ensureModel();
    }

    for (const file of files) {
        status.textContent = 'Generating passport photo for ' + file.name + '…';

        try {
            const personCanvas = await extractImage(file, keepBg, msg => status.textContent = msg);
            const photoCanvas = buildPassportPhoto(personCanvas, format, bgColor);

            const photoBlob = await new Promise(r => photoCanvas.toBlob(r, 'image/jpeg', 0.92));
            const baseName = file.name.replace(/\.[^.]+$/, '');

            downloadBlob(photoBlob, `${baseName}-passport-${countrySelect.value}-${format.widthMm}x${format.heightMm}mm.jpg`);

            if (wantPrintSheet) {
                await new Promise(r => setTimeout(r, 300));
                const result = buildPrintSheet(photoCanvas, paperKey);
                const sheetBlob = await new Promise(r => result.sheet.toBlob(r, 'image/jpeg', 0.92));
                downloadBlob(sheetBlob, `${baseName}-printsheet-${paperKey}.jpg`);
            }

            previewState.file = file;
            previewState.personCanvas = personCanvas;
            previewState.photoCanvas = photoCanvas;
            await updatePreview();
        } catch (err) {
            throw err;
        }

        await new Promise(r => setTimeout(r, 200));
    }
};

function downloadBlob(blob, name) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = name;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 2000);
}

document.addEventListener('DOMContentLoaded', () => {
    wirePreviewListeners();

    // Initialize paper size visibility based on default checkbox state
    const printCheck = document.getElementById('passportPrintSheet');
    const cfg = document.getElementById('printSheetConfig');
    if (printCheck && cfg) {
        cfg.style.display = printCheck.checked ? '' : 'none';
    }

    const input = document.getElementById('fileInput');
    if (input) {
        input.addEventListener('change', () => {
            if (input.files && input.files[0]) {
                previewState.file = input.files[0];
                previewState.personCanvas = null;
                previewState.photoCanvas = null;
                updatePreview();
            }
        });
    }

    const dropZone = document.getElementById('dropZone');
    if (dropZone) {
        dropZone.addEventListener('drop', (e) => {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                previewState.file = e.dataTransfer.files[0];
                previewState.personCanvas = null;
                previewState.photoCanvas = null;
                updatePreview();
            }
        });
    }
});