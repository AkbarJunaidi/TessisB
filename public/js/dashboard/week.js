/* Kartu Minggu Ini: pilih hari untuk menampilkan acaranya; panah kiri/kanan berpindah hari. */
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('dashWeek');
    if (!root) {
        return;
    }

    const tabs = Array.from(root.querySelectorAll('[role="tab"]'));

    const select = function (tab, focus) {
        tabs.forEach(function (t) {
            const on = t === tab;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
            document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
        });
        if (focus) {
            tab.focus();
        }
    };

    tabs.forEach(function (tab, i) {
        tab.addEventListener('click', function () { select(tab, false); });
        tab.addEventListener('keydown', function (e) {
            const step = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
            if (step) {
                e.preventDefault();
                select(tabs[(i + step + tabs.length) % tabs.length], true);
            }
        });
    });
});
