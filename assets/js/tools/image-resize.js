window.runConversion = async function (files) {
    const targetW = parseInt(document.getElementById('resizeWidth')?.value || '0', 10);
    const targetH = parseInt(document.getElementById('resizeHeight')?.value || '0', 10);

    for (const file of files) {
        const img = await new Promise((res, rej) => {
            const i = new Image();
            i.onload = () => res(i);
            i.onerror = rej;
            i.src = URL.createObjectURL(file);
        });

        let w = targetW || Math.round(img.naturalWidth * (targetH / img.naturalHeight));
        let h = targetH || Math.round(img.naturalHeight * (targetW / img.naturalWidth));
        if (!w || !h) { w = img.naturalWidth; h = img.naturalHeight; }

        const canvas = document.createElement('canvas');
        canvas.width = w; canvas.height = h;
        canvas.getContext('2d').drawImage(img, 0, 0, w, h);

        const blob = await new Promise(r => canvas.toBlob(r, 'image/png'));
        downloadBlob(blob, file.name.replace(/\.[^.]+$/, '') + '-' + w + 'x' + h + '.png');
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