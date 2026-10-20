// Input angka: titik ribuan otomatis, batas min/max dari atribut, dan nilai bersih (tanpa titik) saat form dikirim.
// Pasang data-no-format untuk mengecualikan input. Skrip lain memakai NumberInput.parse/format/set.
(function () {
    'use strict';

    const SELECTOR = 'input[type="number"], input[inputmode="numeric"], input[data-number]';
    const MAX_DIGITS = 15;

    function digitsOf(value) {
        return String(value === null || value === undefined ? '' : value).replace(/\D/g, '').replace(/^0+(?=\d)/, '');
    }

    function format(value) {
        const digits = digitsOf(value).slice(0, MAX_DIGITS);
        return digits === '' ? '' : Number(digits).toLocaleString('id-ID');
    }

    function parse(value) {
        const digits = digitsOf(value).slice(0, MAX_DIGITS);
        return digits === '' ? 0 : parseInt(digits, 10);
    }

    function limit(el, name) {
        const raw = el.getAttribute(name);
        if (raw === null || raw === '') return null;
        const n = parseInt(digitsOf(raw), 10);
        return isNaN(n) ? null : n;
    }

    function hintOf(el) {
        const host = el.closest('.input-group') || el;
        const next = host.nextElementSibling;
        return next && next.classList.contains('number-limit-hint') ? next : null;
    }

    function showHint(el, max) {
        let hint = hintOf(el);
        if (!hint) {
            hint = document.createElement('div');
            hint.className = 'form-text text-danger number-limit-hint';
            (el.closest('.input-group') || el).insertAdjacentElement('afterend', hint);
        }
        hint.textContent = 'Maksimal ' + format(String(max)) + '.';
    }

    function apply(el) {
        const raw = el.value;
        const hasCaret = document.activeElement === el && el.selectionStart !== null;
        const before = hasCaret ? raw.slice(0, el.selectionStart).replace(/\D/g, '').length : 0;
        const max = limit(el, 'max');
        let digits = digitsOf(raw).slice(0, MAX_DIGITS);
        let capped = false;

        if (digits !== '' && max !== null && parseInt(digits, 10) > max) {
            digits = String(max);
            capped = true;
        }

        const next = digits === '' ? '' : Number(digits).toLocaleString('id-ID');

        if (next !== raw) {
            el.value = next;
            if (hasCaret && !capped) {
                let pos = 0;
                let seen = 0;
                while (pos < next.length && seen < before) {
                    if (/\d/.test(next.charAt(pos))) seen++;
                    pos++;
                }
                try { el.setSelectionRange(pos, pos); } catch (err) { /* tipe input tanpa seleksi */ }
            }
        }

        if (capped) {
            showHint(el, max);
        } else if (hintOf(el)) {
            hintOf(el).remove();
        }
    }

    function enforceMin(el) {
        const digits = digitsOf(el.value);
        const min = limit(el, 'min');
        if (digits !== '' && min !== null && parseInt(digits, 10) < min) {
            el.value = format(String(min));
        }
        if (hintOf(el)) hintOf(el).remove();
    }

    function upgrade(el) {
        if (el.dataset.numFmt || el.hasAttribute('data-no-format')) return;
        if (el.type !== 'number' && el.type !== 'text') return;

        const wasNumber = el.type === 'number';
        let value = el.value;

        el.dataset.numFmt = '1';
        if (wasNumber) {
            el.type = 'text';
            el.removeAttribute('step');
            // Nilai server bisa berupa "15000000.00"; ambil bagian bulatnya, bukan menghapus titiknya.
            if (value !== '') {
                const n = Math.round(parseFloat(value));
                value = isNaN(n) ? '' : String(n);
            }
        }
        el.setAttribute('inputmode', 'numeric');
        el.setAttribute('autocomplete', 'off');
        el.value = format(value);
    }

    function scan(root) {
        if (root.nodeType !== 1) return;
        if (root.matches(SELECTOR)) upgrade(root);
        root.querySelectorAll(SELECTOR).forEach(upgrade);
    }

    // Capture: nilai sudah rapi dan terbatas sebelum listener input milik halaman membacanya.
    document.addEventListener('input', function (e) {
        if (e.target.dataset && e.target.dataset.numFmt) apply(e.target);
    }, true);

    document.addEventListener('change', function (e) {
        if (e.target.dataset && e.target.dataset.numFmt) enforceMin(e.target);
    }, true);

    // Tempel "1500000.50" atau "1500000,5": buang desimalnya, jangan jadi 150000050.
    document.addEventListener('paste', function (e) {
        const el = e.target;
        if (!el.dataset || !el.dataset.numFmt) return;
        const text = (e.clipboardData || window.clipboardData).getData('text');
        const match = /^\s*(?:Rp\.?\s*)?(\d+)[.,]\d{1,2}\s*$/i.exec(text);
        if (!match) return;
        e.preventDefault();
        el.value = match[1];
        apply(el);
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }, true);

    // Kirim angka murni; berlaku untuk submit biasa maupun FormData milik ajax-forms.
    document.addEventListener('formdata', function (e) {
        const groups = {};
        e.target.querySelectorAll('input[data-num-fmt]').forEach(function (el) {
            if (el.disabled || !el.name) return;
            (groups[el.name] = groups[el.name] || []).push(el);
        });

        Object.keys(groups).forEach(function (name) {
            const els = groups[name];
            if (e.formData.getAll(name).length !== els.length) return;
            e.formData.delete(name);
            els.forEach(function (el) {
                const digits = digitsOf(el.value);
                e.formData.append(name, digits);
            });
        });
    });

    new MutationObserver(function (mutations) {
        mutations.forEach(function (m) { m.addedNodes.forEach(scan); });
    }).observe(document.documentElement, { childList: true, subtree: true });

    scan(document.documentElement);

    window.NumberInput = {
        parse: parse,
        format: format,
        // Isi input dengan angka lewat kode; tampil berformat, batas max tetap berlaku.
        set: function (el, value) {
            el.value = value === '' || value === null ? '' : String(value);
            apply(el);
        },
    };
})();
