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
