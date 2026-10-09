// Putar chevron saat Surat Jalan di-expand/collapse; selector '#sjDetail' mencakup tabel desktop dan kartu mobile.
document.querySelectorAll('[data-bs-target^="#sjDetail"]').forEach(function (row) {
    const targetId = row.getAttribute('data-bs-target');
    const collapseEl = document.querySelector(targetId);
    const chevron = row.querySelector('.bi-chevron-down');

    if (!collapseEl || !chevron) return;

    collapseEl.addEventListener('show.bs.collapse', () => chevron.classList.add('rotate-180'));
    collapseEl.addEventListener('hide.bs.collapse', () => chevron.classList.remove('rotate-180'));
});
