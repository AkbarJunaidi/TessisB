{{-- Header offcanvas ini HANYA tampil di layar <992px (mobile/tablet).
     Di desktop, offcanvas-lg otomatis jadi sidebar statis tanpa header ini. --}}
<div class="offcanvas-header d-lg-none px-3 pt-3 pb-2">
    <a href="{{ route('dashboard') }}" class="sidebar-logo-wrap text-decoration-none" id="appSidebarLabel">
        <img src="{{ asset('image/logo.png') }}" alt="Logo" class="sidebar-logo-mobile">
    </a>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Tutup menu"></button>
</div>

<div class="offcanvas-body d-flex flex-column p-3 text-white h-100 sidebar-body">

    {{-- Brand (hanya tampil di desktop; di mobile sudah ada di offcanvas-header) --}}
    <a href="{{ route('dashboard') }}" class="d-none d-lg-flex align-items-center text-white text-decoration-none mb-2 px-1">
        <img src="{{ asset('image/logo.png') }}" alt="Logo" style="height: 64px; width: auto; object-fit: contain;">
    </a>

    <hr class="border-white opacity-10 sidebar-divider">

    <ul class="nav nav-pills flex-column mb-auto gap-1 sidebar-nav" id="sidebarMenuAccordion" role="menu" aria-label="Menu utama">

        <li class="nav-item">
            <a href="{{ route('dashboard') }}"
                class="nav-link sidebar-link text-white {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>
        </li>

        @if(auth()->user()->hasRole('super_admin', 'admin'))

            @php $invActive = request()->routeIs('inventory.*'); @endphp
            <li class="nav-item">
                <a href="#menuInventory" data-bs-toggle="collapse" class="nav-link sidebar-link text-white d-flex align-items-center justify-content-between {{ $invActive ? 'active' : '' }}" aria-expanded="{{ $invActive ? 'true' : 'false' }}">
                    <span><i class="bi bi-box-seam"></i> <span>Inventory</span></span>
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
                <span><i class="bi bi-kanban"></i> <span>Progress Management</span></span>
                <i class="bi bi-chevron-down sidebar-collapse-icon"></i>
            </a>
            <div class="collapse {{ $trackActive ? 'show' : '' }}" id="menuTracking" data-bs-parent="#sidebarMenuAccordion">
                <ul class="nav flex-column sidebar-submenu">
                    <li class="nav-item">
                        <a href="{{ route('projects.index') }}" class="nav-link sidebar-sublink {{ request()->routeIs('projects.index') || request()->routeIs('projects.show') || request()->routeIs('tasks.show') ? 'active' : '' }}">
                            <i class="bi bi-kanban"></i> Projects
                        </a>
                    </li>
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

        {{-- Integrasi Data --}}
        @php $intActive = request()->routeIs('folders.*') || request()->routeIs('files.*'); @endphp
        <li class="nav-item">
            <a href="#menuIntegrasi" data-bs-toggle="collapse" class="nav-link sidebar-link text-white d-flex align-items-center justify-content-between {{ $intActive ? 'active' : '' }}" aria-expanded="{{ $intActive ? 'true' : 'false' }}">
                <span><i class="bi bi-database"></i> <span>Integrasi Data</span></span>
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

        @if(auth()->user()->isSuperAdmin())
            @php $userActive = request()->routeIs('users.*'); @endphp
            <li class="nav-item">
                <a href="#menuUser" data-bs-toggle="collapse" class="nav-link sidebar-link text-white d-flex align-items-center justify-content-between {{ $userActive ? 'active' : '' }}" aria-expanded="{{ $userActive ? 'true' : 'false' }}">
                    <span><i class="bi bi-people"></i> <span>User Management</span></span>
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

        {{-- Activity Logs & Trash - level-atas, sejajar modul lain (bukan submenu) --}}
        @if(auth()->user()->hasRole('super_admin', 'admin'))
            <li class="nav-item">
                <a href="{{ route('activity-logs.index') }}"
                    class="nav-link sidebar-link text-white {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i> <span>Activity Logs</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('trash.index') }}"
                    class="nav-link sidebar-link text-white {{ request()->routeIs('trash.*') ? 'active' : '' }}">
                    <i class="bi bi-trash"></i> <span>Trash</span>
                </a>
            </li>
        @endif

    </ul>

    <hr class="border-white opacity-10 my-3">

    {{-- Profil pengguna --}}
    <div class="mb-3">
        <div class="d-flex align-items-center p-2 rounded-4 sidebar-profile">
            @auth
                <div class="avatar-initial flex-shrink-0" style="width:40px;height:40px;font-size:1.05rem;background:rgba(255,255,255,.12);color:#fff;">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="ms-3 overflow-hidden">
                    <div class="text-white fw-semibold text-truncate mb-0 lh-sm" style="font-size:.9rem;">
                        {{ Auth::user()->name }}
                    </div>
                    <small class="text-white-50 text-truncate d-block" style="font-size:.72rem;">
                        {{ Auth::user()->email ?? 'Administrator' }}
                    </small>
                </div>
            @endauth
        </div>
    </div>

    {{-- Keluar sistem --}}
    <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin keluar sistem?');">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-light w-100 d-flex align-items-center justify-content-center py-2 rounded-3">
            <i class="bi bi-box-arrow-left me-2"></i> Keluar Sistem
        </button>
    </form>
</div>

<style>
    /* Warna & hover disesuaikan token theme.css (var(--c-primary) dsb.) via inline karena
       sidebar punya latar gelap khusus (navy gradient) - style ini scoped di file ini saja. */
    .sidebar-link {
        border-radius: .65rem;
        padding: .65rem .85rem;
        font-size: .9rem;
        font-weight: 500;
        opacity: .85;
        transition: all .2s ease;
        display: flex;
        align-items: center;
        gap: .65rem;
    }
    .sidebar-link i { font-size: 1.05rem; width: 1.1rem; text-align: center; }
    .sidebar-link:hover { background: rgba(255,255,255,.08); opacity: 1; }
    .sidebar-link.active {
        background: var(--c-primary, #0d84fc);
        opacity: 1;
        font-weight: 600;
        box-shadow: 0 6px 16px -4px rgba(13,132,252,.55);
    }

    .sidebar-collapse-icon { font-size: .75rem; transition: transform .25s ease; }
    [aria-expanded="true"] .sidebar-collapse-icon { transform: rotate(180deg); }

    .sidebar-submenu {
        background: rgba(255,255,255,.05);
        border-radius: .65rem;
        margin: .25rem .25rem 0;
        padding: .35rem;
        gap: .1rem;
    }
    .sidebar-sublink {
        color: rgba(255,255,255,.75);
        font-size: .84rem;
        padding: .55rem .7rem;
        border-radius: .5rem;
        display: flex;
        align-items: center;
        gap: .6rem;
        transition: all .15s ease;
    }
    .sidebar-sublink i { font-size: .95rem; width: 1rem; text-align: center; }
    .sidebar-sublink:hover { background: rgba(255,255,255,.1); color: #fff; }
    .sidebar-sublink.active {
        background: #fff;
        color: var(--c-accent, #035eb9);
        font-weight: 700;
    }

    .sidebar-profile { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08); }

    /* Scrollbar tipis untuk sidebar (khusus WebKit, degradasi aman di browser lain) */
    .offcanvas-body::-webkit-scrollbar { width: 6px; }
    .offcanvas-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 10px; }
</style>
