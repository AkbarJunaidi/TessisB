// Form AJAX generik (atribut data-ajax): kirim via XHR, tampilkan toast, lalu perbarui halaman lewat atribut data-ajax-*.
// Atribut: -remove, -remove-closest, -prepend (butuh data.html), -decrement, -reset, -progress, -hash (dipakai bersama reload).
(function () {
    'use strict';

    function send(form, submitter, onProgress) {
        return new Promise(function (resolve) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            if (onProgress) {
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) onProgress(Math.round(e.loaded / e.total * 100));
                });
            }
            xhr.addEventListener('load', function () {
                let data = null;
                try { data = JSON.parse(xhr.responseText); } catch (err) { data = null; }
                resolve({ status: xhr.status, data: data });
            });
            xhr.addEventListener('error', function () { resolve({ status: 0, data: null }); });
            const body = new FormData(form);
            if (submitter && submitter.name) body.append(submitter.name, submitter.value);
            xhr.send(body);
        });
    }

    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('.ajax-feedback').forEach(function (el) { el.remove(); });
    }

    // Tandai field yang salah; pesan untuk field yang tidak ada di form dikembalikan agar ditampilkan lewat toast.
    function showErrors(form, errors) {
        const unplaced = [];
        Object.keys(errors).forEach(function (key) {
            const name = key.split('.')[0];
            const input = form.querySelector('[name="' + name + '"]') || form.querySelector('[name="' + name + '[]"]');
            if (!input) { unplaced.push(errors[key][0]); return; }
            input.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback ajax-feedback';
            feedback.textContent = errors[key][0];
            input.insertAdjacentElement('afterend', feedback);
        });
        return unplaced;
    }

    function setProgress(form, percent) {
        const wrap = form.querySelector('.ajax-progress');
        if (!wrap) return;
        const bar = wrap.querySelector('.progress-bar');
        wrap.classList.remove('d-none');
        bar.style.width = percent + '%';
        bar.textContent = percent + '%';
        wrap.setAttribute('aria-valuenow', percent);
    }

    function setBusy(form, busy) {
        form.dataset.ajaxBusy = busy ? '1' : '';
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (btn) {
            btn.disabled = busy;
            const old = btn.querySelector('.ajax-spinner');
            if (old) old.remove();
            const icon = btn.querySelector('i.bi');
            if (icon) icon.classList.toggle('d-none', busy);
            if (busy) {
                const spinner = document.createElement('span');
                spinner.className = 'spinner-border spinner-border-sm me-2 ajax-spinner';
                spinner.setAttribute('aria-hidden', 'true');
                btn.insertBefore(spinner, btn.firstChild);
            }
        });
    }

    function removeWithFade(el) {
        if (!el) return;
        el.classList.add('ajax-removing');
        setTimeout(function () { el.remove(); }, 200);
    }

    function onSuccess(form, data) {
        data = data || {};
        if (data.reload) {
            if (form.dataset.ajaxHash) window.location.hash = form.dataset.ajaxHash;
            window.location.reload();
            return;
        }
        if (data.message) window.AppUI.toast(data.message, 'success');

        const modalEl = form.closest('.modal');
        const modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
        if (modal) modal.hide();

        if (form.dataset.ajaxPrepend && data.html) {
            const target = document.querySelector(form.dataset.ajaxPrepend);
            if (target) target.insertAdjacentHTML('afterbegin', data.html);
        }
        if (form.dataset.ajaxRemove) removeWithFade(document.querySelector(form.dataset.ajaxRemove));
        if (form.dataset.ajaxRemoveClosest) removeWithFade(form.closest(form.dataset.ajaxRemoveClosest));
        if (form.dataset.ajaxDecrement) {
            const counter = document.querySelector(form.dataset.ajaxDecrement);
            if (counter) counter.textContent = Math.max(0, (parseInt(counter.textContent, 10) || 0) - 1);
        }
        if (form.hasAttribute('data-ajax-reset')) form.reset();

        form.dispatchEvent(new CustomEvent('ajax:success', { bubbles: true, detail: data }));
    }

    function onError(form, res) {
        const data = res.data || {};
        if (res.status === 422 && data.errors) {
            const unplaced = showErrors(form, data.errors);
            if (unplaced.length) window.AppUI.toast(unplaced[0], 'danger');
            return;
        }
        let message = data.message;
        if (res.status === 0) message = 'Koneksi terputus. Periksa jaringan lalu coba lagi.';
        else if (res.status === 419) message = 'Sesi berakhir. Muat ulang halaman lalu coba lagi.';
        else if (res.status === 403 && (!message || /unauthorized/i.test(message))) message = 'Anda tidak memiliki hak akses untuk aksi ini.';
        else if (res.status >= 500 || !message) message = 'Terjadi kesalahan. Silakan coba lagi.';
        window.AppUI.toast(message, 'danger');
    }

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.hasAttribute || !form.hasAttribute('data-ajax')) return;
        e.preventDefault();
        if (form.dataset.ajaxBusy === '1') return;

        clearErrors(form);
        setBusy(form, true);
        const showProgress = form.hasAttribute('data-ajax-progress');
        if (showProgress) setProgress(form, 0);

        send(form, e.submitter, showProgress ? function (p) { setProgress(form, p); } : null).then(function (res) {
            if (res.status >= 200 && res.status < 300) {
                onSuccess(form, res.data);
            } else {
                onError(form, res);
            }
            // Setelah reload tombol dibiarkan terkunci agar tidak terkirim dua kali.
            if (!(res.data && res.data.reload && res.status < 300)) {
                setBusy(form, false);
                const wrap = form.querySelector('.ajax-progress');
                if (wrap) wrap.classList.add('d-none');
            }
        });
    });
})();
