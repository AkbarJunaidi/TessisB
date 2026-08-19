<div class="d-flex flex-column flex-shrink-0 p-3 text-white ap-sidebar">
    {{-- Tombol tutup - hanya terlihat saat sidebar jadi drawer (tablet/mobile) --}}
    <button type="button" class="ap-sidebar-close-btn btn btn-sm btn-outline-light rounded-circle align-self-end mb-2"
            id="sidebarCloseBtn" style="display: none; width: 32px; height: 32px;" aria-label="Tutup menu">
        <i class="bi bi-x-lg"></i>
    </button>

    <!-- Brand Info -->
    <a href="{{ route('dashboard') }}" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <img src="{{ asset('image/logo.png') }}" alt="logo" class="me-2" style="height: 72px; width: auto; object-fit: contain;">
    </a>

    <hr class="border-white opacity-25 my-3">

    <ul class="nav nav-pills flex-column mb-auto gap-1" id="sidebarMenuAccordion">


        <li class="nav-item mb-1">
            <a href="{{ route('dashboard') }}"
                class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active fw-semibold' : 'opacity-75' }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        @if(auth()->user()->hasPermission('inventory', 'view'))

            @php
                $invActive = request()->routeIs('inventory.*');
            @endphp
            <li class="nav-item">
                <a href="#menuInventory" data-bs-toggle="collapse" class="nav-link text-white d-flex align-items-center justify-content-between {{ $invActive ? 'active fw-semibold' : 'opacity-75' }}" aria-expanded="{{ $invActive ? 'true' : 'false' }}">
                    <div>
                        <i class="bi bi-box-seam me-2"></i> Inventory
                    </div>
                    <i class="bi bi-chevron-down fs-7 sidebar-collapse-icon"></i>
                </a>
                <div class="collapse {{ $invActive ? 'show' : '' }}" id="menuInventory" data-bs-parent="#sidebarMenuAccordion">
                    <ul class="nav flex-column bg-white rounded-3 mt-1 mx-2 py-2 shadow-sm overflow-hidden">
                        <li class="nav-item">
                            <a href="{{ route('inventory.index') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('inventory.index') || request()->routeIs('inventory.show') || request()->routeIs('inventory.edit') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                                <i class="bi bi-list-ul me-2 text-muted"></i> Inventory List
                            </a>
                        </li>
                        @if(auth()->user()->hasPermission('inventory', 'create'))
                        <li class="nav-item">
                            <a href="{{ route('inventory.create') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('inventory.create') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                                <i class="bi bi-plus-circle me-2 text-muted"></i> Add Inventory
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>
        @endif

        <!--tracking progress / progress management-->
        @if(auth()->user()->hasPermission('tracking_progress', 'view'))
        @php
            $trackActive = request()->routeIs('projects.*') || request()->routeIs('tasks.*') || request()->routeIs('borrowed-items.*');
        @endphp
        <li class="nav-item">
            <a href="#menuTracking" data-bs-toggle="collapse" class="nav-link text-white d-flex align-items-center justify-content-between {{ $trackActive ? 'active fw-semibold' : 'opacity-75' }}" aria-expanded="{{ $trackActive ? 'true' : 'false' }}">
                <div>
                    <i class="bi bi-kanban me-2"></i> Progress Management
                </div>
                <i class="bi bi-chevron-down fs-7 sidebar-collapse-icon"></i>
            </a>
            <div class="collapse {{ $trackActive ? 'show' : '' }}" id="menuTracking" data-bs-parent="#sidebarMenuAccordion">
                <ul class="nav flex-column bg-white rounded-3 mt-1 mx-2 py-2 shadow-sm overflow-hidden">
                    <li class="nav-item">
                        <a href="{{ route('projects.index') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('projects.index') || request()->routeIs('projects.show') || request()->routeIs('tasks.show') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                            <i class="bi bi-kanban me-2 text-muted"></i> Projects
                        </a>
                    </li>
                    <li class="nav-item">
                    @if(auth()->user()->hasPermission('borrowed_items', 'view'))
                        <a href="{{ route('borrowed-items.index') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('borrowed-items.*') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                            <i class="bi bi-box-arrow-in-left me-2 text-muted"></i> Barang Pinjaman
                        </a>
                    @endif
                    </li>
                    @if(auth()->user()->hasPermission('tracking_progress', 'create_project'))
                    <li class="nav-item">
                        <a href="{{ route('projects.create') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('projects.create') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                            <i class="bi bi-folder-plus me-2 text-muted"></i> Add Project
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </li>
        @endif

        <!--integrasi data-->
        @if(auth()->user()->hasPermission('data_integration', 'view'))
        @php
            $intActive = request()->routeIs('folders.*') || request()->routeIs('files.*');
        @endphp
        <li class="nav-item">
            <a href="#menuIntegrasi" data-bs-toggle="collapse" class="nav-link text-white d-flex align-items-center justify-content-between {{ $intActive ? 'active fw-semibold' : 'opacity-75' }}" aria-expanded="{{ $intActive ? 'true' : 'false' }}">
                <div>
                    <i class="bi bi-database me-2"></i> Integrasi Data
                </div>
                <i class="bi bi-chevron-down fs-7 sidebar-collapse-icon"></i>
            </a>
            <div class="collapse {{ $intActive ? 'show' : '' }}" id="menuIntegrasi" data-bs-parent="#sidebarMenuAccordion">
                <ul class="nav flex-column bg-white rounded-3 mt-1 mx-2 py-2 shadow-sm overflow-hidden">
                    <li class="nav-item">
                        <a href="{{ route('folders.index') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('folders.index') || request()->routeIs('folders.show') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                            <i class="bi bi-folder2-open me-2 text-muted"></i> Folder Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('files.my-files') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('files.my-files') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                            <i class="bi bi-file-earmark-arrow-up me-2 text-muted"></i> My Files
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        @endif

        @if(auth()->user()->hasPermission('user_management', 'view_user'))
            <!--user management-->
            @php
                $userActive = request()->routeIs('users.*');
            @endphp
            <li class="nav-item">
                <a href="#menuUser" data-bs-toggle="collapse" class="nav-link text-white d-flex align-items-center justify-content-between {{ $userActive ? 'active fw-semibold' : 'opacity-75' }}" aria-expanded="{{ $userActive ? 'true' : 'false' }}">
                    <div>
                        <i class="bi bi-people me-2"></i> User Management
                    </div>
                    <i class="bi bi-chevron-down fs-7 sidebar-collapse-icon"></i>
                </a>
                <div class="collapse {{ $userActive ? 'show' : '' }}" id="menuUser" data-bs-parent="#sidebarMenuAccordion">
                    <ul class="nav flex-column bg-white rounded-3 mt-1 mx-2 py-2 shadow-sm overflow-hidden">
                        <li class="nav-item">
                            <a href="{{ route('users.index') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('users.index') || request()->routeIs('users.edit') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                                <i class="bi bi-person-lines-fill me-2 text-muted"></i> Data User
                            </a>
                        </li>
                        @if(auth()->user()->isSuperAdmin())
                        <li class="nav-item">
                            <a href="{{ route('users.create') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('users.create') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                                <i class="bi bi-person-plus me-2 text-muted"></i> Add User
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>
        @endif

        @if(auth()->user()->hasRole('super_admin', 'admin'))
            <li class="nav-item mb-1">
                <a href="{{ route('activity-logs.index') }}"
                    class="nav-link text-white {{ request()->routeIs('activity-logs.*') ? 'active fw-semibold' : 'opacity-75' }}">
                    <i class="bi bi-journal-text me-2"></i> Activity Logs
                </a>
            </li>
        @endif

        @if(auth()->user()->hasRole('super_admin', 'admin'))
            <li class="nav-item mb-1">
                <a href="{{ route('trash.index') }}"
                    class="nav-link text-white {{ request()->routeIs('trash.*') ? 'active fw-semibold' : 'opacity-75' }}">
                    <i class="bi bi-trash me-2"></i> Trash
                </a>
            </li>
        @endif

    </ul>

    <hr class="border-white opacity-25 my-3">

    <!--profil pengguna & keluar sistem-->
    <div class="px-1">
        @auth
            <div class="d-flex align-items-center gap-2 mb-3 p-2 rounded-4 bg-white bg-opacity-10">
                <!--inisial user-->
                <div class="bg-white bg-opacity-25 text-white rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                     style="width: 38px; height: 38px; font-size: 1.05rem;">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <!--nama dan info-->
                <div class="overflow-hidden">
                    <div class="text-white fw-bold text-truncate lh-1" style="font-size: 1rem;">
                        {{ Auth::user()->name }}
                    </div>
                    <small class="text-white-50 text-truncate d-block mt-1" style="font-size: 0.74rem;" title="{{ Auth::user()->email ?? 'Administrator' }}">
                        {{ Auth::user()->email ?? 'Administrator' }}
                    </small>
                </div>
            </div>
        @endauth

        <!--keluar sistem-->
        <form action="{{ route('logout') }}"
            method="POST"
            onsubmit="return confirm('Apakah Anda yakin ingin keluar sistem?');">

            @csrf

            <button type="submit"
                class="btn btn-outline-light rounded-4 w-100 d-flex align-items-center justify-content-center gap-2 py-2 fw-semibold">
                <i class="bi bi-box-arrow-left"></i> Keluar Sistem
            </button>

        </form>
    </div>
</div>

<style>
    /*anim rotasi panah dropdown*/
    .sidebar-collapse-icon {
        transition: transform 0.3s ease;
    }
    [aria-expanded="true"] .sidebar-collapse-icon {
        transform: rotate(180deg);
    }

    /*efek hover - warna diatur di public/css/theme.css (.sidebar-dropdown-item:hover)*/
    .sidebar-dropdown-item {
        transition: all 0.2s ease;
    }
    .sidebar-dropdown-item:hover {
        padding-left: 1.25rem !important;
    }
</style>
