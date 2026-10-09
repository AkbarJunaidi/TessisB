{{-- Header offcanvas ini HANYA tampil di layar <992px (mobile/tablet).
     Di desktop, offcanvas-lg otomatis jadi sidebar statis tanpa header ini. --}}
<div class="offcanvas-header d-lg-none px-3 pt-3 pb-2">
    <a href="{{ route('dashboard') }}" class="sidebar-logo-wrap text-decoration-none" id="appSidebarLabel">
        <img src="{{ asset('image/logo1.png') }}" alt="Logo {{ \App\Models\AppSetting::get('company_name') }}" class="sidebar-logo-mobile">
        <span class="sidebar-brand-text">{{ \App\Models\AppSetting::get('company_name') }}</span>
    </a>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Tutup menu"></button>
</div>

<div class="offcanvas-body d-flex flex-column p-3 pt-lg-0 text-white h-100 sidebar-body">

    {{-- Brand + tombol collapse (hanya desktop; logikanya di layouts/app.blade.php). Tinggi 64px .sidebar-brand-row = tinggi navbar agar garisnya sejajar,
         karena itu offcanvas-body memakai pt-lg-0 supaya brand menempel di y=0. --}}
    <div class="d-none d-lg-flex px-1 sidebar-brand-row">
        <a href="{{ route('dashboard') }}" class="sidebar-brand-link" id="sidebarBrandLink">
            <span class="sidebar-brand-icon-wrap">
                <img src="{{ asset('image/logo1.png') }}" alt="Logo {{ \App\Models\AppSetting::get('company_name') }}" class="sidebar-brand-icon">
                <i class="bi bi-chevron-right sidebar-brand-expand-icon" aria-hidden="true"></i>
            </span>
            <span class="sidebar-brand-text sidebar-link-text">{{ \App\Models\AppSetting::get('company_name') }}</span>
        </a>
        <button type="button" class="sidebar-collapse-toggle" id="sidebarCollapseToggle" aria-label="Tutup sidebar" title="Tutup sidebar">
            <i class="bi bi-chevron-left"></i>
        </button>
    </div>

    {{-- Garis pembatas hanya untuk mobile; di desktop border-bottom .sidebar-brand-row sudah menjadi garisnya. --}}
    <hr class="border-white opacity-10 sidebar-divider d-lg-none">

    <ul class="nav nav-pills flex-column mb-auto gap-1 sidebar-nav" id="sidebarMenuAccordion" role="menu" aria-label="Menu utama">

        <li class="nav-item">
            <a href="{{ route('dashboard') }}"
                class="nav-link sidebar-link text-white {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> <span class="sidebar-link-text">Dashboard</span>
            </a>
        </li>

        {{-- Notifikasi: semua role; isi halaman menyesuaikan role (notification/index.blade.php). --}}
        <li class="nav-item">
            <a href="{{ route('announcements.index') }}"
                class="nav-link sidebar-link text-white {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> <span class="sidebar-link-text">Notifikasi</span>
            </a>
        </li>

        {{-- Approval - kotak masuk approval generik lintas modul, pakai
             permission 'approval.view' (default hanya Super Admin). --}}
        @if(auth()->user()->hasPermission('approval', 'view'))
            <li class="nav-item">
                <a href="{{ route('approval.index') }}"
                    class="nav-link sidebar-link text-white {{ request()->routeIs('approval.*') ? 'active' : '' }}">
                    <i class="bi bi-check2-square"></i> <span class="sidebar-link-text">Approval</span>
                </a>
            </li>
        @endif

        <li class="sidebar-group-label sidebar-link-text" role="presentation">Operasional</li>

        @if(auth()->user()->hasPermission('scan_barang', 'view'))
        <li class="nav-item">
            <a href="{{ route('scan.index') }}"
                class="nav-link sidebar-link text-white {{ request()->routeIs('scan.index') ? 'active' : '' }}">
                <i class="bi bi-upc-scan"></i> <span class="sidebar-link-text">Scan Barang</span>
            </a>
        </li>
        @endif

        @if(auth()->user()->hasRole('super_admin', 'admin'))

            @php $invActive = request()->routeIs('inventory.*') && !request()->routeIs('inventory.locations.*'); @endphp
            <li class="nav-item">
                <a href="#menuInventory" data-bs-toggle="collapse" class="nav-link sidebar-link text-white d-flex align-items-center justify-content-between {{ $invActive ? 'active' : '' }}" aria-expanded="{{ $invActive ? 'true' : 'false' }}">
                    <span><i class="bi bi-box-seam"></i> <span class="sidebar-link-text">Inventory</span></span>
                    <i class="bi bi-chevron-down sidebar-collapse-icon"></i>
                </a>
                <div class="collapse {{ $invActive ? 'show' : '' }}" id="menuInventory" data-bs-parent="#sidebarMenuAccordion">
                    <ul class="nav flex-column sidebar-submenu">
                        <li class="nav-item">
                            <a href="{{ route('inventory.index') }}" class="nav-link sidebar-sublink {{ request()->routeIs('inventory.index') || request()->routeIs('inventory.show') || request()->routeIs('inventory.edit') ? 'active' : '' }}">
                                <i class="bi bi-list-ul"></i> Inventory List
                            </a>
                        </li>
                        <li class="nav-item">
                            @if(auth()->user()->hasPermission('inventory', 'create'))
                            <a href="{{ route('inventory.create') }}" class="nav-link sidebar-sublink {{ request()->routeIs('inventory.create') ? 'active' : '' }}">
                                <i class="bi bi-plus-circle"></i> Add Inventory
                            </a>
                            @endif
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('inventory.mutasi') }}" class="nav-link sidebar-sublink {{ request()->routeIs('inventory.mutasi') ? 'active' : '' }}">
                                <i class="bi bi-arrow-left-right"></i> Mutasi Aset
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('inventory.repairs.index') }}" class="nav-link sidebar-sublink {{ request()->routeIs('inventory.repairs.*') ? 'active' : '' }}">
                                <i class="bi bi-tools"></i> Perbaikan Barang
                            </a>
                        </li>
                        @if(auth()->user()->hasPermission('purchase', 'view'))
                        <li class="nav-item">
                            <a href="{{ route('inventory.prices.index') }}" class="nav-link sidebar-sublink {{ request()->routeIs('inventory.prices.*') ? 'active' : '' }}">
                                <i class="bi bi-currency-dollar"></i> Daftar Harga Barang
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>
        @endif

        {{-- Progress Management (Projects & Task) --}}
        @php
            $trackActive = request()->routeIs('projects.*') || request()->routeIs('tasks.*') || request()->routeIs('borrowed-items.*');
        @endphp
        <li class="nav-item">
            <a href="#menuTracking" data-bs-toggle="collapse" class="nav-link sidebar-link text-white d-flex align-items-center justify-content-between {{ $trackActive ? 'active' : '' }}" aria-expanded="{{ $trackActive ? 'true' : 'false' }}">
                <span><i class="bi bi-kanban"></i> <span class="sidebar-link-text">Progress Management</span></span>
                <i class="bi bi-chevron-down sidebar-collapse-icon"></i>
            </a>
            <div class="collapse {{ $trackActive ? 'show' : '' }}" id="menuTracking" data-bs-parent="#sidebarMenuAccordion">
                <ul class="nav flex-column sidebar-submenu">
                    <li class="nav-item">
                        <a href="{{ route('projects.index') }}" class="nav-link sidebar-sublink {{ request()->routeIs('projects.index') || request()->routeIs('projects.show') || request()->routeIs('tasks.show') ? 'active' : '' }}">
                            <i class="bi bi-kanban"></i> Projects
                        </a>
                    </li>
                    @if(auth()->user()->hasRole('super_admin', 'admin'))
                    <li class="nav-item">
                        <a href="{{ route('projects.pipeline') }}" class="nav-link sidebar-sublink {{ request()->routeIs('projects.pipeline') ? 'active' : '' }}">
                            <i class="bi bi-diagram-3"></i> Pipeline
                        </a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a href="{{ route('borrowed-items.index') }}" class="nav-link sidebar-sublink {{ request()->routeIs('borrowed-items.*') ? 'active' : '' }}">
                            <i class="bi bi-box-arrow-in-left"></i> Barang Pinjaman
                        </a>
                    </li>
                    <li class="nav-item">
                        @if(auth()->user()->hasPermission('tracking_progress', 'create_project'))
                        <a href="{{ route('projects.create') }}" class="nav-link sidebar-sublink {{ request()->routeIs('projects.create') ? 'active' : '' }}">
                            <i class="bi bi-folder-plus"></i> Add Project
                        </a>
                        @endif
                    </li>
                </ul>
            </div>
        </li>

        @if(auth()->user()->hasPermission('purchase', 'view'))
        <li class="nav-item">
            <a href="{{ route('purchases.index') }}"
                class="nav-link sidebar-link text-white {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                <i class="bi bi-bag-check"></i> <span class="sidebar-link-text">Pembelian</span>
            </a>
        </li>
        @endif

        {{-- Kontak: permission 'kontak.view' (config/permissions.php). --}}
        @if(auth()->user()->hasPermission('kontak', 'view'))
        <li class="nav-item">
            <a href="{{ route('contacts.index') }}"
                class="nav-link sidebar-link text-white {{ request()->routeIs('contacts.*') ? 'active' : '' }}">
                <i class="bi bi-person-vcard"></i> <span class="sidebar-link-text">Kontak</span>
            </a>
        </li>
        @endif

        <li class="sidebar-group-label sidebar-link-text" role="presentation">Data</li>

        {{-- Keuangan: permission 'finance.view', sama dengan gate Data Keuangan di tab Project. --}}
        @if(auth()->user()->hasPermission('finance', 'view'))
        <li class="nav-item">
            <a href="{{ route('finance.summary') }}"
                class="nav-link sidebar-link text-white {{ request()->routeIs('kwitansi.*', 'finance.*') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i> <span class="sidebar-link-text">Keuangan</span>
            </a>
        </li>
        @endif

        {{-- Integrasi Data --}}
        @php $intActive = request()->routeIs('folders.*') || request()->routeIs('files.*'); @endphp
        <li class="nav-item">
            <a href="#menuIntegrasi" data-bs-toggle="collapse" class="nav-link sidebar-link text-white d-flex align-items-center justify-content-between {{ $intActive ? 'active' : '' }}" aria-expanded="{{ $intActive ? 'true' : 'false' }}">
                <span><i class="bi bi-database"></i> <span class="sidebar-link-text">Integrasi Data</span></span>
                <i class="bi bi-chevron-down sidebar-collapse-icon"></i>
            </a>
            <div class="collapse {{ $intActive ? 'show' : '' }}" id="menuIntegrasi" data-bs-parent="#sidebarMenuAccordion">
                <ul class="nav flex-column sidebar-submenu">
                    <li class="nav-item">
                        <a href="{{ route('folders.index') }}" class="nav-link sidebar-sublink {{ request()->routeIs('folders.index') || request()->routeIs('folders.show') ? 'active' : '' }}">
                            <i class="bi bi-folder2-open"></i> Folder Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('files.my-files') }}" class="nav-link sidebar-sublink {{ request()->routeIs('files.my-files') ? 'active' : '' }}">
                            <i class="bi bi-file-earmark-arrow-up"></i> My Files
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="sidebar-group-label sidebar-link-text" role="presentation">Administrasi</li>

        @if(auth()->user()->isSuperAdmin())
            @php $userActive = request()->routeIs('users.*'); @endphp
            <li class="nav-item">
                <a href="#menuUser" data-bs-toggle="collapse" class="nav-link sidebar-link text-white d-flex align-items-center justify-content-between {{ $userActive ? 'active' : '' }}" aria-expanded="{{ $userActive ? 'true' : 'false' }}">
                    <span><i class="bi bi-people"></i> <span class="sidebar-link-text">User Management</span></span>
                    <i class="bi bi-chevron-down sidebar-collapse-icon"></i>
                </a>
                <div class="collapse {{ $userActive ? 'show' : '' }}" id="menuUser" data-bs-parent="#sidebarMenuAccordion">
                    <ul class="nav flex-column sidebar-submenu">
                        <li class="nav-item">
                            <a href="{{ route('users.index') }}" class="nav-link sidebar-sublink {{ request()->routeIs('users.index') || request()->routeIs('users.edit') ? 'active' : '' }}">
                                <i class="bi bi-person-lines-fill"></i> Data User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('users.create') }}" class="nav-link sidebar-sublink {{ request()->routeIs('users.create') ? 'active' : '' }}">
                                <i class="bi bi-person-plus"></i> Add User
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        @endif

        {{-- Activity Logs - level-atas, tetap role-only (sengaja tidak
             ikut sistem Permission Override, lihat config/permissions.php). --}}
        @if(auth()->user()->hasRole('super_admin', 'admin'))
            <li class="nav-item">
                <a href="{{ route('activity-logs.index') }}"
                    class="nav-link sidebar-link text-white {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i> <span class="sidebar-link-text">Activity Logs</span>
                </a>
            </li>
        @endif

        {{-- Trash: permission 'trash.view'. --}}
        @if(auth()->user()->hasPermission('trash', 'view'))
            <li class="nav-item">
                <a href="{{ route('trash.index') }}"
                    class="nav-link sidebar-link text-white {{ request()->routeIs('trash.*') ? 'active' : '' }}">
                    <i class="bi bi-trash"></i> <span class="sidebar-link-text">Trash</span>
                </a>
            </li>
        @endif

        {{-- Pengaturan: Umum (Super Admin), Lokasi (Admin dengan akses Inventory), Tanda Tangan Saya
             (semua role). Kalau cuma Tanda Tangan yang berhak, tampil sebagai link langsung. --}}
        @php
            $setShowUmum   = auth()->user()->isSuperAdmin();
            $setShowLokasi = auth()->user()->hasRole('super_admin', 'admin') && auth()->user()->hasPermission('inventory', 'view');
            $setActive     = request()->routeIs('settings.*', 'inventory.locations.*', 'signature.*');
        @endphp
        @if($setShowUmum || $setShowLokasi)
            <li class="nav-item">
                <a href="#menuPengaturan" data-bs-toggle="collapse" class="nav-link sidebar-link text-white d-flex align-items-center justify-content-between {{ $setActive ? 'active' : '' }}" aria-expanded="{{ $setActive ? 'true' : 'false' }}">
                    <span><i class="bi bi-gear"></i> <span class="sidebar-link-text">Pengaturan</span></span>
                    <i class="bi bi-chevron-down sidebar-collapse-icon"></i>
                </a>
                <div class="collapse {{ $setActive ? 'show' : '' }}" id="menuPengaturan" data-bs-parent="#sidebarMenuAccordion">
                    <ul class="nav flex-column sidebar-submenu">
                        @if($setShowUmum)
                            <li class="nav-item">
                                <a href="{{ route('settings.index') }}" class="nav-link sidebar-sublink {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                                    <i class="bi bi-building"></i> Umum
                                </a>
                            </li>
                        @endif
                        @if($setShowLokasi)
                            <li class="nav-item">
                                <a href="{{ route('inventory.locations.index') }}" class="nav-link sidebar-sublink {{ request()->routeIs('inventory.locations.*') ? 'active' : '' }}">
                                    <i class="bi bi-geo-alt"></i> Lokasi
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('signature.index') }}" class="nav-link sidebar-sublink {{ request()->routeIs('signature.*') ? 'active' : '' }}">
                                <i class="bi bi-pen"></i> Tanda Tangan Saya
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        @else
            <li class="nav-item">
                <a href="{{ route('signature.index') }}"
                    class="nav-link sidebar-link text-white {{ request()->routeIs('signature.*') ? 'active' : '' }}">
                    <i class="bi bi-pen"></i> <span class="sidebar-link-text">Tanda Tangan Saya</span>
                </a>
            </li>
        @endif

    </ul>

    <hr class="border-white opacity-10 my-3">

    {{-- Profil pengguna --}}
    <div class="mb-3">
        <div class="d-flex align-items-center p-2 rounded-4 sidebar-profile">
            @auth
                <div class="avatar-initial flex-shrink-0 u-w-40px u-h-40px u-fs-1p05rem u-bg-rgba255-255-255-p12 u-c-fff">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="ms-3 overflow-hidden sidebar-link-text">
                    <div class="text-white fw-semibold text-truncate mb-0 lh-sm u-fs-p9rem">
                        {{ Auth::user()->name }}
                    </div>
                    <small class="text-white-50 text-truncate d-block u-fs-p72rem">
                        {{ Auth::user()->email ?? 'Administrator' }}
                    </small>
                </div>
            @endauth
        </div>
    </div>

    {{-- Keluar sistem --}}
    <form action="{{ route('logout') }}" method="POST" data-confirm="Apakah Anda yakin ingin keluar sistem?" data-confirm-label="Keluar">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-light w-100 d-flex align-items-center justify-content-center py-2 rounded-3">
            <i class="bi bi-box-arrow-left me-2"></i> <span class="sidebar-link-text">Keluar Sistem</span>
        </button>
    </form>
</div>

