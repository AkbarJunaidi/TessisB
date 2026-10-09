(function () {
    const section = document.getElementById('permission-section');
    if (!section) return;

    const roleDefaults = JSON.parse(section.dataset.roleDefaults);
    const catalog = JSON.parse(section.dataset.catalog);
    const roleSelect = document.getElementById('role');
    const customBadge = document.getElementById('summary-custom-badge');
    const roleBadge = document.getElementById('summary-role-badge');
    const resetBtn = document.getElementById('btn-reset-permission');

    // Ambil checkbox izin untuk satu modul dan aksi.
    function checkbox(module, action) {
        return document.getElementById(`perm-${module}-${action}`);
    }

    // Centang ulang semua izin sesuai bawaan role yang dipilih.
    function applyDefaultsForRole(role) {
        const defaults = roleDefaults[role] || {};

        Object.keys(catalog).forEach((module) => {
            catalog[module].forEach((action) => {
                const el = checkbox(module, action);
                if (el) el.checked = !!(defaults[module] && defaults[module][action]);
            });
        });

        recalculateSummary();
    }

    // Hitung ringkasan akses dan tandai "custom" bila berbeda dari bawaan role.
    function recalculateSummary() {
        let granted = 0, readOnly = 0, noAccess = 0, isCustom = false;
        const role = roleSelect ? roleSelect.value : '';
        const defaults = roleDefaults[role] || {};

        Object.keys(catalog).forEach((module) => {
            const active = catalog[module].filter((action) => {
                const el = checkbox(module, action);
                return el && el.checked;
            });

            if (active.length === 0) {
                noAccess++;
            } else if (active.length === 1 && ['view', 'view_user'].includes(active[0])) {
                readOnly++;
            } else {
                granted++;
            }

            catalog[module].forEach((action) => {
                const el = checkbox(module, action);
                const defaultChecked = !!(defaults[module] && defaults[module][action]);
                if (el && el.checked !== defaultChecked) isCustom = true;
            });

            const countBadge = document.getElementById(`module-count-${module}`);
            if (countBadge) countBadge.textContent = `${active.length} aktif`;
        });

        document.getElementById('summary-granted').textContent = granted;
        document.getElementById('summary-readonly').textContent = readOnly;
        document.getElementById('summary-noaccess').textContent = noAccess;

        if (customBadge) {
            customBadge.textContent = isCustom ? 'Aktif' : 'Tidak';
            customBadge.className = 'badge ms-1 ' + (isCustom ? 'bg-warning text-dark' : 'bg-secondary');
        }
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', function () {
            if (roleBadge) {
                roleBadge.textContent = this.value
                    ? this.value.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
                    : '-';
            }
            applyDefaultsForRole(this.value);
        });
    }

    section.querySelectorAll('.permission-checkbox').forEach((el) => {
        el.addEventListener('change', recalculateSummary);
    });

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            applyDefaultsForRole(roleSelect ? roleSelect.value : '');
        });
    }

    recalculateSummary();
})();
