// Only the URL constant and the import line change
const IMGLY_ESM = 'https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.7.0/+esm';
let imglyLoaded = false;
let removeBackgroundFn = null;

async function ensureModel(onProgress) {
    if (imglyLoaded) {
        onProgress({ status: 'ready' });
        return;
    }

    onProgress({ status: 'downloading', percent: 0, message: 'Loading AI library…' });

    // Use the pre-bundled ESM build — no bare specifiers, no bundler needed
    const module = await import(IMGLY_ESM);
    removeBackgroundFn = module.removeBackground || module.default;

    imglyLoaded = true;
    onProgress({ status: 'ready' });
}

window.runConversion = async function (files) {
    const status = document.getElementById('status');

    if (!imglyLoaded) {
        status.textContent = 'Loading 66 MB AI model (one-time — cached after this)…';
        await ensureModel((state) => {
            if (state.status === 'downloading') {
                status.textContent = state.message || 'Loading AI model (66 MB, one time)…';
            }
        });
    }

    for (const file of files) {
        status.textContent = 'Removing background from ' + file.name + '…';

        const blob = await removeBackgroundFn(file, {
            model: 'isnet_quint8',  // ~40 MB model — smaller and faster
            progress: (key, current, total) => {
                if (total > 0) {
                    const pct = Math.round((current / total) * 100);
                    status.textContent = `Processing ${file.name} — ${pct}%`;
                }
            },
            output: { format: 'image/png', quality: 0.9 },
        });

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = file.name.replace(/\.[^.]+$/, '') + '-no-bg.png';
        a.click();
        setTimeout(() => URL.revokeObjectURL(url), 2000);

        await new Promise(r => setTimeout(r, 200));
    }
};