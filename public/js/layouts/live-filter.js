// Filter langsung: form GET dengan data-live-filter memperbarui hasil tanpa memuat ulang halaman penuh.
// Isi halaman setelah kartu .filter-card diganti dari respons server; kegagalan apa pun jatuh ke muat ulang biasa.
(function () {
    'use strict';

    const DELAY = 350;
    const TEXT = ['text', 'search', 'number', 'email', 'tel'];

    const isModal = function (el) { return el.classList.contains('modal') && el.id; };

    const siblingsAfter = function (node) {
        const list = [];
        for (let el = node.nextElementSibling; el; el = el.nextElementSibling) {
            if (el.tagName !== 'SCRIPT') list.push(el);
        }
        return list;
    };

    function init(form, index) {
        const anchor = form.closest('.filter-card');
        if (!anchor || !anchor.parentElement) return;

        const parent = anchor.parentElement;
        let timer = null;
        let controller = null;
        let lastKey = null;

        function buildUrl() {
            const url = new URL(form.getAttribute('action') || window.location.pathname, window.location.href);
            const params = new URLSearchParams(new FormData(form));
            params.delete('page');
            const perPage = new URL(window.location.href).searchParams.get('per_page');
            if (perPage && !params.has('per_page')) params.set('per_page', perPage);
            url.search = params.toString();
            return url;
        }

        // Ganti semua elemen setelah kartu filter; modal statis (sudah dipasangi handler) dipertahankan.
        function swap(html) {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.querySelectorAll('form[data-live-filter]')[index];
            const freshAnchor = fresh && fresh.closest('.filter-card');
            if (!freshAnchor) return false;

            const freshNodes = siblingsAfter(freshAnchor);
            const freshIds = new Set(freshNodes.filter(isModal).map(function (el) { return el.id; }));
            const keptIds = new Set();

            siblingsAfter(anchor).forEach(function (el) {
                if (isModal(el) && freshIds.has(el.id)) keptIds.add(el.id);
                else el.remove();
            });

            anchor.after.apply(anchor, freshNodes
                .filter(function (el) { return !(isModal(el) && keptIds.has(el.id)); })
                .map(function (el) { return document.importNode(el, true); }));
            return true;
        }

        async function run(url, scroll) {
            if (controller) controller.abort();
            const mine = new AbortController();
            controller = mine;

            parent.setAttribute('aria-busy', 'true');
            siblingsAfter(anchor).forEach(function (el) { if (!isModal(el)) el.classList.add('live-loading'); });

            try {
                const res = await fetch(url.toString(), {
                    signal: mine.signal,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                });
                if (!res.ok || new URL(res.url).pathname !== url.pathname || !swap(await res.text())) {
                    window.location.href = url.toString();
                    return;
                }
                window.history.replaceState(null, '', url.pathname + url.search);
                lastKey = url.pathname + url.search;
                if (scroll) anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.dispatchEvent(new CustomEvent('live-filter:updated'));
            } catch (err) {
                if (err.name !== 'AbortError') window.location.href = url.toString();
            } finally {
                if (controller === mine) parent.removeAttribute('aria-busy');
            }
        }

        function go() {
            clearTimeout(timer);
            const url = buildUrl();
            if (url.pathname + url.search !== lastKey) run(url, false);
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            go();
        });

        form.addEventListener('input', function (e) {
            if (e.isComposing || TEXT.indexOf(e.target.type) === -1) return;
            clearTimeout(timer);
            timer = setTimeout(go, DELAY);
        });

        form.addEventListener('change', go);

        // Halaman berikutnya ikut tanpa muat ulang; klik dengan tombol modifier tetap membuka tab baru.
        parent.addEventListener('click', function (e) {
            const link = e.target.closest('.pagination a[href]');
            if (!link || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin || url.pathname !== buildUrl().pathname) return;
            e.preventDefault();
            run(url, true);
        });
    }

    function start() {
        document.querySelectorAll('form[data-live-filter]').forEach(init);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
