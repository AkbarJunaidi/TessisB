// Pindah ke tab Surat Jalan dengan memicu tombol tab aslinya (agar Bootstrap menonaktifkan tab aktif), lalu scroll.
function goToSuratJalanTab() {
    const realTabButton = document.querySelector('#projectTabs [data-bs-target="#tab-suratjalan"]');
    if (realTabButton) {
        bootstrap.Tab.getOrCreateInstance(realTabButton).show();
    }

    setTimeout(function () {
        // Target scroll: .tab-content.
        const tabsSection = document.getElementById('projectTabContent');
        if (tabsSection) tabsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 50);
}

document.querySelectorAll('.go-to-surat-jalan-tab').forEach(function (link) {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        goToSuratJalanTab();
    });
});

// Buka tab dari URL #tab-suratjalan. Dibungkus DOMContentLoaded karena bootstrap.bundle.js
// dimuat setelah blok ini; tanpa itu `bootstrap` masih undefined dan gagal diam-diam.
document.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash;
    if (hash === '#tab-suratjalan') {
        goToSuratJalanTab();
    } else if (/^#tab-[a-z]+$/.test(hash)) {
        // Tab lain (mis. #tab-dokumen setelah unggah) dibuka langsung dari hash.
        const trigger = document.querySelector('[data-bs-target="' + hash + '"]');
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }
});

// Modal Kirim Notifikasi: pilih semua, hitung terpilih, tombol kirim aktif bila ada penerima.
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('projectNotifyModal');
    if (!modal) return;

    const all = modal.querySelector('#notifyAll');
    const boxes = Array.from(modal.querySelectorAll('.notify-user'));
    const count = modal.querySelector('#notifyCount');
    const submit = modal.querySelector('button[type="submit"]');

    const sync = function () {
        const n = boxes.filter(function (b) { return b.checked; }).length;
        count.textContent = n + ' dipilih';
        submit.disabled = n === 0;
        all.checked = n === boxes.length;
        all.indeterminate = n > 0 && n < boxes.length;
    };

    all.addEventListener('change', function () {
        boxes.forEach(function (b) { b.checked = all.checked; });
        sync();
    });
    boxes.forEach(function (b) { b.addEventListener('change', sync); });
    modal.addEventListener('hidden.bs.modal', function () {
        modal.querySelector('form').reset();
        sync();
    });
    sync();
});
