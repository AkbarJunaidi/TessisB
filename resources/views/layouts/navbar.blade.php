<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-2 shadow-sm">
    <div class="container-fluid p-0">
        <button class="btn btn-outline-secondary btn-sm me-3" id="menu-toggle">
            <i class="bi bi-list fs-5"></i>
        </button>

        <span class="navbar-text fw-medium text-secondary d-none d-md-inline">
            Tesis
        </span>

        @if(auth()->user()->hasRole('super_admin', 'admin'))
        {{-- Ticker notifikasi - cuma di layar lebar (lg+) supaya navbar tidak
             sesak di tablet/mobile; isi yang sama tetap bisa dilihat lewat
             tombol lonceng di semua ukuran layar. --}}
        <div class="notif-ticker-wrap flex-grow-1 mx-3 d-none d-lg-flex" id="notifTickerWrap">
            <div class="notif-ticker" id="notifTicker"></div>
        </div>
        @endif

        <div class="ms-auto d-flex align-items-center gap-2">

            @if(auth()->user()->hasRole('super_admin', 'admin'))
            {{-- Bell notifikasi --}}
            <div class="dropdown">
                <button
                    class="btn btn-outline-secondary btn-sm position-relative"
                    type="button"
                    id="notifBellBtn"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    title="Notifikasi"
                >
                    <i class="bi bi-bell fs-6"></i>
                    <span class="notif-badge badge rounded-pill bg-danger d-none" id="notifBadge">0</span>
                </button>

                <div class="dropdown-menu dropdown-menu-end shadow-sm p-0 notif-dropdown-menu" aria-labelledby="notifBellBtn">
                    <div class="px-3 py-2 border-bottom fw-semibold small text-secondary text-uppercase">
                        Notifikasi Aktif
                    </div>
                    <div id="notifDropdownList">
                        <div class="px-3 py-3 text-muted small text-center">
                            Tidak ada notifikasi aktif.
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!--komponen waktu-->
            <div class="d-flex align-items-center bg-light border rounded-pill px-3 py-1 shadow-sm gap-2">
                <!--tanggal dan waktu-->
                <div class="d-flex flex-column text-end" style="line-height: 1.1;">
                        <span class="fw-semibold text-dark" style="font-size: 0.85rem;" id="realtime-date">
                            Memuat tanggal...
                        </span>
                    <span class="text-muted fw-bold" style="font-size: 0.75rem;" id="realtime-clock">
                        --:--:--
                    </span>
                </div>
            </div>
        </div>
    </div>
</nav>

@if(auth()->user()->hasRole('super_admin', 'admin'))
<style>
    /* ==== Ticker notifikasi (navbar) ==== */
    .notif-ticker-wrap {
        min-width: 0;
        max-width: 420px;
    }

    .notif-ticker {
        position: relative;
        height: 26px;
        overflow: hidden;
        perspective: 500px;
    }

    .notif-ticker-item {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        color: #495057;
        text-decoration: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transform-origin: 50% 100%;
        transform: rotateX(90deg) translateY(100%);
        opacity: 0;
        transition: transform .45s cubic-bezier(.4, 0, .2, 1), opacity .3s ease;
    }

    .notif-ticker-item:hover {
        color: #0d6efd;
    }

    .notif-ticker-item.is-active {
        transform: rotateX(0deg) translateY(0);
        opacity: 1;
    }

    .notif-ticker-item.is-leaving {
        transform: rotateX(-90deg) translateY(-100%);
        opacity: 0;
    }

    /* ==== Bell & dropdown notifikasi ==== */
    .notif-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        font-size: 0.65rem;
        min-width: 1.1rem;
        padding: 0.2rem 0.4rem;
        line-height: 1;
    }

    .notif-dropdown-menu {
        width: 320px;
        max-width: 90vw;
        max-height: 360px;
        overflow-y: auto;
    }

    .notif-dropdown-item {
        display: block;
        padding: 0.65rem 1rem;
        text-decoration: none;
        border-bottom: 1px solid #f1f3f5;
        color: #212529;
    }

    .notif-dropdown-item:last-child {
        border-bottom: none;
    }

    .notif-dropdown-item:hover {
        background-color: #f8f9fa;
    }

    .notif-dropdown-item .notif-title {
        font-weight: 600;
        font-size: 0.8rem;
        display: block;
    }

    .notif-dropdown-item .notif-message {
        font-size: 0.75rem;
        color: #6c757d;
        display: block;
        margin-top: 2px;
    }
</style>
@endif

<!--waktu nyata-->
<script>
    function updateDateTime()
    {
        const now = new Date();
        const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = now.toLocaleDateString('id-ID', dateOptions);
        const timeOptions = { hour: '2-digit', minute: '2-digit', hour12: false };
        const formattedTime = now.toLocaleTimeString('id-ID', timeOptions);

        document.getElementById('realtime-date').textContent = formattedDate;
        document.getElementById('realtime-clock').textContent = formattedTime + ' WIB';
    }

    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>

