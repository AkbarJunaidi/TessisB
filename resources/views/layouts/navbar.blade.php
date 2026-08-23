<nav class="app-topbar navbar navbar-expand-lg border-bottom bg-white px-3 px-md-4 py-2">
    <div class="container-fluid p-0">

        {{-- Tombol buka sidebar: pakai atribut Bootstrap native (data-bs-toggle),
             otomatis accessible (aria, focus-trap, keyboard Esc) tanpa JS tambahan. --}}
        <button class="btn btn-light border rounded-3 me-3 d-lg-none" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar"
                aria-label="Buka menu navigasi">
            <i class="bi bi-list fs-5"></i>
        </button>

        <div class="d-flex align-items-center gap-3">
            <div>
                <span class="fw-semibold text-navy d-block" style="font-size:.95rem;">
                    @yield('title', 'Dashboard')
                </span>
                <span class="text-muted d-none d-sm-block" style="font-size:.72rem;">
                    Sistem Informasi Manajemen
                </span>
            </div>

            {{-- Ticker notifikasi - teks pesan notifikasi aktif bergulir (animasi
                 slide ke atas), berganti otomatis tiap beberapa detik. Sumber
                 datanya SAMA dengan dropdown lonceng di kanan (satu fetch dipakai
                 bersama, tidak dobel request) - lihat script navbarNotif di bawah.
                 Sengaja ditaruh NEMPEL di sebelah judul (dipisah garis "|"), bukan
                 melebar ke tengah navbar - dan kapsulnya dibuat sepadan dengan
                 kapsul jam di kanan (px-3 py-1 rounded-pill), bukan teks polos.
                 Cuma tampil di layar lebar (ruang navbar sempit di mobile, dan
                 mobile sudah punya bottom-nav sendiri untuk fokus konten). --}}
            @if(auth()->user()->hasRole('super_admin', 'admin'))
                <div class="vr d-none d-lg-block align-self-stretch" style="opacity: .15;"></div>
                <div class="d-none d-lg-flex align-items-center rounded-pill px-3 py-1 position-relative overflow-hidden"
                     id="navbarNotifTicker" style="height: 40px; width: 380px; flex-shrink: 0; visibility: hidden;"></div>
            @endif
        </div>

        <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">

            {{-- Notifikasi navbar - Super Admin & Admin saja (sama seperti gate
                 di NotificationController), supaya Employee tidak memicu fetch
                 yang toh akan ditolak 403 oleh backend. --}}
            @if(auth()->user()->hasRole('super_admin', 'admin'))
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
            @endif

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

<style>
    .app-topbar { position: sticky; top: 0; z-index: 1030; }
    .text-navy { color: var(--c-navy); }

    /* Ticker notifikasi navbar - tiap pesan ditumpuk absolute di posisi yang
       sama, lalu digeser masuk/keluar lewat transform (slide ke atas).
       Pakai transition CSS biasa, bukan library animasi eksternal. */
    #navbarNotifTicker .ticker-item {
        position: absolute;
        padding-left: 12px;
        inset: 0;
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .78rem;
        color: var(--c-navy);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        opacity: 0;
        transform: translateY(100%);
        transition: transform .4s ease, opacity .4s ease;
        pointer-events: none;
    }
    #navbarNotifTicker .ticker-item.active {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
    #navbarNotifTicker .ticker-item.leaving {
        opacity: 0;
        transform: translateY(-100%);
    }
    #navbarNotifTicker .ticker-item span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #navbarNotifTicker.has-notif {
        /* border-radius & padding sekarang dari class Bootstrap (rounded-pill
           px-3 py-1) langsung di elemennya - biar sepadan persis dengan
           kapsul jam di sebelahnya, satu sumber ukuran yang sama. Di sini
           tinggal warnanya saja. */
        background-color: #fff1f0;
        border: 1px solid #ffd4d1;
    }
    #navbarNotifTicker.has-notif .ticker-item {
        color: #b02a37;
    }
    #navbarNotifTicker.has-notif .ticker-item i {
        color: #dc3545;
    }

    /* Ikon lonceng ikut "menyala" (bukan cuma badge angkanya) begitu ada
       notifikasi aktif - supaya lebih kerasa ada sesuatu yang perlu
       diperhatikan, bukan cuma ikon netral seperti biasa. */
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

{{-- Waktu nyata --}}
<script>
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

@if(auth()->user()->hasRole('super_admin', 'admin'))
<script>
    // Notifikasi navbar - fetch dari endpoint yang sudah ada
    // (notifications.active), di-poll berkala. Data selalu dihitung
    // langsung oleh backend (bukan read/unread tersimpan), jadi cukup
    // render ulang seluruh list tiap fetch, tidak perlu state tambahan.
    (function () {
        const listEl = document.getElementById('navbarNotifList');
        const badgeEl = document.getElementById('navbarNotifBadge');
        const tickerEl = document.getElementById('navbarNotifTicker');
        const notifBtn = document.getElementById('navbarNotifBtn');
        if (!listEl || !badgeEl) return;

        const notifUrl = @json(route('notifications.active'));
        let tickerTimer = null;
        let tickerIndex = 0;

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str == null ? '' : String(str);
            return div.innerHTML;
        }

        function renderNotifications(notifications) {
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
                    <a href="${n.url}" class="dropdown-item d-flex align-items-start gap-2 py-2 px-3 border-bottom text-wrap">
                        <i class="bi ${n.icon} mt-1"></i>
                        <div class="small">
                            <div class="fw-semibold text-dark">${escapeHtml(n.title)}</div>
                            <div class="text-muted">${escapeHtml(n.message)}</div>
                        </div>
                    </a>
                `;
            }).join('');
        }

        // Ticker teks bergulir di navbar - pakai data notifikasi yang SAMA
        // dengan dropdown lonceng di atas (satu fetch dipakai bersama).
        function setupTicker(notifications) {
            if (!tickerEl) return;

            if (tickerTimer) {
                clearInterval(tickerTimer);
                tickerTimer = null;
            }

            if (!notifications.length) {
                tickerEl.style.visibility = 'hidden';
                tickerEl.classList.remove('has-notif');
                tickerEl.innerHTML = '';
                return;
            }

            tickerEl.classList.add('has-notif');
            tickerEl.innerHTML = notifications.map(function (n) {
                return `
                    <div class="ticker-item">
                        <i class="bi ${n.icon}"></i>
                        <span>${escapeHtml(n.title)}: ${escapeHtml(n.message)}</span>
                    </div>
                `;
            }).join('');

            tickerEl.style.visibility = 'visible';
            tickerIndex = 0;

            const items = tickerEl.querySelectorAll('.ticker-item');
            items[0].classList.add('active');

            if (items.length > 1) {
                tickerTimer = setInterval(function () {
                    const current = items[tickerIndex];
                    const nextIndex = (tickerIndex + 1) % items.length;
                    const next = items[nextIndex];

                    current.classList.remove('active');
                    current.classList.add('leaving');
                    next.classList.add('active');

                    setTimeout(function () {
                        current.classList.remove('leaving');
                    }, 400); // samakan dengan durasi transition di CSS

                    tickerIndex = nextIndex;
                }, 5000);
            }
        }

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
        setInterval(loadNotifications, 60000); // poll tiap 60 detik
    })();
</script>
@endif
