// Pager: ganti halaman, jumlah baris per halaman, dan lompat ke halaman tanpa memuat ulang penuh.
// Hanya blok tabel dan pager yang diganti; parameter lain di URL (filter) dipertahankan.
// Halaman tanpa tabel di samping pager tetap pindah halaman biasa.
(function () {
    'use strict';

    const LOADING_DELAY = 300;
    let controller = null;

    const isModal = function (el) { return el.classList.contains('modal') && el.id; };

    function buildUrl(changes) {
        const url = new URL(window.location.href);
        Object.keys(changes).forEach(function (key) {
            if (changes[key] === null) url.searchParams.delete(key);
            else url.searchParams.set(key, changes[key]);
        });
        return url;
    }

    // Tabel yang dilayani pager: tabel terakhir sebelum pager di luar modal.
    function tableFor(nav) {
        const tables = Array.from(nav.ownerDocument.querySelectorAll('table')).filter(function (t) {
            return !t.closest('.modal') && (t.compareDocumentPosition(nav) & Node.DOCUMENT_POSITION_FOLLOWING);
        });
        return tables[tables.length - 1] || null;
    }

    // Blok yang diganti: dari anak pembungkus tabel sampai anak pembungkus pager, plus modal yang menyusul.
    function rangeFor(nav) {
        const table = tableFor(nav);
        if (!table) return null;

        let ca = table.parentElement;
        while (ca && !ca.contains(nav)) ca = ca.parentElement;
        if (!ca) return null;

        let a = table;
        while (a.parentElement !== ca) a = a.parentElement;
        let b = nav;
        while (b.parentElement !== ca) b = b.parentElement;
        if (a === b) return null;

        const nodes = [];
        for (let el = a; el; el = el.nextElementSibling) {
            if (el.tagName !== 'SCRIPT') nodes.push(el);
            if (el === b) break;
        }
        const tail = [];
        for (let el = b.nextElementSibling; el; el = el.nextElementSibling) {
            if (el.tagName === 'SCRIPT') continue;
            if (!isModal(el)) break;
            tail.push(el);
        }
        return { parent: ca, nodes: nodes, all: nodes.concat(tail) };
    }

    function swap(doc, index) {
        const nav = document.querySelectorAll('.app-pager')[index];
        const freshNav = doc.querySelectorAll('.app-pager')[index];
        const old = nav && rangeFor(nav);
        const fresh = freshNav && rangeFor(freshNav);
        if (!old || !fresh) return false;

        // Modal statis (sudah dipasangi handler) dipertahankan; modal per baris yang baru ditambahkan.
        const freshIds = new Set(fresh.all.filter(isModal).map(function (el) { return el.id; }));
        const kept = new Set();
        old.all.forEach(function (el) { if (isModal(el) && freshIds.has(el.id)) kept.add(el.id); });

        const ref = old.all[0];
        const incoming = fresh.all
            .filter(function (el) { return !(isModal(el) && kept.has(el.id)); })
            .map(function (el) { return document.importNode(el, true); });
        incoming.forEach(function (el) { old.parent.insertBefore(el, ref); });
        old.all.forEach(function (el) { if (!(isModal(el) && kept.has(el.id))) el.remove(); });

        const first = incoming[0];
        if (first) {
            const top = first.getBoundingClientRect().top;
            if (top < 0 || top > window.innerHeight * 0.6) first.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        return true;
    }

    async function load(url, index) {
        if (controller) controller.abort();
        const mine = new AbortController();
        controller = mine;

        const nav = document.querySelectorAll('.app-pager')[index];
        const old = nav && rangeFor(nav);
        if (!old) { window.location.href = url.toString(); return; }

        // Setelah jeda singkat layar diberi lapisan gelap tipis (CSS html.is-loading); halaman cepat tidak berkedip.
        const timer = setTimeout(function () { document.documentElement.classList.add('is-loading'); }, LOADING_DELAY);

        try {
            const res = await fetch(url.toString(), {
                signal: mine.signal,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            });
            if (!res.ok || new URL(res.url).pathname !== url.pathname) throw new Error('respons tidak valid');

            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            if (!swap(doc, index)) throw new Error('blok tabel tidak ditemukan');

            // Entri awal ikut ditandai agar tombol Kembali memicu popstate yang dikenali.
            if (!window.history.state || !window.history.state.appPager) {
                window.history.replaceState(Object.assign({}, window.history.state, { appPager: true }), '', window.location.href);
            }
            window.history.pushState(Object.assign({}, window.history.state, { appPager: true }), '', url.pathname + url.search);
            document.dispatchEvent(new CustomEvent('pager:updated'));
        } catch (err) {
            if (err.name !== 'AbortError') window.location.href = url.toString();
        } finally {
            clearTimeout(timer);
            if (controller === mine) document.documentElement.classList.remove('is-loading');
        }
    }

    function go(nav, changes) {
        const url = buildUrl(changes);
        if (nav && rangeFor(nav)) load(url, Array.from(document.querySelectorAll('.app-pager')).indexOf(nav));
        else window.location.href = url.toString();
    }

    // Capture agar berjalan sebelum umpan balik navigasi (page-feedback.js) yang melewati klik yang dibatalkan.
    document.addEventListener('click', function (e) {
        const a = e.target.closest('.app-pager-pages a[href]');
        if (!a || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const nav = a.closest('.app-pager');
        const url = new URL(a.href, window.location.href);
        if (url.origin !== window.location.origin || !rangeFor(nav)) return;
        e.preventDefault();
        load(url, Array.from(document.querySelectorAll('.app-pager')).indexOf(nav));
    }, true);

    document.addEventListener('change', function (e) {
        if (e.target.matches('[data-pager-size]')) go(e.target.closest('.app-pager'), { per_page: e.target.value, page: null });
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
        if (String(page) !== input.dataset.pagerCurrent) go(input.closest('.app-pager'), { page: page });
        else input.value = page;
    });

    // Tombol Kembali/Maju ke halaman hasil AJAX: muat ulang sesuai URL (tab AJAX menangani miliknya sendiri).
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.appPager && !e.state.ajaxTabs) window.location.reload();
    });
})();
