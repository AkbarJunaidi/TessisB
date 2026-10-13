// Pencarian langsung di daftar yang tampil. Memakai delegasi agar tetap jalan setelah tab diganti lewat AJAX.
(function () {
    'use strict';

    function filter(input) {
        const q        = input.value.trim().toLowerCase();
        const rows     = Array.from(document.querySelectorAll('[data-drv-row]'));
        const groups   = Array.from(document.querySelectorAll('[data-drv-group]'));
        const noResult = document.getElementById('drvNoResult');
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

        if (noResult) noResult.classList.toggle('d-none', !(q !== '' && rows.length > 0 && visible === 0));
    }

    document.addEventListener('input', function (event) {
        if (event.target.id === 'drvSearch') filter(event.target);
    });

    // Enter tidak boleh mengirim ulang form filter.
    document.addEventListener('keydown', function (event) {
        if (event.target.id === 'drvSearch' && event.key === 'Enter') event.preventDefault();
    });
})();
