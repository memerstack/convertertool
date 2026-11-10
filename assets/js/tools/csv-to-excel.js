window.runConversion = async function (files) {
    for (const file of files) {
        const text = await file.text();
        const wb = XLSX.read(text, { type: 'string' });
        const out = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
        const blob = new Blob([out], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        });
        downloadBlob(blob, file.name.replace(/\.csv$/i, '.xlsx'));
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