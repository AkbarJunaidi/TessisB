<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Management Information System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Font identitas CV. Arindra Production: Space Grotesk (heading/angka),
         Inter (body/tabel/form) - lihat public/css/theme.css untuk token warna --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    {{-- Tema terpusat - SATU-SATUNYA sumber warna/font/radius untuk seluruh app.
         Dimuat setelah Bootstrap supaya bisa menimpanya. Jangan tambah warna/
         font hardcode baru di file Blade lain, tambahkan di theme.css. --}}
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">

    <style>
        body {
            /* font-family & background-color kini diatur oleh theme.css
               (--bs-body-font-family / --bs-body-bg) - jangan didefinisikan
               ulang di sini supaya tidak ada 2 sumber kebenaran yang beda. */
            overflow-x: hidden;
        }
        #wrapper {
            display: flex;
            width: 100vw;
            height: 100vh;
        }
        #sidebar-wrapper {
            min-width: 260px;
            max-width: 260px;
            /* Warna sidebar diatur oleh class ap-sidebar di sidebar.blade.php.
               Perilaku drawer responsif (tablet/mobile) diatur terpusat di
               public/css/theme.css bagian "SIDEBAR RESPONSIF" - jangan
               menambah media query sidebar baru di sini. */
        }
        #page-content-wrapper {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .main-content {
            padding: 1.5rem;
            flex: 1;
        }
    </style>
</head>
<body>

    <div id="wrapper">
        <div id="sidebar-wrapper">
            @include('layouts.sidebar')
        </div>

        {{-- Overlay gelap di belakang drawer sidebar - hanya aktif di tablet/mobile
             (lihat theme.css). Tap di sini menutup sidebar. --}}
        <div id="sidebarOverlay"></div>

        <div id="page-content-wrapper">
            @include('layouts.navbar')

            <main class="main-content">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            // ===== Drawer Sidebar (tablet & mobile) =====
            // State tunggal: class "ap-sidebar-open" di <body>. Semua bagian
            // (transform sidebar, overlay, ikon hamburger) mengikuti 1 class
            // ini lewat CSS - supaya tidak ada state tersebar di banyak tempat.
            const body = document.body;
            const toggleBtn = document.getElementById("menu-toggle");
            const closeBtn = document.getElementById("sidebarCloseBtn");
            const overlay = document.getElementById("sidebarOverlay");
            const sidebar = document.getElementById("sidebar-wrapper");

            function openSidebar() {
                body.classList.add("ap-sidebar-open");
            }

            function closeSidebar() {
                body.classList.remove("ap-sidebar-open");
            }

            if (toggleBtn) {
                toggleBtn.addEventListener("click", function (e) {
                    e.preventDefault();
                    if (body.classList.contains("ap-sidebar-open")) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener("click", closeSidebar);
            }

            if (overlay) {
                overlay.addEventListener("click", closeSidebar);
            }

            // Menutup drawer otomatis begitu user benar-benar berpindah halaman
            // lewat link di sidebar - TAPI bukan untuk link pemicu submenu
            // (data-bs-toggle="collapse"), supaya membuka submenu tidak ikut
            // menutup drawer-nya.
            if (sidebar) {
                sidebar.querySelectorAll('a[href]:not([data-bs-toggle="collapse"])').forEach(function (link) {
                    link.addEventListener("click", closeSidebar);
                });
            }

            // Kalau layar di-resize melewati breakpoint desktop (lg) saat
            // drawer sedang terbuka, bersihkan state-nya supaya tidak ada
            // overlay/transform yang "nyangkut" saat kembali ke mobile nanti.
            const desktopQuery = window.matchMedia("(min-width: 992px)");
            function handleBreakpointChange(e) {
                if (e.matches) {
                    closeSidebar();
                }
            }
            desktopQuery.addEventListener("change", handleBreakpointChange);

            // Tampilkan toast notifikasi global (kalau ada) - auto-hilang sendiri
            document.querySelectorAll('.toast-container .toast').forEach(function (toastEl) {
                const toast = new bootstrap.Toast(toastEl);
                toast.show();
            });
        });
    </script>
</body>
</html>
