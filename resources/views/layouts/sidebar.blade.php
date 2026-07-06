<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark h-100">
    <a href="{{ route('dashboard') }}" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <i class="bi bi-grid-1x2-fill me-2 fs-4 text-primary"></i>
        <span class="fs-5 fw-bold tracking-wide">Tessis PROJECT</span>
    </a>

    <hr class="border-secondary my-3">

    <ul class="nav nav-pills flex-column mb-auto">

        <li class="nav-item mb-1">
            <a href="{{ route('dashboard') }}"
                class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active fw-semibold' : 'opacity-75' }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        @if(auth()->user()->hasRole('super_admin', 'admin'))

            <li class="mt-3 mb-1 px-3 text-uppercase text-secondary fw-bold fs-7"
                style="font-size: 0.75rem; letter-spacing: 1px;">
                Inventory
            </li>

            <li class="nav-item mb-1">
                <a href="{{ route('inventory.index') }}"
                    class="nav-link text-white {{ request()->routeIs('inventory.index') || request()->routeIs('inventory.show') || request()->routeIs('inventory.edit') ? 'active fw-semibold' : 'opacity-75' }}">
                    <i class="bi bi-box-seam me-2"></i> Inventory List
                </a>
            </li>

            <li class="nav-item mb-1">
                <a href="{{ route('inventory.create') }}"
                    class="nav-link text-white {{ request()->routeIs('inventory.create') ? 'active fw-semibold' : 'opacity-75' }}">
                    <i class="bi bi-plus-circle me-2"></i> Add Inventory
                </a>
            </li>

        @endif


        <li class="mt-3 mb-1 px-3 text-uppercase text-secondary fw-bold fs-7"
            style="font-size: 0.75rem; letter-spacing: 1px;">
            Tracking Progress
        </li>

        <li class="nav-item mb-1">
            <a href="{{ route('projects.index') }}"
                class="nav-link text-white {{ request()->routeIs('projects.index') || request()->routeIs('projects.show') || request()->routeIs('tasks.show') ? 'active fw-semibold' : 'opacity-75' }}">
                <i class="bi bi-kanban me-2"></i> Projects
            </a>
        </li>

        <li class="nav-item mb-1">
            <a href="{{ route('projects.create') }}"
                class="nav-link text-white {{ request()->routeIs('projects.create') ? 'active fw-semibold' : 'opacity-75' }}">
                <i class="bi bi-folder-plus me-2"></i> Add Project
            </a>
        </li>


        @if(auth()->user()->hasRole('super_admin', 'admin'))

            <li class="nav-item mb-1">
                <a href="{{ route('activity-logs.index') }}"
                    class="nav-link text-white {{ request()->routeIs('activity-logs.index') ? 'active fw-semibold' : 'opacity-75' }}">
                    <i class="bi bi-journal-text me-2"></i> Activity Logs
                </a>
            </li>

        @endif


        <li class="mt-3 mb-1 px-3 text-uppercase text-secondary fw-bold fs-7"
            style="font-size: 0.75rem; letter-spacing: 1px;">
            Integrasi Data
        </li>

        <li class="nav-item mb-1">
            <a href="{{ route('folders.index') }}"
                class="nav-link text-white {{ request()->routeIs('folders.index') || request()->routeIs('folders.show') ? 'active fw-semibold' : 'opacity-75' }}">
                <i class="bi bi-folder2-open me-2"></i> Folder Management
            </a>
        </li>

        <li class="nav-item mb-1">
            <a href="{{ route('files.my-files') }}"
                class="nav-link text-white {{ request()->routeIs('files.my-files') ? 'active fw-semibold' : 'opacity-75' }}">
                <i class="bi bi-file-earmark-arrow-up me-2"></i> My Files
            </a>
        </li>


            @if(auth()->user()->isSuperAdmin())

            <li class="mt-3 mb-1 px-3 text-uppercase text-secondary fw-bold fs-7"
                style="font-size: 0.75rem; letter-spacing: 1px;">
                User Management
            </li>

            <li class="nav-item mb-1">
                <a href="{{ route('users.index') }}"
                    class="nav-link text-white {{ request()->routeIs('users.index') || request()->routeIs('users.edit') ? 'active fw-semibold' : 'opacity-75' }}">
                    <i class="bi bi-people me-2"></i> Data User
                </a>
            </li>

            <li class="nav-item mb-1">
                <a href="{{ route('users.create') }}"
                    class="nav-link text-white {{ request()->routeIs('users.create') ? 'active fw-semibold' : 'opacity-75' }}">
                    <i class="bi bi-person-plus me-2"></i> Add User
                </a>
            </li>

            @endif

    </ul>

    <hr class="border-secondary my-3">

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

