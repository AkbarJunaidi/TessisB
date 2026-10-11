// Ringkasan perubahan form untuk popup konfirmasi (dipakai app-2.js lewat AppFormChanges.diff(form)).
// Pasang data-track-changes di <form>. Label isian: <label for>, atau data-change-label. Awalan nilai: data-change-prefix="Rp ".
// Daftar baris yang bisa ditambah/dihapus: pasang data-change-list="Judul" data-change-row=".kelas-baris" di wadahnya.
(function () {
    'use strict';

    const EMPTY = '(kosong)';
    const NON_FIELD = ['hidden', 'submit', 'button', 'reset', 'image'];
    const ORDER = { remove: 0, change: 1, add: 2 };
    const snapshots = new WeakMap();

    function typeOf(el) {
        return (el.type || '').toLowerCase();
    }

    function labelText(el) {
        const root = el.ownerDocument;
        const label = (el.id && root.querySelector('label[for="' + CSS.escape(el.id) + '"]')) || el.closest('label');
        if (!label) return '';

        const clone = label.cloneNode(true);
        clone.querySelectorAll('input, select, textarea, .text-danger').forEach(function (n) { n.remove(); });
        return clone.textContent.replace(/\*/g, '').replace(/\s+/g, ' ').trim();
    }

    function labelOf(el) {
        return el.getAttribute('data-change-label')
            || labelText(el)
            || el.getAttribute('placeholder')
            || el.name.replace(/\[\]$/, '').replace(/[_\[\]]+/g, ' ').trim();
    }

    function valueOf(el) {
        const type = typeOf(el);
        if (type === 'checkbox') return el.checked ? 'Ya' : 'Tidak';
        if (type === 'password') return el.value ? '(diubah)' : '';
        if (type === 'file') return Array.from(el.files || []).map(function (f) { return f.name; }).join(', ');
        if (el.tagName === 'SELECT') {
            return Array.from(el.selectedOptions).map(function (o) { return o.textContent.trim(); }).join(', ');
        }

        const value = el.value.trim();
        const prefix = el.getAttribute('data-change-prefix');
        return value && prefix ? prefix + value : value;
    }

    function radioValue(form, name) {
        const checked = form.querySelector('input[type="radio"][name="' + CSS.escape(name) + '"]:checked');
        return checked ? (labelText(checked) || checked.value) : '';
    }

    // Isian di luar daftar baris. Kuncinya elemen itu sendiri; radio diwakili radio pertama dalam grupnya.
    function collectFields(form) {
        const fields = new Map();
        const seenRadio = new Set();

        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            const type = typeOf(el);
            if (!el.name || el.disabled || NON_FIELD.indexOf(type) !== -1 || el.closest('[data-change-list]')) return;

            if (type === 'radio') {
                if (seenRadio.has(el.name)) return;
                seenRadio.add(el.name);
                fields.set(el, { label: labelOf(el), value: radioValue(form, el.name) });
                return;
            }
            fields.set(el, { label: labelOf(el), value: valueOf(el) });
        });
        return fields;
    }

    function currentValue(form, el) {
        return typeOf(el) === 'radio' ? radioValue(form, el.name) : valueOf(el);
    }

    // Ringkasan satu baris; baris yang semua isiannya kosong dianggap baris kosong (tidak dihitung).
    function rowSummary(row) {
        const parts = [];
        let hasText = false;

        row.querySelectorAll('input, select, textarea').forEach(function (el) {
            const type = typeOf(el);
            if (!el.name || el.disabled || NON_FIELD.indexOf(type) !== -1 || type === 'checkbox' || type === 'radio') return;

            const value = valueOf(el);
            if (el.tagName !== 'SELECT' && value) hasText = true;
            if (value) parts.push(value);
        });
        return hasText ? parts.join(' \u2014 ') : '';
    }

    function snapshotList(container) {
        const rows = new Map();
        container.querySelectorAll(container.getAttribute('data-change-row')).forEach(function (row) {
            rows.set(row, rowSummary(row));
        });
        return { container: container, title: container.getAttribute('data-change-list'), rows: rows };
    }

    function takeSnapshot(form) {
        snapshots.set(form, {
            fields: collectFields(form),
            lists: Array.from(form.querySelectorAll('[data-change-list]')).map(snapshotList)
        });
    }

    function diffFields(form, snap) {
        const items = [];
        snap.fields.forEach(function (before, el) {
            const now = currentValue(form, el);
            if (now !== before.value) {
                items.push({ type: 'change', label: before.label, from: before.value || EMPTY, to: now || EMPTY });
            }
        });
        return items;
    }

    function diffList(list) {
        const items = [];
        const rowSelector = list.container.getAttribute('data-change-row');

        list.rows.forEach(function (before, row) {
            if (!list.container.contains(row)) {
                if (before) items.push({ type: 'remove', text: before });
                return;
            }

            const after = rowSummary(row);
            if (after === before) return;
            if (!after) items.push({ type: 'remove', text: before });
            else if (!before) items.push({ type: 'add', text: after });
            else items.push({ type: 'change', from: before, to: after });
        });

        list.container.querySelectorAll(rowSelector).forEach(function (row) {
            if (list.rows.has(row)) return;
            const text = rowSummary(row);
            if (text) items.push({ type: 'add', text: text });
        });

        return items.sort(function (a, b) { return ORDER[a.type] - ORDER[b.type]; });
    }

    // Hasil: [{ title, items: [{ type: 'add' | 'remove' | 'change', label?, text?, from?, to? }] }]; null bila form tidak dilacak.
    function diff(form) {
        const snap = snapshots.get(form);
        if (!snap) return null;

        const groups = [{ title: 'Isian yang diubah', items: diffFields(form, snap) }];
        snap.lists.forEach(function (list) {
            groups.push({ title: list.title, items: diffList(list) });
        });
        return groups.filter(function (g) { return g.items.length; });
    }

    // Diambil setelah skrip halaman selesai mengatur nilai awal (DOMContentLoaded milik halaman jalan lebih dulu).
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-track-changes]').forEach(takeSnapshot);
    });

    window.AppFormChanges = { diff: diff };
})();
