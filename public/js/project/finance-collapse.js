// Kartu Data Keuangan di Detail Project: buka/tutup awalnya ditentukan server (class "show"), file ini hanya
// menyelaraskan ikon + ringkasan Laba Bersih saat dilipat/dibuka dan mengarahkan layar bila datang dari notifikasi.
(function () {
    var panel = document.getElementById('financeCollapse');
    var btn = document.getElementById('financeToggle');
    if (!panel || !btn) return;

    var icon = btn.querySelector('.finance-toggle-icon');
    var summary = document.getElementById('financeSummary');

    // Ringkasan Laba Bersih hanya tampil saat kartu tertutup agar tidak dobel dengan isi kartu.
    function render(open) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        icon.className = 'bi finance-toggle-icon ' + (open ? 'bi-chevron-up' : 'bi-chevron-down');
        summary.classList.toggle('d-none', open);
    }

    panel.addEventListener('show.bs.collapse', function () { render(true); });
    panel.addEventListener('hide.bs.collapse', function () { render(false); });

    // Datang dari notifikasi (?buka=keuangan): margin atas menyisakan ruang navbar sticky.
    if (panel.getAttribute('data-scroll-to') === '1') {
        document.addEventListener('DOMContentLoaded', function () {
            var card = btn.closest('.card');
            card.style.scrollMarginTop = '5rem';
            card.scrollIntoView({ block: 'start' });
        });
    }
})();
