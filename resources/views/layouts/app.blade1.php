<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Management Information System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    {{-- Design system terpusat: token warna, radius, shadow, komponen reusable --}}
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body>

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

    {{-- Layout shell styles: dipusatkan di sini karena spesifik struktural,
         bukan token desain umum (yang sudah ada di theme.css).

         MEKANISME (bukan cuma tampilan) diadopsi dari sistem lama yang
         terbukti tidak pernah bug: shell dibuat TINGGI TETAP (dikunci ke
         tinggi layar, bukan minimal), dan yang di-scroll adalah AREA KONTEN
         di dalamnya (overflow-y: auto), BUKAN seluruh halaman/body. Karena
         shell-nya dikunci ke tinggi layar, sidebar (yang ikut diregangkan
         flexbox mengikuti tinggi shell) otomatis TIDAK PERNAH lebih tinggi
         dari layar, apapun panjang halaman yang sedang dibuka - tidak perlu
         lagi trik position:sticky atau align-items:flex-start. --}}
    <style>
        .app-shell {
            display: flex;
            height: 100vh;
            height: 100dvh; /* ikut tinggi viewport yang sebenarnya di HP (bukan nilai awal saat address bar masih terlihat) */
            width: 100%;
            overflow: hidden; /* shell sendiri TIDAK PERNAH scroll - yang scroll adalah .app-main di dalamnya */
        }

        .app-sidebar {
            width: 272px;
            background: linear-gradient(180deg, var(--c-navy) 0%, var(--c-navy-2) 100%);
            border-right: 1px solid rgba(255,255,255,.06);
        }
        /* Desktop (>=992px): offcanvas-lg otomatis jadi kolom statis oleh
           Bootstrap. Karena .app-shell sudah tinggi tetap (bukan minimal),
           default flexbox (align-items: stretch) membuat sidebar otomatis
           persis setinggi layar - selalu, tanpa perlu diatur manual lagi. */

        .app-main {
            flex: 1;
            min-width: 0; /* cegah overflow horizontal di flex child */
            display: flex;
            flex-direction: column;
            overflow-y: auto; /* KUNCI: konten panjang di-scroll DI SINI, bukan di seluruh halaman */
        }

        .app-content {
            flex: 1;
            padding: 1.5rem;
        }
        @media (min-width: 768px) {
            .app-content { padding: 2rem 2rem 2.5rem; }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Tampilkan toast notifikasi global (kalau ada) - auto-hilang sendiri
            document.querySelectorAll('.toast-container .toast').forEach(function (toastEl) {
                new bootstrap.Toast(toastEl).show();
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
