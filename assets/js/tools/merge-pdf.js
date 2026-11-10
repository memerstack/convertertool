window.runConversion = async function (files) {
    const { PDFDocument } = PDFLib;
    const merged = await PDFDocument.create();
    for (const file of files) {
        const src  = await PDFDocument.load(await file.arrayBuffer());
        const pages = await merged.copyPages(src, src.getPageIndices());
        pages.forEach(p => merged.addPage(p));
    }
    const bytes = await merged.save();
    const blob  = new Blob([bytes], { type: 'application/pdf' });
    const url   = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'merged.pdf';
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 2000);
};