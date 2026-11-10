window.runConversion = async function (files) {
    const { PDFDocument } = PDFLib;
    const src = await PDFDocument.load(await files[0].arrayBuffer());
    const from = Math.max(1, parseInt(document.getElementById('splitStart')?.value || '1', 10));
    const to   = Math.min(src.getPageCount(), parseInt(document.getElementById('splitEnd')?.value || '1', 10));

    const out = await PDFDocument.create();
    const indices = [];
    for (let i = from - 1; i <= to - 1; i++) indices.push(i);
    const pages = await out.copyPages(src, indices);
    pages.forEach(p => out.addPage(p));

    const bytes = await out.save();
    const blob  = new Blob([bytes], { type: 'application/pdf' });
    const url   = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = files[0].name.replace(/\.pdf$/i, '') + '-pages-' + from + '-' + to + '.pdf';
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 2000);
};