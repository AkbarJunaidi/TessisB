<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Management Information System</title>

    {{ \App\Support\VendorAsset::style('bootstrap-css') }}
    {{ \App\Support\VendorAsset::style('bootstrap-icons') }}
    {{-- Design system terpusat: token warna, radius, shadow, komponen reusable --}}
    <link href="{{ \App\Support\AppAsset::url('css/theme.css') }}" rel="stylesheet">

    @stack('styles')
    <link href="{{ \App\Support\AppAsset::url('css/layout.css') }}" rel="stylesheet">
    <link href="{{ \App\Support\AppAsset::url('css/modules.css') }}" rel="stylesheet">
</head>
<body>

    <a class="skip-link" href="#mainContent">Lewati ke konten</a>

    {{-- Terapkan status collapse sidebar sebelum halaman digambar (cegah kedipan); hanya berefek di >=992px,
         HP/tablet memakai offcanvas normal. --}}
    <script>
        if (localStorage.getItem('sidebarCollapsed') === '1') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>

    {{-- App shell: sidebar statis di desktop (>=992px), offcanvas Bootstrap di layar sempit. --}}
    <div class="app-shell">

        {{-- SIDEBAR --}}
        <div class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
            @include('layouts.sidebar')
        </div>

        {{-- KONTEN UTAMA --}}
        <div class="app-main">
            @include('layouts.navbar')

            <main class="app-content" id="mainContent" tabindex="-1">
                @yield('content')
            </main>
        </div>
    </div>

    @auth
        @include('layouts.bottom-nav')
    @endauth

    {{-- Toast global (auto-hilang); jangan tambah alert session('success'/'error') lokal di view, andalkan ini. --}}
    <div class="toast-container position-fixed top-0 end-0 p-3 u-z-1100">
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

    {{ \App\Support\VendorAsset::script('bootstrap-js') }}
    <script src="{{ \App\Support\AppAsset::url('js/layouts/app-1.js') }}"></script>

    {{-- Registrasi Service Worker Web Push (pasif, tanpa meminta izin); permintaan izin ada di halaman Notifikasi. --}}
    @auth
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(function () {
                    // Abaikan bila gagal (browser lama atau bukan https); aplikasi tetap jalan tanpa fitur ini.
                });
            }
        </script>
    @endauth

    {{-- Modal konfirmasi global: pasang data-confirm="Teks" di <form>.
         Opsional: data-confirm-danger, data-confirm-label="Hapus". --}}
    <div class="modal fade" id="appConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body pt-4">
                    <p class="mb-0 fw-semibold" id="appConfirmText"></p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="appConfirmOk">Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ \App\Support\AppAsset::url('js/layouts/app-2.js') }}"></script>
    <script src="{{ \App\Support\AppAsset::url('js/layouts/page-feedback.js') }}"></script>

    @stack('scripts')
</body>
</html>
