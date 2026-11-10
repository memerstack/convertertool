window.runConversion = async function (files) {
    // Configure the PDF.js worker once
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';

    for (const file of files) {
        // ── 1. Load the PDF ────────────────────────────────
        const buf = await file.arrayBuffer();
        const pdf = await pdfjsLib.getDocument({ data: buf }).promise;

        // ── 2. Extract text page by page ───────────────────
        const pages = [];
        let totalChars = 0;

        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            const content = await page.getTextContent();

            // Join items with spaces, split per line by transform.y
            const pageText = content.items
                .map(it => it.str || '')
                .join(' ')
                .replace(/\s+/g, ' ')
                .trim();

            totalChars += pageText.length;
            pages.push(pageText);
        }

        // ── 3. Guard: nothing extracted? ───────────────────
        if (totalChars === 0) {
            throw new Error(
                'No extractable text found in "' + file.name + '". ' +
                'This usually means the PDF is scanned (image-based) or uses ' +
                'fonts that cannot be decoded. Try an OCR tool first, or ' +
                'open the PDF in a reader to verify it contains selectable text.'
            );
        }

        // ── 4. Build the Word document ─────────────────────
        const { Document, Packer, Paragraph, TextRun } = docx;

        const paragraphs = [];
        pages.forEach((pageText, idx) => {
            // Skip empty pages (don't add blank paragraphs)
            if (!pageText) return;

            // One paragraph per page keeps structure readable
            paragraphs.push(new Paragraph({
                children: [new TextRun({ text: pageText, size: 22 })],
                spacing: { after: 200 },
            }));

            // Page break between pages (not after the last)
            if (idx < pages.length - 1 && pages.slice(idx + 1).some(Boolean)) {
                paragraphs.push(new Paragraph({ text: '', pageBreakBefore: true }));
            }
        });

        // Fallback: if somehow no paragraphs, don't silently produce blank
        if (paragraphs.length === 0) {
            paragraphs.push(new Paragraph({
                children: [new TextRun({
                    text: '(No text could be extracted from this PDF.)',
                    italics: true,
                })],
            }));
        }

        const doc = new Document({
            creator: 'ConverterTool.in',
            description: 'Converted from PDF (client-side)',
            sections: [{ properties: {}, children: paragraphs }],
        });

        // ── 5. Save ────────────────────────────────────────
        const blob = await Packer.toBlob(doc);
        downloadBlob(blob, file.name.replace(/\.pdf$/i, '.docx'));

        // Small pause between files so browsers don't drop downloads
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