// assets/js/tools/_shared.js
(function () {
    'use strict';

    const cfg    = window.CONVERTER_TOOL || {};
    const input  = document.getElementById('fileInput');
    const zone   = document.getElementById('dropZone');
    const listUl = document.getElementById('fileListUl');
    const listWrap = document.getElementById('fileList');
    const listCount = document.getElementById('fileListCount');
    const clearBtn  = document.getElementById('clearAllBtn');
    const btn    = document.getElementById('convertBtn');
    const status = document.getElementById('status');

    if (!input || !zone || !btn || !listUl) return;

    /** @type {File[]} — the working set of files */
    let selected = [];

    /* ── Helpers ─────────────────────────────── */

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        if (bytes < 1024 * 1024 * 1024) return (bytes / 1048576).toFixed(2) + ' MB';
        return (bytes / 1073741824).toFixed(2) + ' GB';
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function setStatus(msg, kind) {
        if (!status) return;
        status.textContent = msg || '';
        status.className = 'tool-status' + (kind ? ' is-' + kind : '');
    }

    /* ── UI rendering ────────────────────────── */

    function renderList() {
        if (selected.length === 0) {
            listWrap.hidden = true;
            listUl.innerHTML = '';
            listCount.textContent = '';
            btn.disabled = true;
            return;
        }

        listWrap.hidden = false;
        btn.disabled = false;

        listCount.textContent =
            selected.length === 1
                ? '1 file selected'
                : selected.length + ' files selected';

        listUl.innerHTML = selected.map((f, i) => `
            <li class="file-item" data-index="${i}">
                <span class="file-item-icon" aria-hidden="true">
                    ${escapeHtml((f.name.split('.').pop() || '?').toUpperCase().slice(0, 4))}
                </span>
                <span class="file-item-name" title="${escapeHtml(f.name)}">${escapeHtml(f.name)}</span>
                <span class="file-item-size">${formatSize(f.size)}</span>
                <button type="button"
                        class="file-item-remove"
                        aria-label="Remove ${escapeHtml(f.name)}"
                        data-remove="${i}">✕</button>
            </li>
        `).join('');
    }

    /* ── Mutations ───────────────────────────── */

    function addFiles(list) {
        // De-duplicate by name + size (same file picked twice)
        const existing = new Set(selected.map(f => f.name + '::' + f.size));
        const incoming = Array.from(list || []);
        for (const f of incoming) {
            if (!existing.has(f.name + '::' + f.size)) {
                selected.push(f);
                existing.add(f.name + '::' + f.size);
            }
        }
        renderList();
        setStatus('');
    }

    function removeAt(index) {
        selected.splice(index, 1);
        renderList();
        setStatus('');
    }

    function clearAll() {
        selected = [];
        input.value = '';
        renderList();
        setStatus('');
    }

    /* ── Wire up ─────────────────────────────── */

    // Open picker
    zone.addEventListener('click', (ev) => {
        if (ev.target.tagName !== 'INPUT') input.click();
    });
    zone.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter' || ev.key === ' ') {
            ev.preventDefault();
            input.click();
        }
    });

    // File picker — append to the working set
    input.addEventListener('change', () => {
        if (input.files && input.files.length) {
            addFiles(input.files);
        }
        // Reset so picking the same file again still fires change
        input.value = '';
    });

    // Drag & drop
    ['dragenter', 'dragover'].forEach(ev =>
        zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('is-drag'); })
    );
    ['dragleave', 'drop'].forEach(ev =>
        zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('is-drag'); })
    );
    zone.addEventListener('drop', (e) => {
        if (e.dataTransfer && e.dataTransfer.files) {
            addFiles(e.dataTransfer.files);
        }
    });

    // Per-file remove (event delegation on the list)
    listUl.addEventListener('click', (ev) => {
        const btnEl = ev.target.closest('[data-remove]');
        if (!btnEl) return;
        const idx = parseInt(btnEl.getAttribute('data-remove'), 10);
        if (!isNaN(idx)) removeAt(idx);
    });

    // Clear all
    if (clearBtn) {
        clearBtn.addEventListener('click', clearAll);
    }

    // Convert
    btn.addEventListener('click', async () => {
        if (selected.length === 0) return;
        if (typeof window.runConversion !== 'function') {
            setStatus('Tool is not ready. Please refresh the page.', 'error');
            return;
        }

        btn.disabled = true;
        const labelBefore = btn.textContent;
        btn.textContent = 'Converting…';
        setStatus('Converting locally — nothing is uploaded.', 'info');

        // Snapshot so removal during conversion doesn't break the run
        const toConvert = selected.slice();

        try {
            await window.runConversion(toConvert);
            setStatus('Done — your file has been downloaded.', 'success');
        } catch (err) {
            console.error(err);
            setStatus(err && err.message ? err.message : 'Conversion failed.', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = labelBefore;
        }
    });

    // Quality slider live label (used by image-compress)
    const quality = document.getElementById('qualityRange');
    const qualityVal = document.getElementById('qualityVal');
    if (quality && qualityVal) {
        quality.addEventListener('input', () => {
            qualityVal.textContent = quality.value + '%';
        });
    }

    // Initial render
    renderList();
})();