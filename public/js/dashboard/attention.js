// Kartu "Perlu perhatian" di Dashboard: buka/tutup awalnya ditentukan server (class "show"); file ini hanya
// menyelaraskan ikon dan ringkasan saat dilipat atau dibuka.
(function () {
    var panel = document.getElementById('attentionCollapse');
    var btn = document.getElementById('attentionToggle');
    if (!panel || !btn) return;

    var icon = btn.querySelector('.attention-toggle-icon');
    var summary = document.getElementById('attentionSummary');

    // Ringkasan hanya tampil saat kartu tertutup agar tidak dobel dengan isi kartu.
    function render(open) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        icon.className = 'bi attention-toggle-icon ' + (open ? 'bi-chevron-up' : 'bi-chevron-down');
        summary.classList.toggle('d-none', open);
    }

    panel.addEventListener('show.bs.collapse', function () { render(true); });
    panel.addEventListener('hide.bs.collapse', function () { render(false); });
})();
