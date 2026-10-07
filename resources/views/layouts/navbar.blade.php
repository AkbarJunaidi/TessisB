<nav class="app-topbar navbar navbar-expand-lg border-bottom bg-white px-3 px-md-4 py-2">
    <div class="container-fluid p-0 flex-nowrap">

        <div class="d-flex align-items-center gap-4" style="min-width:0;">
            <div style="min-width:0;">
                <span class="fw-semibold text-navy d-block navbar-page-title" style="font-size:.95rem;">
                    @yield('title', 'Dashboard')
                </span>
                <span class="text-muted d-none d-sm-block" style="font-size:.72rem;">
                    Sistem Informasi Manajemen
                </span>
            </div>

            {{-- Ticker notifikasi (sumber sama dengan lonceng) untuk semua role; jenis yang tampil disaring per user di NotificationService. --}}
                <div class="rounded-pill" id="navbarNotifTicker">
                    <div class="d-flex align-items-center gap-2" id="navbarNotifTickerContent"></div>
                </div>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2 gap-md-3 flex-shrink-0">

            {{-- Ikon pencarian global: membuka baris pencarian tepat di bawah navbar (#navbarSearchBar). --}}
                <button type="button" class="btn btn-light border rounded-circle d-flex align-items-center justify-content-center navbar-search-btn"
                        style="width: 38px; height: 38px;"
                        id="navbarSearchToggle"
                        data-bs-toggle="collapse" data-bs-target="#navbarSearchBar"
                        aria-expanded="false" aria-controls="navbarSearchBar"
                        aria-label="Buka pencarian">
                    <i class="bi bi-search fs-6"></i>
                </button>

            {{-- Notifikasi navbar untuk semua role; jenis yang tampil disaring per user di NotificationService. --}}
                <div class="dropdown">
                    <button type="button" class="btn btn-light border rounded-circle position-relative d-flex align-items-center justify-content-center navbar-notif-btn"
                            style="width: 38px; height: 38px;"
                            id="navbarNotifBtn"
                            data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                        <i class="bi bi-bell fs-6"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="navbarNotifBadge" style="font-size: .6rem;"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm p-0" style="width: 320px; max-width: 90vw;">
                        <div class="px-3 py-2 border-bottom fw-semibold small text-dark">Notifikasi</div>
                        <div id="navbarNotifList" style="max-height: 360px; overflow-y: auto;">
                            <div class="text-center text-muted small py-4" id="navbarNotifLoading">Memuat notifikasi...</div>
                        </div>
                    </div>
                </div>

            {{-- Komponen waktu real-time --}}
            <div class="d-none d-sm-flex align-items-center bg-light border rounded-pill px-3 py-1 gap-2">
                <i class="bi bi-clock text-primary"></i>
                <div class="d-flex flex-column text-end lh-sm">
                    <span class="fw-semibold text-dark" style="font-size:.8rem;" id="realtime-date">Memuat tanggal...</span>
                    <span class="text-muted fw-medium" style="font-size:.72rem;" id="realtime-clock">--:--:--</span>
                </div>
            </div>
        </div>
    </div>
</nav>

{{-- Baris pencarian global (collapse di bawah navbar): ketik -> saran Project/Inventory/Kontak/Surat Jalan (search.suggest) -> klik ke detail.
     Tanpa saran cocok, Enter/Cari hanya menandai kolom invalid dan tidak pindah halaman. --}}
<div class="collapse" id="navbarSearchBar">
    <div class="border-bottom bg-white px-3 px-md-4 py-2">
        <div class="position-relative" style="max-width: 480px;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-search text-muted"></i>
                <input
                    type="search"
                    id="navbarSearchInput"
                    class="form-control form-control-sm border-0 shadow-none"
                    placeholder="Cari project, inventory, kontak, surat jalan..."
                    autocomplete="off"
                >
                <button type="button" id="navbarSearchGoBtn" class="btn btn-sm btn-primary flex-shrink-0">
                    Cari
                </button>
            </div>

            <div id="navbarSearchInvalid" class="text-danger small mt-1 d-none">
                <i class="bi bi-exclamation-circle"></i>
                Tidak ada modul/halaman yang cocok dengan "<span id="navbarSearchInvalidKeyword"></span>".
            </div>

            <div id="navbarSearchSuggestions" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1050; top: 100%;"></div>
        </div>
    </div>
</div>

<style>
    /* Tinggi navbar eksplisit 64px = tinggi .sidebar-brand-row di sidebar agar garis border sejajar;
       min-height (bukan height) supaya boleh tumbuh bila konten lebih tinggi. */
    .app-topbar {
        min-height: 64px;
    }


    .app-topbar { position: sticky; top: 0; z-index: 1030; }
    .text-navy { color: var(--c-navy); }

    /* Judul halaman navbar dipotong dengan '...' bila terlalu panjang; prefiks tetap dipertahankan selama muat. */
    .navbar-page-title {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    #navbarNotifTicker {
        height: 40px;
        padding: 0 8px;
        display: none;
        align-items: center;
        white-space: nowrap;
        margin-right: 1.5rem; /* jarak tetap ke ikon lonceng, tidak bergantung sisa ruang flex */
    }
    @media (min-width: 1156px) {
        #navbarNotifTicker.has-notif { display: flex; }
    }
    #navbarNotifTicker #navbarNotifTickerContent {
        font-size: .78rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 360px;
        transition: opacity .3s ease, transform .3s ease;
    }
    #navbarNotifTicker.has-notif {
        background-color: #fff1f0;
        border: 1px solid #ffd4d1;
        border-radius: 999px;
    }
    #navbarNotifTicker.has-notif #navbarNotifTickerContent {
        color: #b02a37;
    }
    #navbarNotifTicker.has-notif #navbarNotifTickerContent i {
        color: #dc3545;
    }

    #navbarSearchBar #navbarSearchInput:focus {
        box-shadow: none;
    }

    .navbar-notif-btn.has-notif {
        background-color: #fff1f0 !important;
        border-color: #ffb3ae !important;
        color: #dc3545;
        animation: navbarNotifPulse 2s ease-in-out infinite;
    }
    @keyframes navbarNotifPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, .25); }
        50% { box-shadow: 0 0 0 5px rgba(220, 53, 69, 0); }
    }
