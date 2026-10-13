// Tab AJAX: pindah tab tanpa memuat ulang halaman. Wilayah [data-ajax-region] diganti dari respons halaman tujuan.
// Pakai: <a href="..." data-ajax-tab> di dalam <ul data-ajax-region="tabs">; area konten dibungkus data-ajax-region="nama".
(function () {
    'use strict';

    const REGIONS = '[data-ajax-region]';
    let busy = false;

    function swap(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const regions = Array.from(document.querySelectorAll(REGIONS));

        // Semua wilayah harus ada di halaman tujuan; kalau tidak, pindah halaman biasa.
        const pairs = regions.map(function (current) {
            return [current, doc.querySelector('[data-ajax-region="' + current.dataset.ajaxRegion + '"]')];
        });
        if (pairs.length === 0 || pairs.some(function (pair) { return !pair[1]; })) throw new Error('Wilayah tab tidak lengkap.');

        pairs.forEach(function (pair) { pair[0].innerHTML = pair[1].innerHTML; });
        if (doc.title) document.title = doc.title;
    }

    function load(url, push) {
        if (busy) return;
        busy = true;
        const regions = Array.from(document.querySelectorAll(REGIONS));
        regions.forEach(function (region) { region.classList.add('is-loading'); });

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok || response.redirected) throw new Error('Gagal memuat tab.');
                return response.text();
            })
            .then(function (html) {
                swap(html);
                if (push) history.pushState({ ajaxTabs: true }, '', url);
                document.dispatchEvent(new CustomEvent('ajax-tabs:loaded'));
            })
            .catch(function () { window.location.href = url; })
            .finally(function () {
                busy = false;
                regions.forEach(function (region) { region.classList.remove('is-loading'); });
            });
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[data-ajax-tab]');
        if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        event.preventDefault();
        if (link.href !== window.location.href) load(link.href, true);
    });

    // Tombol kembali atau maju: muat ulang wilayah sesuai URL yang dituju.
    window.addEventListener('popstate', function (event) {
        if (event.state && event.state.ajaxTabs) load(window.location.href, false);
    });

    // Tandai entri awal agar tombol kembali ke halaman ini juga memicu popstate yang dikenali.
    if (document.querySelector(REGIONS)) history.replaceState({ ajaxTabs: true }, '', window.location.href);
})();
