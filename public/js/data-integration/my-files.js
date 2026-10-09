(function () {
    'use strict';

    const input    = document.getElementById('drvSearch');
    const noResult = document.getElementById('drvNoResult');
    if (!input) return;

    const rows   = Array.from(document.querySelectorAll('[data-drv-row]'));
    const groups = Array.from(document.querySelectorAll('[data-drv-group]'));

    input.addEventListener('input', function () {
        const q = input.value.trim().toLowerCase();
        let visible = 0;

        rows.forEach(function (row) {
            const match = q === '' || (row.dataset.search || '').indexOf(q) !== -1;
            row.classList.toggle('d-none', !match);
            if (match) visible++;
        });

        // Sembunyikan judul kelompok yang seluruh barisnya tersaring.
        groups.forEach(function (group) {
            let next = group.nextElementSibling;
            let hasVisible = false;
            while (next && !next.hasAttribute('data-drv-group')) {
                if (next.hasAttribute('data-drv-row') && !next.classList.contains('d-none')) hasVisible = true;
                next = next.nextElementSibling;
            }
            group.classList.toggle('d-none', !hasVisible);
        });

        noResult.classList.toggle('d-none', !(q !== '' && rows.length > 0 && visible === 0));
    });
})();
