// Kondisi barang: rincian servis dimuat saat baris dibuka, lalu disimpan di panelnya.
// Memakai delegasi agar tetap jalan setelah tab diganti lewat AJAX.
(function () {
    'use strict';

    async function toggle(button) {
        const detail = button.closest('tr').nextElementSibling;
        const panel = detail.querySelector('.kondisi-panel');
        const opening = detail.classList.contains('d-none');

        detail.classList.toggle('d-none', !opening);
        button.setAttribute('aria-expanded', String(opening));
        button.querySelector('i').className = opening ? 'bi bi-chevron-up' : 'bi bi-chevron-down';

        if (!opening || panel.dataset.loaded) {
            return;
        }

        panel.innerHTML = '<div class="text-muted small">Memuat rincian...</div>';

        try {
            const response = await fetch(button.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });

            if (!response.ok) {
                throw new Error(response.status);
            }

            panel.innerHTML = await response.text();
            panel.dataset.loaded = '1';
        } catch (error) {
            panel.innerHTML = '<div class="text-danger small">Rincian gagal dimuat. Tutup lalu buka lagi.</div>';
        }
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.js-kondisi-toggle');
        if (button) toggle(button);
    });

    // Dari tautan satu barang (Daftar Harga), rinciannya langsung terbuka.
    function autoOpen() {
        const buttons = document.querySelectorAll('.js-kondisi-toggle');
        if (buttons.length === 1 && buttons[0].dataset.autoopen === '1') toggle(buttons[0]);
    }

    document.addEventListener('DOMContentLoaded', autoOpen);
    document.addEventListener('ajax-tabs:loaded', autoOpen);
})();
