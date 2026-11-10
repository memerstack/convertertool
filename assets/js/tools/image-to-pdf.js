// assets/js/tools/image-to-pdf.js
// Image → PDF with optional page format (A4, Letter, etc.)
// Uses pdf-lib (window.PDFLib) — no server.

/* ─────────────────────────────────────────────
   Paper sizes (in PDF points — 1 pt = 1/72 inch)
   Source: ISO 216 + ANSI/ASME Y14.1
   ───────────────────────────────────────────── */
const PAPER_SIZES = {
    A0:      { w: 2384, h: 3370 },   // 841 × 1189 mm
    A1:      { w: 1684, h: 2384 },   // 594 × 841 mm
    A2:      { w: 1191, h: 1684 },   // 420 × 594 mm
    A3:      { w: 842,  h: 1191 },   // 297 × 420 mm
    A4:      { w: 595,  h: 842  },   // 210 × 297 mm
    A5:      { w: 420,  h: 595  },   // 148 × 210 mm
    A6:      { w: 298,  h: 420  },   // 105 × 148 mm
    LETTER:  { w: 612,  h: 792  },   // 8.5 × 11 in
    LEGAL:   { w: 612,  h: 1008 },   // 8.5 × 14 in
    TABLOID: { w: 792,  h: 1224 },   // 11 × 17 in
    B5:      { w: 499,  h: 709  },   // 176 × 250 mm
};

/* ─────────────────────────────────────────────
   Read tool options from the UI
   ───────────────────────────────────────────── */
function getOptions() {
    const modeEl   = document.getElementById('pdfPageMode');
    const sizeEl   = document.getElementById('pdfPaperSize');
    const oriEl    = document.getElementById('pdfOrientation');
    const marginEl = document.getElementById('pdfMargin');

    return {
        mode:        modeEl   ? modeEl.value   : 'format',
        paperKey:    sizeEl   ? sizeEl.value   : 'A4',
        orientation: oriEl    ? oriEl.value    : 'auto',
        margin:      marginEl ? parseInt(marginEl.value, 10) : 24,
    };
}

/* ─────────────────────────────────────────────
   Image loader (returns natural dimensions)
   ───────────────────────────────────────────── */
function loadImage(file) {
    return new Promise((res, rej) => {
        const img = new Image();
        img.onload = () => res(img);
        img.onerror = () => rej(new Error('Could not load image: ' + file.name));
        img.src = URL.createObjectURL(file);
    });
}

/* ─────────────────────────────────────────────
   Embed the image into the PDF
   Handles PNG (with alpha), JPG (no alpha),
   and WEBP (which pdf-lib cannot embed natively
   → we convert to PNG via canvas first).
   ───────────────────────────────────────────── */
async function embedImage(pdfDoc, file) {
    const bytes = await file.arrayBuffer();

    if (file.type === 'image/png') {
        return { embedded: await pdfDoc.embedPng(bytes), width: null, height: null };
    }
    if (file.type === 'image/jpeg') {
        return { embedded: await pdfDoc.embedJpg(bytes), width: null, height: null };
    }

    // WEBP (or anything else) → re-encode via canvas to PNG
    const img = await loadImage(file);
    const canvas = document.createElement('canvas');
    canvas.width  = img.naturalWidth;
    canvas.height = img.naturalHeight;
    canvas.getContext('2d').drawImage(img, 0, 0);
    const pngDataUrl = canvas.toDataURL('image/png');
    const pngBytes = Uint8Array.from(
        atob(pngDataUrl.split(',')[1]),
        c => c.charCodeAt(0)
    );
    URL.revokeObjectURL(img.src);

    return { embedded: await pdfDoc.embedPng(pngBytes), width: null, height: null };
}

/* ─────────────────────────────────────────────
   Compute the rectangle to fit an image inside a page
   Preserves aspect ratio, centered, with margin.
   Returns { x, y, width, height } in PDF points.
   ───────────────────────────────────────────── */
function fitRect(imageW, imageH, pageW, pageH, margin) {
    const availW = pageW - margin * 2;
    const availH = pageH - margin * 2;

    const imgAspect  = imageW / imageH;
    const areaAspect = availW / availH;

    let w, h;
    if (imgAspect > areaAspect) {
        // image is wider than the available area → constrain by width
        w = availW;
        h = availW / imgAspect;
    } else {
        // image is taller → constrain by height
        h = availH;
        w = availH * imgAspect;
    }

    return {
        x: margin + (availW - w) / 2,
        y: margin + (availH - h) / 2,
        width:  w,
        height: h,
    };
}

/* ─────────────────────────────────────────────
   Entry point (called by _shared.js)
   ───────────────────────────────────────────── */
window.runConversion = async function (files) {
    const { PDFDocument } = PDFLib;
    const opts = getOptions();
    const pdfDoc = await PDFDocument.create();

    // Set metadata (nice touch in Acrobat → File > Properties)
    pdfDoc.setProducer('ConverterTool.in');
    pdfDoc.setCreator('ConverterTool.in Image to PDF');

    for (const file of files) {
        const { embedded } = await embedImage(pdfDoc, file);
        const imgW = embedded.width;
        const imgH = embedded.height;

        if (opts.mode === 'native') {
            // ── Native size: page = image dimensions (current behavior) ──
            const page = pdfDoc.addPage([imgW, imgH]);
            page.drawImage(embedded, { x: 0, y: 0, width: imgW, height: imgH });
            continue;
        }

        // ── Page format mode ──
        const paper = PAPER_SIZES[opts.paperKey] || PAPER_SIZES.A4;
        let pageW = paper.w;
        let pageH = paper.h;

        // Orientation
        if (opts.orientation === 'landscape') {
            [pageW, pageH] = [pageH, pageW];
        } else if (opts.orientation === 'auto') {
            // Match image orientation: if image is wider than tall, use landscape
            const imageIsLandscape = imgW > imgH;
            if (imageIsLandscape && pageH > pageW) {
                [pageW, pageH] = [pageH, pageW];
            }
        }

        const page = pdfDoc.addPage([pageW, pageH]);

        const rect = fitRect(imgW, imgH, pageW, pageH, opts.margin);
        page.drawImage(embedded, {
            x: rect.x,
            y: rect.y,
            width: rect.width,
            height: rect.height,
        });
    }

    const bytes = await pdfDoc.save();

    // Download
    const blob = new Blob([bytes], { type: 'application/pdf' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = files.length === 1
        ? files[0].name.replace(/\.[^.]+$/, '') + '.pdf'
        : 'images.pdf';
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 2000);
};