// assets/js/tools/heic-to-jpg.js
// Converts HEIC/HEIF to JPG using heic-to (libheif WASM).
// The library is loaded from CDN as an IIFE build (no bundler needed).

const HEIC_CDN = 'https://cdn.jsdelivr.net/npm/heic-to@1.2.2/dist/iife/heic-to.js';
let heicLoaded = false;

async function ensureHeicLib(onProgress) {
    if (heicLoaded) {
        onProgress && onProgress({ status: 'ready' });
        return;
    }

    if (onProgress) onProgress({ status: 'loading', message: 'Loading HEIC decoder (first time only)…' });

    await new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = HEIC_CDN;
        s.onload = resolve;
        s.onerror = () => reject(new Error('Failed to load HEIC decoder.'));
        document.head.appendChild(s);
    });

    heicLoaded = true;
    if (onProgress) onProgress({ status: 'ready' });
}

window.runConversion = async function (files) {
    const status = document.getElementById('status');

    status.textContent = 'Loading HEIC decoder…';
    await ensureHeicLib();

    for (const file of files) {
        status.textContent = 'Converting ' + file.name + '…';

        try {
            // Check if the file is actually HEIC/HEIF
            const isHeic = await HeicTo.isHeic(file);
            if (!isHeic) {
                throw new Error(
                    '"' + file.name + '" does not appear to be a HEIC/HEIF file. ' +
                    'Try an image converter instead.'
                );
            }

            // Convert to JPG
            const jpegBlob = await HeicTo({
                blob: file,
                type: 'image/jpeg',
                quality: 0.92,
            });

            // Download
            const url = URL.createObjectURL(jpegBlob);
            const a = document.createElement('a');
            a.href = url;
            a.download = file.name.replace(/\.(heic|heif)$/i, '.jpg');
            a.click();
            setTimeout(() => URL.revokeObjectURL(url), 2000);
        } catch (err) {
            throw err;
        }

        // Small pause so browsers don't drop simultaneous downloads
        await new Promise(r => setTimeout(r, 200));
    }
};