</style>

{{-- Typeahead dan penanda invalid pencarian: lihat komentar #navbarSearchBar di atas. --}}
<script>
    (function () {
        const searchBar        = document.getElementById('navbarSearchBar');
        const input            = document.getElementById('navbarSearchInput');
        const suggestionBox    = document.getElementById('navbarSearchSuggestions');
        const invalidBox       = document.getElementById('navbarSearchInvalid');
        const invalidKeywordEl = document.getElementById('navbarSearchInvalidKeyword');
        const goBtn            = document.getElementById('navbarSearchGoBtn');

        if (!searchBar || !input || !suggestionBox) return;

        const suggestUrl = @json(route('search.suggest'));

        let debounceTimer = null;
        let currentSuggestions = [];

        // Escape teks agar aman disisipkan ke HTML.
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text ?? '';
            return div.innerHTML;
        }

        // Sembunyikan saran pencarian.
        function hideSuggestions() {
            suggestionBox.classList.add('d-none');
            suggestionBox.innerHTML = '';
            currentSuggestions = [];
        }

        // Hapus tanda kolom pencarian tidak valid.
        function clearInvalid() {
            input.classList.remove('is-invalid');
            invalidBox.classList.add('d-none');
        }

        // Tandai kolom pencarian tidak valid beserta katanya.
        function showInvalid(keyword) {
            input.classList.add('is-invalid');
            invalidKeywordEl.textContent = keyword;
            invalidBox.classList.remove('d-none');
        }

        // Pindah halaman.
        function goTo(url) {
            window.location.href = url;
        }

        // Tampilkan saran pencarian dan simpan untuk Enter.
        function renderSuggestions(items) {
            currentSuggestions = items;

            if (!items.length) {
                suggestionBox.classList.add('d-none');
                suggestionBox.innerHTML = '';
                return;
            }

            suggestionBox.innerHTML = items.map((item) => `
                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 navbar-search-item" data-url="${escapeHtml(item.url)}">
                    <span class="text-truncate">
                        <i class="bi ${item.icon} text-primary me-2"></i>
                        <span class="fw-semibold">${escapeHtml(item.label)}</span>
                        <span class="text-muted small ms-1">${escapeHtml(item.subtitle)}</span>
                    </span>
                    <span class="badge bg-light text-muted border flex-shrink-0 ms-2">${escapeHtml(item.category)}</span>
                </button>
            `).join('');

            suggestionBox.classList.remove('d-none');

            suggestionBox.querySelectorAll('.navbar-search-item').forEach((btn) => {
                btn.addEventListener('click', function () {
                    goTo(this.dataset.url);
                });
            });
        }

        // Enter/tombol Cari: pakai saran teratas bila ada; bila tidak ada saran, tandai kolom invalid dan tetap di halaman.
        function attemptSearch() {
            const keyword = input.value.trim();

            if (keyword === '') {
                return;
            }

            if (currentSuggestions.length > 0) {
                goTo(currentSuggestions[0].url);
                return;
            }

            showInvalid(keyword);
        }

        input.addEventListener('input', function () {
            const keyword = this.value.trim();

            clearInvalid();
            clearTimeout(debounceTimer);

            if (keyword.length < 2) {
                hideSuggestions();
                return;
            }

            debounceTimer = setTimeout(function () {
                fetch(`${suggestUrl}?q=${encodeURIComponent(keyword)}`, {
                    headers: { 'Accept': 'application/json' },
                })
                    .then((res) => res.json())
                    .then((data) => renderSuggestions(data.results || []))
                    .catch(() => renderSuggestions([]));
            }, 300);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                attemptSearch();
            }
        });

        if (goBtn) {
            goBtn.addEventListener('click', attemptSearch);
        }

        document.addEventListener('click', function (e) {
            if (!input.contains(e.target) && !suggestionBox.contains(e.target)) {
                suggestionBox.classList.add('d-none');
            }
        });

        searchBar.addEventListener('shown.bs.collapse', function () {
            input.focus();
        });

        // Setiap kali baris pencarian ditutup, reset total - biar pas
        // dibuka lagi tidak menampilkan sisa keyword/saran/invalid yang lama.
        searchBar.addEventListener('hidden.bs.collapse', function () {
            input.value = '';
            clearInvalid();
            hideSuggestions();
        });
    })();
