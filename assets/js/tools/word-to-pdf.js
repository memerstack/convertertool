window.runConversion = async function (files) {
    if (files.length > 5) {
        throw new Error('Please convert up to 5 Word files at a time — remove the extras first.');
    }
    for (const file of files) {
        await convertOne(file);
        await new Promise(r => setTimeout(r, 300));
    }
};

async function convertOne(file) {
    const arrayBuffer = await file.arrayBuffer();
    const { value: html } = await mammoth.convertToHtml({ arrayBuffer });

    const container = document.createElement('div');
    container.innerHTML = html;
    container.style.cssText = 'position:absolute;left:-9999px;top:0;width:794px;padding:40px;font-family:Georgia,serif;font-size:14px;line-height:1.6;color:#111;background:#fff;';
    document.body.appendChild(container);

    try {
        const canvas = await html2canvas(container, { scale: 2, backgroundColor: '#fff' });
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        const pageWidthMm  = pdf.internal.pageSize.getWidth();
        const pageHeightMm = pdf.internal.pageSize.getHeight();
        const imgWidthMm   = pageWidthMm;
        const pxPerMm = canvas.width / pageWidthMm;
        const pageHeightPx = pageHeightMm * pxPerMm;

        let yOffsetPx = 0;
        while (yOffsetPx < canvas.height) {
            const sliceHeightPx = Math.min(pageHeightPx, canvas.height - yOffsetPx);
            const sliceCanvas = document.createElement('canvas');
            sliceCanvas.width  = canvas.width;
            sliceCanvas.height = sliceHeightPx;
            sliceCanvas.getContext('2d').drawImage(
                canvas, 0, yOffsetPx, canvas.width, sliceHeightPx, 0, 0, canvas.width, sliceHeightPx
            );
            if (yOffsetPx > 0) pdf.addPage();
            pdf.addImage(sliceCanvas.toDataURL('image/jpeg', 0.92), 'JPEG', 0, 0, imgWidthMm, sliceHeightPx / pxPerMm);
            yOffsetPx += sliceHeightPx;
        }

        downloadBlob(pdf.output('blob'), file.name.replace(/\.docx$/i, '.pdf'));
    } finally {
        document.body.removeChild(container);
    }
}

function downloadBlob(blob, name) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = name;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 2000);
}