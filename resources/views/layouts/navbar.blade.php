<nav class="app-topbar navbar navbar-expand-lg border-bottom bg-white px-3 px-md-4 py-2">
    <div class="container-fluid p-0">

        {{-- Tombol buka sidebar --}}
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

            {{-- Ticker notifikasi, sumber data sama dengan dropdown lonceng --}}
            @if(auth()->user()->hasRole('super_admin', 'admin'))
                <div class="rounded-pill" id="navbarNotifTicker">
                    <div class="d-flex align-items-center gap-2" id="navbarNotifTickerContent"></div>
                </div>
            @endif
        </div>

        <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">

            {{-- Notifikasi navbar - Super Admin & Admin saja --}}
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

    #navbarNotifTicker {
        height: 40px;
        padding: 0 8px;
        display: none;
        align-items: center;
        white-space: nowrap;
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

        function paintTickerItem(n) {
            tickerContentEl.innerHTML = `<i class="bi ${n.icon}"></i><span>${escapeHtml(n.title)}: ${escapeHtml(n.message)}</span>`;
        }

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
        setInterval(loadNotifications, 60000);
    })();
</script>
@endif
