// Umpan balik navigasi: bar progres di atas halaman dan spinner pada tombol submit.
(function () {
    'use strict';

    // Batas aman: bar hilang sendiri bila navigasi tidak berpindah halaman (mis. unduhan).
    const SAFETY_MS = 8000;
    let bar = null;
    let value = 0;
    let ticker = null;
    let safety = null;
    let spinners = [];

    function ensureBar() {
        if (bar) return bar;
        bar = document.createElement('div');
        bar.id = 'appProgress';
        bar.setAttribute('aria-hidden', 'true');
        document.body.appendChild(bar);
        return bar;
    }

    function render() {
        bar.style.transform = 'scaleX(' + value + ')';
    }

    function start() {
        if (ticker) return;
        ensureBar();
        document.documentElement.classList.add('is-navigating');
        bar.classList.add('is-active');
        value = 0.08;
        render();
        ticker = setInterval(function () {
            value += (0.9 - value) * 0.07;
            render();
        }, 200);
        safety = setTimeout(finish, SAFETY_MS);
    }

    function clearSpinners() {
        spinners.forEach(function (s) {
            if (s.spinner.parentNode) s.spinner.remove();
            if (s.icon) s.icon.classList.remove('d-none');
        });
        spinners = [];
    }

    function finish() {
        clearInterval(ticker);
        clearTimeout(safety);
        ticker = safety = null;
        document.documentElement.classList.remove('is-navigating');
        clearSpinners();
        if (!bar || !bar.classList.contains('is-active')) return;
        value = 1;
        render();
        setTimeout(function () {
            bar.classList.remove('is-active');
            setTimeout(function () { value = 0; render(); }, 250);
        }, 150);
    }

    function isPageLink(a) {
        if (a.target && a.target !== '_self') return false;
        if (a.hasAttribute('download') || a.hasAttribute('data-bs-toggle') || a.hasAttribute('data-no-progress')) return false;
        let url;
        try { url = new URL(a.href, location.href); } catch (err) { return false; }
        if (url.origin !== location.origin || !/^https?:$/.test(url.protocol)) return false;
        if (/\/download/.test(url.pathname)) return false;
        // Hanya hash yang berbeda: tidak pindah halaman.
        if (url.pathname === location.pathname && url.search === location.search && url.hash !== '') return false;
        return true;
    }

    function showButtonSpinner(btn) {
        if (!btn || btn.classList.contains('btn-close')) return;
        const spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm me-2';
        spinner.setAttribute('aria-hidden', 'true');
        const icon = btn.querySelector('i.bi');
        if (icon) icon.classList.add('d-none');
        btn.insertBefore(spinner, btn.firstChild);
        spinners.push({ spinner: spinner, icon: icon });
    }

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest('a[href]');
        if (a && isPageLink(a)) start();
    });

    document.addEventListener('submit', function (e) {
        const form = e.target;
        // Tunda satu tick: konfirmasi atau handler AJAX bisa membatalkan submit.
        setTimeout(function () {
            if (e.defaultPrevented) return;
            if ((form.target && form.target !== '_self') || form.hasAttribute('data-no-progress')) return;
            if (/\/download/.test(form.action || '')) return;
            showButtonSpinner(e.submitter || form.querySelector('button[type="submit"], button:not([type])'));
            start();
        }, 0);
    });

    // Reload, tombol kembali, atau pindah halaman lewat script.
    window.addEventListener('beforeunload', start);

    // Kembali dari cache browser (bfcache): matikan bar yang masih menyala.
    window.addEventListener('pageshow', finish);
})();
