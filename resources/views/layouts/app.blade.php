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
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">

    @stack('styles')
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
        @media (max-width: 991.98px) {
            .app-content { padding-bottom: calc(5.5rem + env(safe-area-inset-bottom)); }
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

    {{ \App\Support\VendorAsset::script('bootstrap-js') }}
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Tampilkan toast notifikasi global (kalau ada) - auto-hilang sendiri
            document.querySelectorAll('.toast-container .toast').forEach(function (toastEl) {
                new bootstrap.Toast(toastEl).show();
            });

            // Sidebar collapse (ikon saja, hanya desktop): permanen lewat localStorage (chevron/klik logo),
            // atau peek sementara saat klik ikon dropdown; menutup lagi saat klik di luar sidebar.
            var sidebarEl = document.querySelector('.app-sidebar');
            var sidebarToggleBtn = document.getElementById('sidebarCollapseToggle');
            var sidebarBrandLink = document.getElementById('sidebarBrandLink');

            if (sidebarEl && sidebarToggleBtn) {
                var accordionToggles = document.querySelectorAll('#sidebarMenuAccordion [data-bs-toggle="collapse"]');
                var peekExpanded = false; // true = sidebar kebuka gara-gara peek, bukan preferensi permanen

                // Saat rail collapsed submenu tak punya ruang: data-bs-toggle dilepas dan dipasang lagi saat interaktif.
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

                // Status awal dibaca dari <html> (diset skrip FOUC di atas <body>), jadi accordion nonaktif sejak awal.
                if (document.documentElement.classList.contains('sidebar-collapsed')) {
                    setAccordionInteractive(false);
                }

                // Satu fungsi untuk perubahan permanen (chevron dan klik logo): class, localStorage, aria-label, accordion.
                function setSidebarCollapsed(collapsed) {
                    peekExpanded = false;
                    document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
                    localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
                    sidebarToggleBtn.setAttribute('aria-label', collapsed ? 'Buka sidebar' : 'Tutup sidebar');
                    setAccordionInteractive(!collapsed);
                }

                // Tutup peek: kembalikan sidebar ke mode collapsed.
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

                // 1b. Klik logo saat collapsed di desktop: expand permanen; saat expanded biarkan link ke Dashboard jalan.
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

                // 2. Peek: klik ikon dropdown saat collapsed membuka rail sementara dan langsung menampilkan submenunya.
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

    <script>
        // Helper UI global: konfirmasi form, toast, kunci tombol submit, aria-label tombol tutup.
        (function () {
            const modalEl = document.getElementById('appConfirmModal');
            const okBtn = document.getElementById('appConfirmOk');
            const textEl = document.getElementById('appConfirmText');
            let pending = null;

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!form.hasAttribute || !form.hasAttribute('data-confirm') || form.dataset.confirmed === '1') return;

                e.preventDefault();
                e.stopImmediatePropagation();
                pending = { form: form, submitter: e.submitter || null };

                textEl.textContent = form.getAttribute('data-confirm');
                okBtn.textContent = form.getAttribute('data-confirm-label') || 'Lanjutkan';
                okBtn.className = 'btn ' + (form.hasAttribute('data-confirm-danger') ? 'btn-danger' : 'btn-primary');
                modal.show();
            }, true);

            okBtn.addEventListener('click', function () {
                if (!pending) return;
                const job = pending;
                pending = null;
                modal.hide();
                if (job.resolve) { job.resolve(true); return; }
                job.form.dataset.confirmed = '1';
                try { job.form.requestSubmit(job.submitter || undefined); } finally { delete job.form.dataset.confirmed; }
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                if (pending && pending.resolve) pending.resolve(false);
                pending = null;
            });

            // Kunci tombol submit setelah form dikirim; buka lagi saat kembali ke halaman.
            const LOCK_MS = 8000;
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (e.defaultPrevented || !form.querySelectorAll) return;
                if (form.target && form.target !== '_self') return;

                setTimeout(function () {
                    const buttons = form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]');
                    buttons.forEach(function (b) { b.disabled = true; });
                    setTimeout(function () { buttons.forEach(function (b) { b.disabled = false; }); }, LOCK_MS);
                }, 0);
            });
            window.addEventListener('pageshow', function (e) {
                if (!e.persisted) return;
                document.querySelectorAll('form').forEach(function (f) {
                    f.querySelectorAll('button:disabled').forEach(function (b) { b.disabled = false; });
                });
            });

            // Toast dinamis: AppUI.toast('Pesan', 'danger' | 'success' | 'primary')
            window.AppUI = {
                // AppUI.confirm('Teks', { label: 'Hapus', danger: true }).then(ok => ...)
                confirm: function (message, opts) {
                    opts = opts || {};
                    return new Promise(function (resolve) {
                        pending = { resolve: resolve };
                        textEl.textContent = message;
                        okBtn.textContent = opts.label || 'Lanjutkan';
                        okBtn.className = 'btn ' + (opts.danger ? 'btn-danger' : 'btn-primary');
                        modal.show();
                    });
                },
                toast: function (message, tone) {
                    const holder = document.querySelector('.toast-container');
                    if (!holder) return;
                    const el = document.createElement('div');
                    el.className = 'toast align-items-center border-0 shadow text-bg-' + (tone || 'danger');
                    el.setAttribute('role', 'alert');
                    el.innerHTML = '<div class="d-flex"><div class="toast-body"></div>' +
                        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button></div>';
                    el.querySelector('.toast-body').textContent = message;
                    holder.appendChild(el);
                    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
                    bootstrap.Toast.getOrCreateInstance(el, { delay: 5000 }).show();
                }
            };

            // Beri aria-label "Tutup" pada tombol tutup yang belum punya.
            function labelCloseButtons() {
                document.querySelectorAll('.btn-close:not([aria-label])').forEach(function (b) { b.setAttribute('aria-label', 'Tutup'); });
            }
            labelCloseButtons();
            document.addEventListener('show.bs.modal', labelCloseButtons);
            document.addEventListener('show.bs.offcanvas', labelCloseButtons);
        })();
    </script>

    @stack('scripts')
</body>
</html>
