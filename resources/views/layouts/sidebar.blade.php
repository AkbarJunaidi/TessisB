<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-primary h-100">
    <!-- Brand Info -->
    <a href="{{ route('dashboard') }}" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <img src="{{ asset('image/logo.png') }}" alt="logo" class="me-2" style="height: 72px; width: auto; object-fit: contain;">
    </a>

    <hr class="border-white opacity-25 my-3">

    <ul class="nav nav-pills flex-column mb-auto gap-1">


        <li class="nav-item mb-1">
            <a href="{{ route('dashboard') }}"
                class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active fw-semibold' : 'opacity-75' }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        @if(auth()->user()->hasRole('super_admin', 'admin'))

            @php
                $invActive = request()->routeIs('inventory.*');
            @endphp
            <li class="nav-item">
                <a href="#menuInventory" data-bs-toggle="collapse" class="nav-link text-white d-flex align-items-center justify-content-between {{ $invActive ? 'fw-semibold' : 'opacity-75' }}" aria-expanded="{{ $invActive ? 'true' : 'false' }}">
                    <div>
                        <i class="bi bi-box-seam me-2"></i> Inventory
                    </div>
                    <i class="bi bi-chevron-down fs-7 sidebar-collapse-icon"></i>
                </a>
                <div class="collapse {{ $invActive ? 'show' : '' }}" id="menuInventory">
                    <ul class="nav flex-column bg-white rounded-3 mt-1 mx-2 py-2 shadow-sm overflow-hidden">
                        <li class="nav-item">
                            <a href="{{ route('inventory.index') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('inventory.index') || request()->routeIs('inventory.show') || request()->routeIs('inventory.edit') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                                <i class="bi bi-list-ul me-2 text-muted"></i> Inventory List
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('inventory.create') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('inventory.create') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                                <i class="bi bi-plus-circle me-2 text-muted"></i> Add Inventory
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        @endif

        <!--tracking progress-->
        @php
            $trackActive = request()->routeIs('projects.*') || request()->routeIs('tasks.*') || request()->routeIs('activity-logs.*') || request()->routeIs('borrowed-items.*');
        @endphp
        <li class="nav-item">
            <a href="#menuTracking" data-bs-toggle="collapse" class="nav-link text-white d-flex align-items-center justify-content-between {{ $trackActive ? 'fw-semibold' : 'opacity-75' }}" aria-expanded="{{ $trackActive ? 'true' : 'false' }}">
                <div>
                    <i class="bi bi-kanban me-2"></i> Tracking Progress
                </div>
                <i class="bi bi-chevron-down fs-7 sidebar-collapse-icon"></i>
            </a>
            <div class="collapse {{ $trackActive ? 'show' : '' }}" id="menuTracking">
                <ul class="nav flex-column bg-white rounded-3 mt-1 mx-2 py-2 shadow-sm overflow-hidden">
                    <li class="nav-item">
                        <a href="{{ route('projects.index') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('projects.index') || request()->routeIs('projects.show') || request()->routeIs('tasks.show') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                            <i class="bi bi-kanban me-2 text-muted"></i> Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('borrowed-items.index') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('borrowed-items.*') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                            <i class="bi bi-box-arrow-in-left me-2 text-muted"></i> Barang Pinjaman
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('projects.create') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('projects.create') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                            <i class="bi bi-folder-plus me-2 text-muted"></i> Add Project
                        </a>
                    </li>

                    @if(auth()->user()->hasRole('super_admin', 'admin'))
                        <li class="nav-item">
                            <a href="{{ route('activity-logs.index') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('activity-logs.index') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                                <i class="bi bi-journal-text me-2 text-muted"></i> Activity Logs
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </li>

        <!--integrasi data-->
        @php
            $intActive = request()->routeIs('folders.*') || request()->routeIs('files.*');
        @endphp
        <li class="nav-item">
            <a href="#menuIntegrasi" data-bs-toggle="collapse" class="nav-link text-white d-flex align-items-center justify-content-between {{ $intActive ? 'fw-semibold' : 'opacity-75' }}" aria-expanded="{{ $intActive ? 'true' : 'false' }}">
                <div>
                    <i class="bi bi-database me-2"></i> Integrasi Data
                </div>
                <i class="bi bi-chevron-down fs-7 sidebar-collapse-icon"></i>
            </a>
            <div class="collapse {{ $intActive ? 'show' : '' }}" id="menuIntegrasi">
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

        @if(auth()->user()->isSuperAdmin())
            <!--user management-->
            @php
                $userActive = request()->routeIs('users.*');
            @endphp
            <li class="nav-item">
                <a href="#menuUser" data-bs-toggle="collapse" class="nav-link text-white d-flex align-items-center justify-content-between {{ $userActive ? 'fw-semibold' : 'opacity-75' }}" aria-expanded="{{ $userActive ? 'true' : 'false' }}">
                    <div>
                        <i class="bi bi-people me-2"></i> User Management
                    </div>
                    <i class="bi bi-chevron-down fs-7 sidebar-collapse-icon"></i>
                </a>
                <div class="collapse {{ $userActive ? 'show' : '' }}" id="menuUser">
                    <ul class="nav flex-column bg-white rounded-3 mt-1 mx-2 py-2 shadow-sm overflow-hidden">
                        <li class="nav-item">
                            <a href="{{ route('users.index') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('users.index') || request()->routeIs('users.edit') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                                <i class="bi bi-person-lines-fill me-2 text-muted"></i> Data User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('users.create') }}" class="nav-link sidebar-dropdown-item text-dark px-3 py-2 {{ request()->routeIs('users.create') ? 'fw-bold bg-light border-start border-3 border-primary' : '' }}">
                                <i class="bi bi-person-plus me-2 text-muted"></i> Add User
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        @endif

    </ul>

    <hr class="border-secondary my-3">

    <!--profil pengguna-->
    <div class="px-2 mb-3">
        <div class="d-flex align-items-center p-3 rounded-5 bg-white bg-opacity-10 border border-white border-opacity-25 shadow-sm">
            @auth
                <!--inisial user-->
                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm flex-shrink-0"
                     style="width: 42px; height: 42px; font-size: 1.2rem;">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <!--nama dan info-->
                <div class="ms-3 overflow-hidden">
                    <div class="text-white fw-semibold text-truncate mb-0 lh-1" style="font-size: 0.95rem;">
                        {{ Auth::user()->name }}
                    </div>
                    <small class="text-white-50 text-truncate d-block mt-1" style="font-size: 0.75rem;">
                        {{ Auth::user()->email ?? 'Administrator' }}
                    </small>
                </div>
            @endauth
        </div>
    </div>

    <!--keluar sistem-->
    <form action="{{ route('logout') }}"
        method="POST"
        onsubmit="return confirm('Apakah Anda yakin ingin keluar sistem?');">

        @csrf

        <button type="submit"
            class="btn btn-sm btn-outline-danger w-100 d-flex align-items-center justify-content-center py-2">
            <i class="bi bi-box-arrow-left me-2"></i> Keluar Sistem
        </button>

    </form>
</div>

<style>
    /*anim rotasi panah dropdown*/
    .sidebar-collapse-icon {
        transition: transform 0.3s ease;
    }
    [aria-expanded="true"] .sidebar-collapse-icon {
        transform: rotate(180deg);
    }

    /*efek hover putih*/
    .sidebar-dropdown-item {
        transition: all 0.2s ease;
    }
    .sidebar-dropdown-item:hover {
        background-color: #0d84fc;
        padding-left: 1.25rem !important;
    }
</style>
