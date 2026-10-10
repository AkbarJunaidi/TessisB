// Pager: ganti jumlah baris per halaman dan lompat ke halaman; parameter lain di URL dipertahankan.
(function () {
    'use strict';

    function go(changes) {
        const url = new URL(window.location.href);
        Object.keys(changes).forEach(function (key) {
            if (changes[key] === null) url.searchParams.delete(key);
            else url.searchParams.set(key, changes[key]);
        });
        window.location.href = url.toString();
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches('[data-pager-size]')) go({ per_page: e.target.value, page: null });
    });

    // Filter GET di halaman yang sama tetap membawa pilihan baris per halaman.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        const perPage = new URL(window.location.href).searchParams.get('per_page');
        if (!perPage || (form.method || '').toLowerCase() !== 'get' || form.querySelector('[name="per_page"]')) return;
        if (new URL(form.action, window.location.href).pathname !== window.location.pathname) return;
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'per_page';
        hidden.value = perPage;
        form.appendChild(hidden);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' || !e.target.matches('[data-pager-goto]')) return;
        e.preventDefault();
        const input = e.target;
        const max = parseInt(input.max, 10) || 1;
        const page = Math.min(Math.max(parseInt(input.value, 10) || 1, 1), max);
        if (String(page) !== input.dataset.pagerCurrent) go({ page: page });
        else input.value = page;
    });
})();
