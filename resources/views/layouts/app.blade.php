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

    {{-- Satu-satunya notifikasi hasil aksi (auto-hilang). Jangan tambah alert session('success'/'error') lokal di view;
         dari JS pakai AppUI.toast(). Gaya: .app-toast di theme.css. --}}
    <div class="toast-container position-fixed top-0 end-0 p-3 u-z-1100">
        @if(session('success'))
            <div class="toast app-toast app-toast--success" role="alert" data-bs-autohide="true" data-bs-delay="4000" id="globalToastSuccess">
                <div class="d-flex align-items-start">
                    <i class="bi bi-check-circle-fill app-toast-icon"></i>
                    <div class="toast-body">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Tutup"></button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="toast app-toast app-toast--danger" role="alert" data-bs-autohide="true" data-bs-delay="6000" id="globalToastError">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill app-toast-icon"></i>
                    <div class="toast-body">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Tutup"></button>
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

    {{-- Popup konfirmasi global. Semua form non-GET otomatis minta konfirmasi (hapus = merah);
         lewati dengan data-no-confirm. Opsional: data-confirm="Teks", data-confirm-title, data-confirm-label,
         data-confirm-danger, data-track-changes (tampilkan daftar perubahan, lihat form-changes.js). --}}
    <div class="modal fade" id="appConfirmModal" tabindex="-1" aria-labelledby="appConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appConfirmTitle">
                        <i class="bi bi-question-circle-fill text-primary" id="appConfirmIcon"></i>
                        <span id="appConfirmTitleText">Konfirmasi</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="appConfirmText"></p>
                    <div class="app-changes d-none" id="appConfirmChanges"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="appConfirmOk">Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ \App\Support\AppAsset::url('js/layouts/form-changes.js') }}"></script>
    <script src="{{ \App\Support\AppAsset::url('js/layouts/app-2.js') }}"></script>
    <script src="{{ \App\Support\AppAsset::url('js/layouts/page-feedback.js') }}"></script>
    <script src="{{ \App\Support\AppAsset::url('js/layouts/ajax-forms.js') }}"></script>
    <script src="{{ \App\Support\AppAsset::url('js/layouts/pager.js') }}"></script>
    <script src="{{ \App\Support\AppAsset::url('js/layouts/ajax-tabs.js') }}"></script>

    @stack('scripts')
</body>
</html>
