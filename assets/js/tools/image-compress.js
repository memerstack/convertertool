window.runConversion = async function (files) {
    const quality = (parseInt(document.getElementById('qualityRange')?.value || '75', 10)) / 100;

    for (const file of files) {
        const img = await loadImage(file);
        const canvas = document.createElement('canvas');
        canvas.width  = img.naturalWidth;
        canvas.height = img.naturalHeight;
        canvas.getContext('2d').drawImage(img, 0, 0);

        const blob = await new Promise(res => canvas.toBlob(res, 'image/jpeg', quality));
        downloadBlob(blob, file.name.replace(/\.(png|jpe?g)$/i, '') + '-compressed.jpg');

        // small delay so browsers don't drop simultaneous downloads
        await new Promise(r => setTimeout(r, 200));
    }
};

function loadImage(file) {
    return new Promise((res, rej) => {
        const img = new Image();
        img.onload = () => res(img);
        img.onerror = rej;
        img.src = URL.createObjectURL(file);
    });
}

function downloadBlob(blob, name) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = name;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 2000);
}