</script>

{{-- Waktu nyata --}}
<script>
    // Perbarui tanggal dan jam di navbar.
    function updateDateTime() {
        const now = new Date();
        const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        document.getElementById('realtime-date').textContent = now.toLocaleDateString('id-ID', dateOptions);
        document.getElementById('realtime-clock').textContent = now.toLocaleTimeString('id-ID', timeOptions) + ' WIB';
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>

{{-- Script lonceng notifikasi - dijalankan untuk SEMUA role yang login
     (sebelumnya cuma super_admin/admin). --}}
<script>
    // Notifikasi navbar, poll berkala dari endpoint notifications.active
    (function () {
        const listEl = document.getElementById('navbarNotifList');
        const badgeEl = document.getElementById('navbarNotifBadge');
        const tickerEl = document.getElementById('navbarNotifTicker');
        const tickerContentEl = document.getElementById('navbarNotifTickerContent');
        const notifBtn = document.getElementById('navbarNotifBtn');
        if (!listEl || !badgeEl) return;

        const notifUrl = @json(route('notifications.active'));
        let tickerTimer = null;
        let tickerIndex = 0;
        let tickerData = [];

        // Escape teks agar aman disisipkan ke HTML.
        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str == null ? '' : String(str);
            return div.innerHTML;
        }

        // Samakan badge notifikasi di bar bawah HP dengan jumlah di lonceng.
        function syncBottomBadge(count) {
            document.querySelectorAll('[data-notif-badge]').forEach(function (el) {
                el.textContent = count > 9 ? '9+' : count;
                el.classList.toggle('d-none', count === 0);
            });
        }

        // Render daftar notifikasi di dropdown lonceng dan perbarui badge.
        function renderNotifications(notifications) {
            syncBottomBadge(notifications.length);
            if (!notifications.length) {
                listEl.innerHTML = '<div class="text-center text-muted small py-4">Tidak ada notifikasi saat ini.</div>';
                badgeEl.classList.add('d-none');
                if (notifBtn) notifBtn.classList.remove('has-notif');
                return;
            }

            badgeEl.textContent = notifications.length > 9 ? '9+' : notifications.length;
            badgeEl.classList.remove('d-none');
            if (notifBtn) notifBtn.classList.add('has-notif');

            listEl.innerHTML = notifications.map(function (n) {
                return `
                    <a href="${n.url}" class="dropdown-item d-flex align-items-start gap-2 py-2 px-3 border-bottom text-wrap"
                       data-notif-id="${escapeHtml(n.id)}" data-notif-type="${escapeHtml(n.type)}">
                        <i class="bi ${n.icon} mt-1"></i>
                        <div class="small">
                            <div class="fw-semibold text-dark">${escapeHtml(n.title)}</div>
                            <div class="text-muted">${escapeHtml(n.message)}</div>
                        </div>
                    </a>
                `;
            }).join('');
        }

        // Pengumuman tersimpan di database: tandai dibaca dulu (tanpa menunggu), link tetap terbuka normal.
        listEl.addEventListener('click', function (e) {
            const link = e.target.closest('[data-notif-type="announcement"]');
            if (!link) return;

            fetch(`/announcements/${link.dataset.notifId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
        });

        // Tampilkan satu notifikasi di ticker.
        function paintTickerItem(n) {
            tickerContentEl.innerHTML = `<i class="bi ${n.icon}"></i><span>${escapeHtml(n.title)}: ${escapeHtml(n.message)}</span>`;
        }

        // Tampilkan notifikasi ticker ke-index dengan transisi.
        function showTickerItem(index) {
            if (!tickerContentEl || !tickerData.length) return;
            const n = tickerData[index];

            tickerContentEl.style.opacity = '0';
            tickerContentEl.style.transform = 'translateY(-6px)';

            setTimeout(function () {
                paintTickerItem(n);
                tickerContentEl.style.transform = 'translateY(6px)';
                requestAnimationFrame(function () {
                    tickerContentEl.style.opacity = '1';
                    tickerContentEl.style.transform = 'translateY(0)';
                });
            }, 300);
        }

        // Mulai ticker bergilir bila ada notifikasi; ulang timer lama.
        function setupTicker(notifications) {
            if (!tickerEl || !tickerContentEl) return;

            if (tickerTimer) {
                clearInterval(tickerTimer);
                tickerTimer = null;
            }

            tickerData = notifications;

            if (!notifications.length) {
                tickerEl.classList.remove('has-notif');
                tickerContentEl.innerHTML = '';
                return;
            }

            tickerEl.classList.add('has-notif');
            tickerIndex = 0;
            paintTickerItem(tickerData[0]);
            tickerContentEl.style.opacity = '1';
            tickerContentEl.style.transform = 'translateY(0)';

            if (tickerData.length > 1) {
                tickerTimer = setInterval(function () {
                    tickerIndex = (tickerIndex + 1) % tickerData.length;
                    showTickerItem(tickerIndex);
                }, 5000);
            }
        }

        // Ambil notifikasi aktif dari server lalu render dropdown dan ticker.
        function loadNotifications() {
            fetch(notifUrl, { headers: { 'Accept': 'application/json' } })
                .then((res) => {
                    if (!res.ok) throw new Error('Gagal memuat notifikasi.');
                    return res.json();
                })
                .then((data) => {
                    const notifications = data.notifications || [];
                    renderNotifications(notifications);
                    setupTicker(notifications);
                })
                .catch(() => {
                    listEl.innerHTML = '<div class="text-center text-muted small py-4">Gagal memuat notifikasi.</div>';
                });
        }

        loadNotifications();
        setInterval(function () { if (!document.hidden) loadNotifications(); }, 60000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) loadNotifications(); });
    })();
</script>