@if(auth()->user()->hasRole('super_admin', 'admin'))
<script>
/**
 * Widget Notifikasi Navbar (Super Admin & Admin saja).
 *
 * Terdiri dari 2 bagian yang berbagi data yang sama (hasil fetch sekali):
 * 1. Ticker      - baris teks yang bergantian tiap beberapa detik dengan
 *                   animasi "cube slide" dari bawah ke atas (hanya layar lebar).
 * 2. Bell dropdown - daftar lengkap semua notifikasi aktif (semua ukuran layar).
 *
 * Kalau nanti mau menambah jenis notifikasi baru, TIDAK perlu mengubah kode
 * di sini - cukup tambahkan builder baru di NotificationService (backend),
 * widget ini otomatis menampilkannya karena hanya membaca array JSON generik.
 */
(function () {
    const tickerWrap    = document.getElementById('notifTickerWrap');
    const ticker        = document.getElementById('notifTicker');
    const bellBtn        = document.getElementById('notifBellBtn');
    const badge          = document.getElementById('notifBadge');
    const dropdownList   = document.getElementById('notifDropdownList');

    // Widget ini hanya dirender di HTML untuk Super Admin & Admin (lihat
    // pengecekan role di atas). Kalau elemennya tidak ada, hentikan script di sini.
    if (!bellBtn) {
        return;
    }

    const TICKER_INTERVAL_MS = 4500;       // jeda gonta-ganti ticker
    const POLL_INTERVAL_MS   = 5 * 60 * 1000; // refresh data notifikasi tiap 5 menit

    let notifications = [];
    let tickerIndex    = -1;
    let tickerTimer     = null;

    /**
     * title/message notifikasi berasal dari nama Project/Client yang diinput
     * pengguna - selalu di-escape sebelum dirender lewat innerHTML supaya
     * tidak jadi celah XSS kalau ada karakter HTML di nama project/client.
     */
    function escapeHtml(text)
    {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function fetchNotifications()
    {
        fetch('{{ route('notifications.active') }}', {
            headers: { 'Accept': 'application/json' },
        })
            .then((response) => response.json())
            .then((data) => {
                notifications = data.notifications || [];
                renderBadgeAndDropdown();
                startTicker();
            })
            .catch(() => {
                // Notifikasi bukan fitur kritis - diam saja kalau gagal fetch,
                // jangan ganggu pengguna dengan error di navbar.
            });
    }

    function renderBadgeAndDropdown()
    {
        if (notifications.length === 0) {
            badge.classList.add('d-none');
            dropdownList.innerHTML = '<div class="px-3 py-3 text-muted small text-center">Tidak ada notifikasi aktif.</div>';
            return;
        }

        badge.textContent = notifications.length;
        badge.classList.remove('d-none');

        dropdownList.innerHTML = notifications.map((notif) => `
            <a href="${notif.url}" class="notif-dropdown-item">
                <span class="notif-title"><i class="bi ${notif.icon} me-1"></i>${escapeHtml(notif.title)}</span>
                <span class="notif-message">${escapeHtml(notif.message)}</span>
            </a>
        `).join('');
    }

    // ---- Ticker: animasi cube-slide bawah ke atas, gantian berkala ----

    function startTicker()
    {
        if (tickerTimer) {
            clearInterval(tickerTimer);
            tickerTimer = null;
        }

        if (!ticker) {
            return; // ticker tidak dirender di layar kecil
        }

        if (notifications.length === 0) {
            ticker.innerHTML = '';
            tickerWrap?.classList.add('d-none');
            return;
        }

        tickerWrap?.classList.remove('d-none');
        tickerIndex = -1;
        showNextTickerItem();

        if (notifications.length > 1) {
            tickerTimer = setInterval(showNextTickerItem, TICKER_INTERVAL_MS);
        }
    }

    function showNextTickerItem()
    {
        tickerIndex = (tickerIndex + 1) % notifications.length;
        const notif = notifications[tickerIndex];

        // Elemen lama diputar "menjauh" ke atas (cube rotate) lalu dibuang
        // setelah animasinya selesai.
        const outgoing = ticker.querySelector('.notif-ticker-item.is-active');
        if (outgoing) {
            outgoing.classList.remove('is-active');
            outgoing.classList.add('is-leaving');
            outgoing.addEventListener('transitionend', () => outgoing.remove(), { once: true });
        }

        // Elemen baru dibuat dari posisi "di bawah" (rotateX 90deg), lalu
        // diputar masuk ke posisi normal begitu class is-active ditambahkan.
        const el = document.createElement('a');
        el.href = notif.url;
        el.className = 'notif-ticker-item';
        el.innerHTML = `<i class="bi ${notif.icon}"></i><span>${escapeHtml(notif.message)}</span>`;
        ticker.appendChild(el);

        // Paksa reflow supaya browser mendaftarkan posisi awal SEBELUM
        // is-active ditambahkan - tanpa ini transisinya tidak akan terlihat.
        void el.offsetWidth;
        el.classList.add('is-active');
    }

    fetchNotifications();
    setInterval(fetchNotifications, POLL_INTERVAL_MS);
})();
</script>
@endif
