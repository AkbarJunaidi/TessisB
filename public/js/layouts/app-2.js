// Helper UI global: popup konfirmasi (form + AppUI.confirm), toast, kunci tombol submit, aria-label tombol tutup.
(function () {
    const modalEl = document.getElementById('appConfirmModal');
    const okBtn = document.getElementById('appConfirmOk');
    const textEl = document.getElementById('appConfirmText');
    const titleEl = document.getElementById('appConfirmTitleText');
    const iconEl = document.getElementById('appConfirmIcon');
    const changesEl = document.getElementById('appConfirmChanges');
    let pending = null;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    const TAGS = {
        add: ['Ditambah', 'app-change-tag--add'],
        remove: ['Dihapus', 'app-change-tag--remove'],
        change: ['Diubah', 'app-change-tag--change']
    };

    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    // Daftar perubahan: [{ title, items: [{ type, label?, text?, from?, to? }] }] (lihat form-changes.js).
    function renderChanges(groups) {
        changesEl.textContent = '';
        changesEl.classList.toggle('d-none', !groups || !groups.length);
        if (!groups) return;

        groups.forEach(function (group) {
            const wrap = el('div', 'app-changes-group');
            const list = el('ul', 'app-changes-list');
            wrap.appendChild(el('div', 'app-changes-title', group.title));

            group.items.forEach(function (item) {
                const row = el('li');
                const body = el('span');
                row.appendChild(el('span', 'app-change-tag ' + TAGS[item.type][1], TAGS[item.type][0]));

                if (item.label) body.appendChild(el('strong', '', item.label + ': '));
                if (item.type === 'change') {
                    body.appendChild(el('span', 'app-change-from', item.from));
                    body.appendChild(el('i', 'bi bi-arrow-right mx-1'));
                    body.appendChild(document.createTextNode(item.to));
                } else {
                    body.appendChild(document.createTextNode(item.text));
                }

                row.appendChild(body);
                list.appendChild(row);
            });

            wrap.appendChild(list);
            changesEl.appendChild(wrap);
        });
    }

    // spec: { title, message, label, danger, changes }
    function open(spec) {
        titleEl.textContent = spec.title;
        iconEl.className = 'bi ' + (spec.danger ? 'bi-exclamation-triangle-fill text-danger' : 'bi-question-circle-fill text-primary');
        textEl.textContent = spec.message;
        okBtn.textContent = spec.label;
        okBtn.className = 'btn ' + (spec.danger ? 'btn-danger' : 'btn-primary');
        renderChanges(spec.changes);
        modal.show();
    }

    function methodOf(form) {
        const spoof = form.querySelector('input[name="_method"]:not(:disabled)');
        return ((spoof && spoof.value) || form.getAttribute('method') || 'get').toLowerCase();
    }

    function buttonText(form, submitter) {
        const btn = submitter || form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
        if (!btn) return '';
        const text = (btn.tagName === 'INPUT' ? btn.value : btn.textContent).replace(/\s+/g, ' ').trim();
        return text || btn.getAttribute('title') || btn.getAttribute('aria-label') || '';
    }

    // Aturan: semua form non-GET minta konfirmasi, kecuali data-no-confirm atau form di dalam modal
    // (modal itu sendiri sudah berupa popup). data-confirm eksplisit selalu dihormati.
    function specFor(form, submitter) {
        const explicit = form.getAttribute('data-confirm');
        const method = methodOf(form);
        if (!explicit && (method === 'get' || form.hasAttribute('data-no-confirm') || form.closest('.modal'))) return null;

        const isDelete = method === 'delete';
        const danger = isDelete || form.hasAttribute('data-confirm-danger');
        const text = buttonText(form, submitter);
        const changes = form.hasAttribute('data-track-changes') && window.AppFormChanges ? window.AppFormChanges.diff(form) : null;
        const hasChanges = !!(changes && changes.length);

        let message = explicit || (isDelete ? 'Yakin ingin menghapus data ini?' : 'Pastikan data sudah benar sebelum dilanjutkan.');
        if (!explicit && changes) message = hasChanges ? 'Periksa perubahan berikut sebelum disimpan.' : 'Tidak ada perubahan terdeteksi. Tetap simpan?';

        return {
            title: form.getAttribute('data-confirm-title') || (danger ? 'Konfirmasi Hapus' : (hasChanges ? 'Konfirmasi Perubahan' : 'Konfirmasi')),
            message: message,
            label: form.getAttribute('data-confirm-label') || (text && text.length <= 26 ? text : (isDelete ? 'Hapus' : 'Lanjutkan')),
            danger: danger,
            changes: hasChanges ? changes : null
        };
    }

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.hasAttribute || form.dataset.confirmed === '1') return;

        const spec = specFor(form, e.submitter || null);
        if (!spec) return;

        e.preventDefault();
        e.stopImmediatePropagation();
        pending = { form: form, submitter: e.submitter || null };
        open(spec);
    }, true);

    okBtn.addEventListener('click', function () {
        if (!pending) return;
        const job = pending;
        pending = null;
        modal.hide();
        if (job.resolve) { job.resolve(true); return; }
        job.form.dataset.confirmed = '1';
        try { job.form.requestSubmit(job.submitter || undefined); } finally { delete job.form.dataset.confirmed; }
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (pending && pending.resolve) pending.resolve(false);
        // Form yang dikirim lewat onchange (mis. dropdown status) dikembalikan ke nilai awal saat dibatalkan.
        if (pending && pending.form && pending.form.hasAttribute('data-confirm-revert')) pending.form.reset();
        pending = null;
    });

    // Kunci tombol submit setelah form dikirim; buka lagi saat kembali ke halaman.
    const LOCK_MS = 8000;
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (e.defaultPrevented || !form.querySelectorAll || form.hasAttribute('data-ajax')) return;
        if (form.target && form.target !== '_self') return;

        setTimeout(function () {
            const buttons = form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]');
            buttons.forEach(function (b) { b.disabled = true; });
            setTimeout(function () { buttons.forEach(function (b) { b.disabled = false; }); }, LOCK_MS);
        }, 0);
    });
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('form').forEach(function (f) {
            f.querySelectorAll('button:disabled').forEach(function (b) { b.disabled = false; });
        });
    });

    // Toast dinamis: AppUI.toast('Pesan', 'danger' | 'success' | 'primary')
    window.AppUI = {
        // AppUI.confirm('Teks', { label: 'Hapus', danger: true, title: 'Judul', changes: [...] }).then(ok => ...)
        confirm: function (message, opts) {
            opts = opts || {};
            return new Promise(function (resolve) {
                pending = { resolve: resolve };
                open({
                    title: opts.title || (opts.danger ? 'Konfirmasi Hapus' : 'Konfirmasi'),
                    message: message,
                    label: opts.label || 'Lanjutkan',
                    danger: !!opts.danger,
                    changes: opts.changes || null
                });
            });
        },
        toast: function (message, tone) {
            const holder = document.querySelector('.toast-container');
            if (!holder) return;
            const icons = { success: 'bi-check-circle-fill', danger: 'bi-exclamation-triangle-fill', primary: 'bi-info-circle-fill' };
            const key = icons[tone] ? tone : 'danger';
            const el = document.createElement('div');
            el.className = 'toast app-toast app-toast--' + key;
            el.setAttribute('role', 'alert');
            el.innerHTML = '<div class="d-flex align-items-start"><i class="bi ' + icons[key] + ' app-toast-icon"></i>' +
                '<div class="toast-body"></div>' +
                '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Tutup"></button></div>';
            el.querySelector('.toast-body').textContent = message;
            holder.appendChild(el);
            el.addEventListener('hidden.bs.toast', function () { el.remove(); });
            bootstrap.Toast.getOrCreateInstance(el, { delay: key === 'danger' ? 6000 : 4000 }).show();
        }
    };

    // Beri aria-label "Tutup" pada tombol tutup yang belum punya.
    function labelCloseButtons() {
        document.querySelectorAll('.btn-close:not([aria-label])').forEach(function (b) { b.setAttribute('aria-label', 'Tutup'); });
    }
    labelCloseButtons();
    document.addEventListener('show.bs.modal', labelCloseButtons);
    document.addEventListener('show.bs.offcanvas', labelCloseButtons);
})();
