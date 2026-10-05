<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Management Information System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    {{-- Design system terpusat: token warna, radius, shadow, komponen reusable --}}
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body>

    {{-- Terapkan status collapse sidebar SEBELUM sisa halaman digambar,
         biar tidak ada kedipan "kebuka dulu baru collapse" (FOUC) saat
         reload/pindah halaman. Cuma berpengaruh ke tampilan >=992px lewat
         CSS media query di app-sidebar/sidebar.blade.php - di HP/tablet
         localStorage ini tidak pernah dibaca sama sekali (sidebar di sana
         selalu pakai mekanisme offcanvas normal). --}}
    <script>
        if (localStorage.getItem('sidebarCollapsed') === '1') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>

    {{-- ==========================================================
         APP SHELL: sidebar statis di desktop (>=992px), berubah
         jadi offcanvas asli Bootstrap di layar sempit (aksesibel,
         keyboard-friendly, ada focus-trap otomatis dari Bootstrap).
         ========================================================== --}}
    <div class="app-shell">

        {{-- SIDEBAR --}}
        <div class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
            @include('layouts.sidebar')
        </div>

        {{-- KONTEN UTAMA --}}
        <div class="app-main">
            @include('layouts.navbar')

            <main class="app-content">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Toast notifikasi global (auto-hilang, mengambang, tidak mendorong konten halaman).
         Dipakai di SELURUH halaman - jangan tambahkan alert session('success')/session('error')
         lokal lagi di masing-masing view, cukup andalkan ini. --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
        @if(session('success'))
            <div class="toast align-items-center text-bg-success border-0 shadow" role="alert" data-bs-autohide="true" data-bs-delay="4000" id="globalToastSuccess">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="toast align-items-center text-bg-danger border-0 shadow" role="alert" data-bs-autohide="true" data-bs-delay="6000" id="globalToastError">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
    </div>

    {{-- Shell desktop setinggi 1 layar: sidebar dan konten masing-masing scroll sendiri,
         halaman browser tidak ikut scroll. Mobile tetap offcanvas (scroll bawaan Bootstrap). --}}
    <style>
        .app-shell {
            display: flex;
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
        }

        .app-sidebar {
            width: 272px;
            background: linear-gradient(180deg, var(--c-navy) 0%, var(--c-navy-2) 100%);
            border-right: 1px solid rgba(255,255,255,.06);
            transition: width .2s ease;
        }

        .app-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            max-height: 100vh;
            max-height: 100dvh;
            overflow-y: auto;
        }

        .app-content {
            flex: 1;
            padding: 1.5rem;
        }
        @media (min-width: 768px) {
            .app-content { padding: 2rem 2rem 2.5rem; }
        }

        @media (min-width: 992px) {
            html.sidebar-collapsed .app-sidebar { width: 88px; }

            .app-shell {
                height: 100vh;
                height: 100dvh;
                min-height: 0;
                overflow: hidden;
            }

            /* Hanya daftar menu yang scroll. Logo dan profil tetap di tempat, jadi
               scrollbar tidak menyempitkan atau memotong area logo. */
            .app-sidebar {
                flex-shrink: 0;
                height: 100%;
                overflow: hidden;
            }
            .app-sidebar .sidebar-body {
                height: 100% !important;
                min-height: 0;
            }
            /* Bootstrap .nav = flex-wrap: wrap; dengan tinggi terbatas, menu pecah jadi 2 kolom. */
            .app-sidebar .sidebar-nav {
                flex: 1 1 auto;
                flex-wrap: nowrap;
                min-height: 0;
                overflow-x: hidden;
                overflow-y: auto;
                scrollbar-width: thin;
                scrollbar-color: transparent transparent;
            }
            .app-sidebar .sidebar-nav:hover { scrollbar-color: rgba(255,255,255,.28) transparent; }
            /* Hanya daftar menu yang boleh menyusut; baris brand harus tetap 64px sama dengan navbar. */
            .app-sidebar .sidebar-body > :not(.sidebar-nav) { flex-shrink: 0; }
            .app-sidebar .sidebar-nav > li { flex-shrink: 0; }

            /* Baris brand: nama 1 baris (ellipsis dari theme.css), logo diberi tepi kosong
               (tidak di-crop), tombol tutup sedikit menjorok ke kanan. */
            .app-sidebar .sidebar-brand-row { padding-inline: 0 !important; gap: .5rem; }
            .app-sidebar .sidebar-brand-link { gap: .375rem; }
            .app-sidebar .sidebar-brand-icon {
                height: auto;
                width: 44px;
                aspect-ratio: auto;
                object-fit: contain;
                margin-inline: .25rem .125rem;
            }
            .app-sidebar .sidebar-collapse-toggle { margin-right: -.25rem; }

            /* Rail 88px: baris dilebarkan, logo di tengah. */
            html.sidebar-collapsed .app-sidebar .sidebar-brand-row { margin-inline: -.5rem; }
            html.sidebar-collapsed .app-sidebar .sidebar-brand-link { justify-content: center; }
            html.sidebar-collapsed .app-sidebar .sidebar-brand-icon { margin-inline: 0; }

            .app-main {
                height: 100%;
                max-height: none;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Tampilkan toast notifikasi global (kalau ada) - auto-hilang sendiri
            document.querySelectorAll('.toast-container .toast').forEach(function (toastEl) {
                new bootstrap.Toast(toastEl).show();
            });

            // ==================================================================
            // Sidebar collapse (icon-only) - HANYA berefek di desktop >=992px
            // lewat CSS (lihat .app-sidebar & html.sidebar-collapsed di atas +
            // di sidebar.blade.php). Ada 2 cara sidebar bisa expand:
            //   1. Manual (PERMANEN, disimpan ke localStorage, tetap expand
            //      setelah reload/pindah halaman) - lewat 2 pemicu:
            //      a. Tombol chevron - HANYA muncul saat sudah expanded,
            //         untuk menutup.
            //      b. Klik logo - HANYA aktif saat collapsed (tombol
            //         terpisah sengaja tidak ada lagi di kondisi ini,
            //         logo yang ambil alih fungsinya - hover/fokus logo
            //         menampilkan ikon panah, lihat CSS di sidebar.blade.php).
            //         Saat sudah expanded, logo balik jadi link biasa ke
            //         Dashboard.
            //   2. Peek - klik salah satu ikon dropdown (Inventory/Progress
            //      Management/dst) SAAT sedang collapsed, SEMENTARA saja
            //      (tidak disimpan ke localStorage) - begitu klik di luar
            //      sidebar atau kursor keluar dari area sidebar, otomatis
            //      balik collapsed lagi seperti semula.
            // ==================================================================
            var sidebarEl = document.querySelector('.app-sidebar');
            var sidebarToggleBtn = document.getElementById('sidebarCollapseToggle');
            var sidebarBrandLink = document.getElementById('sidebarBrandLink');

            if (sidebarEl && sidebarToggleBtn) {
                var accordionToggles = document.querySelectorAll('#sidebarMenuAccordion [data-bs-toggle="collapse"]');
                var peekExpanded = false; // true = sidebar kebuka gara-gara peek, bukan preferensi permanen

                // Submenu tidak ada tempat menampilkan teksnya saat rail
                // collapsed - data-bs-toggle Bootstrap dilepas sementara
                // supaya klik ikon parent tidak diam-diam nge-toggle submenu
                // yang toh disembunyikan CSS. Dipasang lagi begitu interaktif
                // (baik lewat toggle manual maupun peek).
                function setAccordionInteractive(interactive) {
                    accordionToggles.forEach(function (link) {
                        if (interactive && link.dataset.bsToggleBackup) {
                            link.setAttribute('data-bs-toggle', link.dataset.bsToggleBackup);
                            delete link.dataset.bsToggleBackup;
                        } else if (!interactive && link.getAttribute('data-bs-toggle')) {
                            link.dataset.bsToggleBackup = link.getAttribute('data-bs-toggle');
                            link.removeAttribute('data-bs-toggle');
                        }
                    });
                }

                // Status collapsed dibaca dari <html> yang sudah diset lebih
                // dulu di FOUC-prevention script (paling atas <body>) - kalau
                // memang mulai dalam kondisi collapsed, non-aktifkan accordion
                // dari awal juga (bukan cuma setelah toggle manual pertama).
                if (document.documentElement.classList.contains('sidebar-collapsed')) {
                    setAccordionInteractive(false);
                }

                // Satu fungsi bersama untuk perubahan PERMANEN (disimpan ke
                // localStorage) - dipakai baik oleh tombol chevron maupun
                // klik logo, supaya logic-nya (class, localStorage,
                // aria-label, accordion) tidak dobel ditulis di 2 tempat.
                function setSidebarCollapsed(collapsed) {
                    peekExpanded = false;
                    document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
                    localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
                    sidebarToggleBtn.setAttribute('aria-label', collapsed ? 'Buka sidebar' : 'Tutup sidebar');
                    setAccordionInteractive(!collapsed);
                }

                function collapseBackFromPeek() {
                    if (!peekExpanded) {
                        return;
                    }
                    peekExpanded = false;
                    document.documentElement.classList.add('sidebar-collapsed');
                    setAccordionInteractive(false);
                }

                // 1a. Tombol chevron - selalu menang atas peek, disimpan permanen.
                sidebarToggleBtn.addEventListener('click', function () {
                    setSidebarCollapsed(!document.documentElement.classList.contains('sidebar-collapsed'));
                });

                // 1b. Klik logo SAAT collapsed & desktop -> expand permanen
                //     (BUKAN peek - ini menggantikan tombol chevron yang
                //     sengaja disembunyikan di kondisi ini). Saat sudah
                //     expanded, klik logo dibiarkan jalan normal (navigasi
                //     ke Dashboard via href aslinya, tidak di-preventDefault).
                if (sidebarBrandLink) {
                    sidebarBrandLink.addEventListener('click', function (e) {
                        var isCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
                        if (!isCollapsed || window.innerWidth < 992) {
                            return; // sudah expanded, atau mobile/tablet - biarkan navigasi ke Dashboard normal
                        }
                        e.preventDefault();
                        setSidebarCollapsed(false);
                    });
                }

                // 2. Peek - klik ikon dropdown SAAT collapsed & desktop, buka
                //    sementara + langsung tampilkan submenu yang diklik (biar
                //    tidak perlu klik 2x: sekali buka rail, sekali lagi buka submenu).
                accordionToggles.forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        var isCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
                        if (!isCollapsed || window.innerWidth < 992) {
                            return; // sudah expand (permanen/peek), atau di mobile/tablet - biarkan Bootstrap jalan normal
                        }
                        e.preventDefault();
                        peekExpanded = true;
                        document.documentElement.classList.remove('sidebar-collapsed');
                        setAccordionInteractive(true);

                        var submenu = document.querySelector(link.getAttribute('href'));
                        if (submenu) {
                            bootstrap.Collapse.getOrCreateInstance(submenu, { toggle: false }).show();
                        }
                    });
                });

                // 3. Tutup lagi otomatis kalau sedang peek: klik di luar sidebar...
                document.addEventListener('click', function (e) {
                    if (peekExpanded && !sidebarEl.contains(e.target)) {
                        collapseBackFromPeek();
                    }
                });

                // ...atau kursor keluar dari area sidebar (hover keluar).
                sidebarEl.addEventListener('mouseleave', function () {
                    collapseBackFromPeek();
                });
            }
        });
    </script>

    {{-- Registrasi Service Worker untuk Web Push - PASIF, tidak meminta izin
         apa pun ke user (browser mengizinkan register tanpa gesture user).
         Permintaan izin notifikasi yang sungguhan (Notification.
         requestPermission(), butuh klik user) ada di halaman Notifikasi
         (resources/views/notification/index.blade.php), bukan di sini -
         supaya tidak muncul popup izin browser tiba-tiba di halaman
         manapun tanpa user memintanya. --}}
    @auth
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(function () {
                    // Diamkan saja kalau gagal (misal browser lama yang tidak
                    // support, atau diakses lewat http:// bukan https://) -
                    // seluruh aplikasi tetap harus jalan normal tanpa fitur ini.
                });
            }
        </script>
    @endauth

    @stack('scripts')
</body>
</html>
