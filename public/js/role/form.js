// Form Role: hitung izin aktif per modul, pilih semua / kosongkan per modul, dan salin izin dari role lain.
(function () {
    const root = document.getElementById('role-form');
    if (!root) return;

    const defaults = JSON.parse(root.dataset.roleDefaults || '{}');
    const boxes = () => root.querySelectorAll('.role-permission');

    function refresh() {
        root.querySelectorAll('[data-module-group]').forEach((group) => {
            const on = group.querySelectorAll('.role-permission:checked').length;
            const badge = group.querySelector('.module-count');
            if (badge) badge.textContent = on + ' aktif';
        });

        const total = root.querySelectorAll('.role-permission:checked').length;
        const out = document.getElementById('role-total-active');
        if (out) out.textContent = total;
    }

    root.querySelectorAll('[data-toggle-module]').forEach((btn) => {
        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            const group = root.querySelector('[data-module-group="' + btn.dataset.toggleModule + '"]');
            const check = btn.dataset.state === 'all';
            group.querySelectorAll('.role-permission').forEach((box) => { box.checked = check; });
            refresh();
        });
    });

    const copy = document.getElementById('copy-from');
    if (copy) {
        copy.addEventListener('change', function () {
            const source = defaults[copy.value];
            if (!source) return;

            boxes().forEach((box) => {
                const module = source[box.dataset.module] || {};
                box.checked = !!module[box.dataset.action];
            });
            refresh();
        });
    }

    root.addEventListener('change', function (event) {
        if (event.target.classList.contains('role-permission')) refresh();
    });

    refresh();
})();
