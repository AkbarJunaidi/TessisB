// Helper UI global: konfirmasi form, toast, kunci tombol submit, aria-label tombol tutup.
(function () {
    const modalEl = document.getElementById('appConfirmModal');
    const okBtn = document.getElementById('appConfirmOk');
    const textEl = document.getElementById('appConfirmText');
    let pending = null;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.hasAttribute || !form.hasAttribute('data-confirm') || form.dataset.confirmed === '1') return;

        e.preventDefault();
        e.stopImmediatePropagation();
        pending = { form: form, submitter: e.submitter || null };

        textEl.textContent = form.getAttribute('data-confirm');
        okBtn.textContent = form.getAttribute('data-confirm-label') || 'Lanjutkan';
        okBtn.className = 'btn ' + (form.hasAttribute('data-confirm-danger') ? 'btn-danger' : 'btn-primary');
        modal.show();
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
        // AppUI.confirm('Teks', { label: 'Hapus', danger: true }).then(ok => ...)
        confirm: function (message, opts) {
            opts = opts || {};
            return new Promise(function (resolve) {
                pending = { resolve: resolve };
                textEl.textContent = message;
                okBtn.textContent = opts.label || 'Lanjutkan';
                okBtn.className = 'btn ' + (opts.danger ? 'btn-danger' : 'btn-primary');
                modal.show();
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
