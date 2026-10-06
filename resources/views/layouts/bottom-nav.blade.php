{{-- Navigasi bawah HP/tablet. Item mengikuti izin user; Scan ada di tengah. --}}
@php
    $navUser   = auth()->user();
    $navScan   = $navUser->hasPermission('scan_barang', 'view');
    $navTrack  = $navUser->hasPermission('tracking_progress', 'view');
@endphp
<nav class="app-bottom-nav d-lg-none" aria-label="Navigasi utama">
    <a href="{{ route('dashboard') }}" class="app-bottom-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i class="bi bi-house-door"></i><span>Beranda</span>
    </a>

    <a href="{{ route('announcements.index') }}" class="app-bottom-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
        <i class="bi bi-bell"></i><span>Notifikasi</span>
        <span class="app-bottom-badge d-none" data-notif-badge></span>
    </a>

    @if($navScan)
        <a href="{{ route('scan.index') }}" class="app-bottom-link app-bottom-scan {{ request()->routeIs('scan.index') ? 'active' : '' }}" aria-label="Scan barang">
            <span class="scan-disc"><i class="bi bi-upc-scan"></i></span>
            <span>Scan</span>
        </a>
    @endif

    @if($navTrack)
        <a href="{{ route('projects.index') }}" class="app-bottom-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
            <i class="bi bi-kanban"></i><span>Project</span>
        </a>
    @endif

    <button type="button" class="app-bottom-link" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar">
        <i class="bi bi-list"></i><span>Menu</span>
    </button>
</nav>
