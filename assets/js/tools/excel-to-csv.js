window.runConversion = async function (files) {
    for (const file of files) {
        const data = await file.arrayBuffer();
        const wb   = XLSX.read(data, { type: 'array' });
        const sheet = wb.Sheets[wb.SheetNames[0]];
        const csv = XLSX.utils.sheet_to_csv(sheet);
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        downloadBlob(blob, file.name.replace(/\.(xlsx|xls)$/i, '.csv'));
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