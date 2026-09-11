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

    {{-- Layout shell styles: dipusatkan di sini karena spesifik struktural,
         bukan token desain umum (yang sudah ada di theme.css).

         MEKANISME - 2 aturan yang saling melengkapi, BUKAN cuma "kunci
         tinggi shell" seperti percobaan sebelumnya (itu ternyata rapuh -
         kalau isi sidebar kebetulan lebih panjang dari 1 layar, konten
         yang kelebihan itu genuinely tidak terjangkau sama sekali, bukan
         cuma terpotong tampilan):

         1. .app-shell pakai min-height (LANTAI minimal 1 layar, boleh
            tumbuh lebih tinggi kalau perlu) - bukan height (kaku/mentok).
         2. .app-main (area konten utama) pakai max-height (ATAP maksimal
            1 layar) + overflow-y:auto - jadi HALAMAN PANJANG tidak pernah
            menyeret shell/sidebar ikut tumbuh, karena kelebihannya
            di-scroll sendiri di dalam .app-main.

         Karena sidebar TIDAK diberi pembatas apa pun (tidak max-height,
         tidak overflow sendiri), kalau suatu saat isinya (menu + dropdown
         + Activity Logs + Trash + kartu profil) kebetulan lebih panjang
         dari 1 layar, dia bebas mendorong .app-shell (yang cuma punya
         lantai minimal, bukan langit-langit) tumbuh lebih tinggi - dan
         HALAMAN yang scroll sedikit menampakkannya (fallback alami
         browser), BUKAN sidebar dikasih scrollbar sendiri. Pembagian
         tugas "shell=lantai, main=atap" inilah yang membuat 2 bug (halaman
         panjang menyeret sidebar vs sidebar panjang kepotong) tidak bisa
         terjadi bersamaan - keduanya diselesaikan oleh 2 aturan berbeda,
         bukan 1 aturan yang dipaksa menyelesaikan keduanya sekaligus. --}}
    <style>
        .app-shell {
            display: flex;
            min-height: 100vh;
            min-height: 100dvh; /* ikut tinggi viewport yang sebenarnya di HP */
            width: 100%;
            /* PENTING: min-height, BUKAN height. Beda dari percobaan
               sebelumnya - shell ini punya LANTAI minimal setinggi layar,
               tapi BOLEH tumbuh lebih tinggi kalau memang dibutuhkan (lihat
               .app-main di bawah untuk kenapa ini aman dari 2 bug
               sekaligus). */
        }

        .app-sidebar {
            width: 272px;
            background: linear-gradient(180deg, var(--c-navy) 0%, var(--c-navy-2) 100%);
            border-right: 1px solid rgba(255,255,255,.06);
            transition: width .2s ease;
        }
        /* Mode collapsed (icon-only) - HANYA desktop (>=992px), diaktifkan
           lewat class "sidebar-collapsed" di <html> (tombol toggle ada di
           bagian atas sidebar, lihat layouts/sidebar.blade.php). Mobile/
           tablet (<992px, offcanvas) tidak pernah kena aturan ini - di sana
           sudah ada cara tutup sendiri (backdrop + tombol X). Ikon brand
           (.sidebar-brand-icon di theme.css) SENGAJA satu ukuran konstan di
           kedua state (tidak tergantung 84px di sini) - supaya logonya
           tidak kelihatan "membesar-mengecil" tiap kali toggle. */
        @media (min-width: 992px) {
            html.sidebar-collapsed .app-sidebar { width: 84px; }
        }
        /* Desktop (>=992px): offcanvas-lg otomatis jadi kolom statis oleh
           Bootstrap, lalu default flexbox (align-items: stretch) membuat
           sidebar ikut tinggi baris. Sengaja TIDAK diberi max-height atau
           overflow-nya sendiri - kalau isinya (menu + 1 dropdown + Activity
           Logs + Trash + kartu profil) kebetulan lebih panjang dari 1
           layar, sidebar boleh mendorong .app-shell tumbuh lebih tinggi
           dari 100vh, dan HALAMAN (bukan sidebar) yang scroll sedikit
           untuk menampakkannya - graceful fallback alami, bukan scrollbar
           terpisah di sidebar yang terlihat aneh. Ini aman dipakai karena
           dropdown sudah dikelompokkan 1 accordion (data-bs-parent), jadi
           kasus ini jarang kepakai. */

        .app-main {
            flex: 1;
            min-width: 0; /* cegah overflow horizontal di flex child */
            display: flex;
            flex-direction: column;
            max-height: 100vh;
            max-height: 100dvh; /* KUNCI dari bug ini: dibatasi maksimal, BUKAN dikunci pas (height). Kalau halaman
                                    panjang, area ini scroll SENDIRI dan tidak pernah menyeret .app-shell/sidebar
                                    ikut tumbuh - beda dari sidebar di atas yang justru BOLEH menyeret shell tumbuh
                                    kalau perlu. Pembagian tugas inilah yang membuat 2 bug (halaman panjang menyeret
                                    sidebar, DAN sidebar panjang kepotong) tidak bisa terjadi bersamaan. */
            overflow-y: auto;
        }

        .app-content {
            flex: 1;
            padding: 1.5rem;
        }
        @media (min-width: 768px) {
            .app-content { padding: 2rem 2rem 2.5rem; }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Tampilkan toast notifikasi global (kalau ada) - auto-hilang sendiri
            document.querySelectorAll('.toast-container .toast').forEach(function (toastEl) {
                new bootstrap.Toast(toastEl).show();
            });

            // Toggle sidebar collapsed (icon-only) - HANYA berefek di
            // desktop >=992px lewat CSS (lihat .app-sidebar &
            // html.sidebar-collapsed di atas + di sidebar.blade.php).
            // Tombolnya sendiri juga cuma dirender di layar itu
            // (d-none d-lg-flex), jadi listener ini aman dipasang selalu.
            var sidebarToggleBtn = document.getElementById('sidebarCollapseToggle');
            if (sidebarToggleBtn) {
                var accordionToggles = document.querySelectorAll('#sidebarMenuAccordion [data-bs-toggle="collapse"]');

                sidebarToggleBtn.addEventListener('click', function () {
                    var collapsed = !document.documentElement.classList.contains('sidebar-collapsed');
                    document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
                    localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
                    sidebarToggleBtn.setAttribute('aria-label', collapsed ? 'Buka sidebar' : 'Tutup sidebar');

                    // Submenu (Inventory/Progress Management/dst) sengaja
                    // tidak bisa di-expand saat rail sedang collapsed (tidak
                    // ada tempat menampilkan teksnya, sudah disembunyikan
                    // CSS juga) - data-bs-toggle Bootstrap dilepas sementara
                    // supaya klik ikon parent tidak diam-diam nge-toggle
                    // submenu yang toh tersembunyi. Dikembalikan lagi saat
                    // sidebar di-expand.
                    accordionToggles.forEach(function (link) {
                        if (collapsed) {
                            link.dataset.bsToggleBackup = link.getAttribute('data-bs-toggle');
                            link.removeAttribute('data-bs-toggle');
                        } else if (link.dataset.bsToggleBackup) {
                            link.setAttribute('data-bs-toggle', link.dataset.bsToggleBackup);
                            delete link.dataset.bsToggleBackup;
                        }
                    });
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
