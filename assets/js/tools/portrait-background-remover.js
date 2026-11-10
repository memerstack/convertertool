// assets/js/tools/portrait-background-remover.js
// Uses MediaPipe Selfie Segmentation — 2 MB model, no server.

const MEDIAPIPE_CDN = 'https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation@0.1/';
let selfieSegmentation = null;

/* ─────────────────────────────────────────────
   1. Model loading
   ───────────────────────────────────────────── */

async function ensureModel(onProgress) {
    if (selfieSegmentation) {
        onProgress({ status: 'ready' });
        return;
    }

    onProgress({ status: 'loading', percent: 30, message: 'Loading MediaPipe…' });

    if (!window.SelfieSegmentation) {
        await new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = MEDIAPIPE_CDN + 'selfie_segmentation.js';
            s.crossOrigin = 'anonymous';
            s.onload = resolve;
            s.onerror = () => reject(new Error('Failed to load MediaPipe script.'));
            document.head.appendChild(s);
        });
    }

    onProgress({ status: 'loading', percent: 80, message: 'Initializing model…' });

    selfieSegmentation = new window.SelfieSegmentation({
        locateFile: (file) => MEDIAPIPE_CDN + file,
    });
    selfieSegmentation.setOptions({ modelSelection: 1 }); // 1 = landscape (fast)

    await selfieSegmentation.initialize();
    onProgress({ status: 'ready' });
}

/* ─────────────────────────────────────────────
   2. Draw the mask onto a real canvas
   This is the ONLY way to inspect the mask.
   `segmentationMask` is a WebGLTexture / ImageBitmap,
   NOT a canvas — it has no `.getContext()` method.
   ───────────────────────────────────────────── */

function maskToCanvas(segmentationMask, width, height) {
    if (!segmentationMask) {
        throw new Error('Segmentation failed — no mask returned.');
    }

    const canvas = document.createElement('canvas');
    canvas.width  = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    // This is the key: drawImage handles WebGLTexture / ImageBitmap / HTMLCanvasElement
    ctx.drawImage(segmentationMask, 0, 0, width, height);
    return canvas;
}

/* ─────────────────────────────────────────────
   3. Detect whether a person was found
   MediaPipe paints the person with alpha=255 and
   everything else with alpha=0 (in the canvas after
   drawImage). But depending on version/backend the
   polarity can flip, so we just check "is the mask
   non-uniform?" — if every pixel is identical, no
   person was found.
   ───────────────────────────────────────────── */

function hasPerson(maskCanvas) {
    const { width, height } = maskCanvas;
    const data = maskCanvas.getContext('2d').getImageData(0, 0, width, height).data;

    let minAlpha = 255;
    let maxAlpha = 0;

    // Sample every 16th pixel — enough signal, 16× faster
    for (let i = 3; i < data.length; i += 64) {
        const a = data[i];
        if (a < minAlpha) minAlpha = a;
        if (a > maxAlpha) maxAlpha = a;
    }

    // A uniform mask means no person (all 0 or all 255)
    return (maxAlpha - minAlpha) > 30;
}

/* ─────────────────────────────────────────────
   4. Core conversion
   ───────────────────────────────────────────── */

async function removePortraitBackground(file) {
    // Load the image
    const img = await new Promise((res, rej) => {
        const i = new Image();
        i.onload = () => res(i);
        i.onerror = () => rej(new Error('Could not load image.'));
        i.src = URL.createObjectURL(file);
    });

    const W = img.naturalWidth;
    const H = img.naturalHeight;

    // Run segmentation
    const results = await new Promise((resolve) => {
        selfieSegmentation.onResults(resolve);
        selfieSegmentation.send({ image: img });
    });

    if (!results || !results.segmentationMask) {
        URL.revokeObjectURL(img.src);
        throw new Error('Segmentation returned no mask.');
    }

    // Inspect the mask via a real canvas
    const maskCanvas = maskToCanvas(results.segmentationMask, W, H);
    const personFound = hasPerson(maskCanvas);

    URL.revokeObjectURL(img.src);

    if (!personFound) {
        throw new Error(
            'No person detected in this photo. ' +
            'If you are trying to remove the background from an object, animal, or product, ' +
            'use our general Background Remover instead.'
        );
    }

    // Compose the final image: original photo, masked by segmentation
    const out = document.createElement('canvas');
    out.width  = W;
    out.height = H;
    const ctx = out.getContext('2d');

    ctx.drawImage(img, 0, 0);
    ctx.globalCompositeOperation = 'destination-in';
    ctx.drawImage(maskCanvas, 0, 0);
    ctx.globalCompositeOperation = 'source-over';

    return out;
}

/* ─────────────────────────────────────────────
   5. Entry point (called by _shared.js)
   ───────────────────────────────────────────── */

window.runConversion = async function (files) {
    const status = document.getElementById('status');

    if (!selfieSegmentation) {
        status.textContent = 'Loading 2 MB portrait model (one-time)…';
        await ensureModel((state) => {
            status.textContent = state.message || 'Loading…';
        });
    }

    for (const file of files) {
        status.textContent = 'Processing ' + file.name + '…';

        try {
            const canvas = await removePortraitBackground(file);
            const blob = await new Promise(r => canvas.toBlob(r, 'image/png'));

            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = file.name.replace(/\.[^.]+$/, '') + '-no-bg.png';
            a.click();
            setTimeout(() => URL.revokeObjectURL(url), 2000);
        } catch (err) {
            // Re-throw so _shared.js shows it in the red status bar
            throw err;
        }

        await new Promise(r => setTimeout(r, 200));
    }
};