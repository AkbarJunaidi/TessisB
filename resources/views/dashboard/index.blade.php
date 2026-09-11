@extends('layouts.app')

@section('title', 'Dashboard Utama')

@section('content')

    @php
        $user = auth()->user();
    @endphp

    <div class="page-heading">
        <div>
            <h3>Dashboard Overview</h3>
            <p>Selamat datang, {{ $user->name }} berikut ringkasan sistem hari ini.</p>
        </div>
    </div>

    {{-- Bento grid statistik - dipindah paling atas (ringkasan cepat dulu,
         pola umum ERP dashboard) - permission-aware, auto-reflow (lihat
         .dashboard-stat-grid di theme.css). Tiap kartu dicek izinnya
         sendiri-sendiri, jadi jumlah kartu yang muncul memang berbeda
         wajar per role, bukan bug. --}}
    <div class="dashboard-stat-grid mb-4">

        @if($user->hasPermission('inventory', 'view'))
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem; letter-spacing:.06em;">Inventory</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_inventory'] }}</h3>
                        @if($statistics['new_inventory_this_month'] > 0)
                            <span class="badge-soft-success mt-2 d-inline-block">+{{ $statistics['new_inventory_this_month'] }} barang baru</span>
                        @endif
                    </div>
                    <div class="icon-tile" style="background: rgba(13,132,252,.12); color: var(--c-primary);">
                        <i class="bi bi-box-seam fs-5"></i>
                    </div>
                </div>
            </div>
        @endif

        @if($user->hasPermission('tracking_progress', 'view'))
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem; letter-spacing:.06em;">Project</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_project'] }}</h3>
                        @if($statistics['new_projects_this_month'] > 0)
                            <span class="badge-soft-success mt-2 d-inline-block">+{{ $statistics['new_projects_this_month'] }} project baru</span>
                        @endif
                    </div>
                    <div class="icon-tile" style="background: rgba(25,135,84,.12); color: #198754;">
                        <i class="bi bi-kanban fs-5"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem; letter-spacing:.06em;">Task</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_task'] }}</h3>
                    </div>
                    <div class="icon-tile" style="background: rgba(255,193,7,.18); color: #997404;">
                        <i class="bi bi-list-task fs-5"></i>
                    </div>
                </div>
            </div>
        @endif

        @if($user->hasPermission('data_integration', 'view'))
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem; letter-spacing:.06em;">Files</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_files'] }}</h3>
                    </div>
                    <div class="icon-tile" style="background: rgba(13,202,240,.16); color: #0aa2c0;">
                        <i class="bi bi-file-earmark-arrow-up fs-5"></i>
                    </div>
                </div>
            </div>
        @endif

        {{-- Kontak SENGAJA tidak digate hasPermission() - modul ini aksesnya
             role-based (Super Admin/Admin/Employee, lihat routes/web.php),
             tidak diatur lewat config/permissions.php seperti modul lain. --}}
        <div class="stat-card p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem; letter-spacing:.06em;">Kontak</span>
                    <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_contact'] }}</h3>
                    @if($statistics['new_contacts_this_month'] > 0)
                        <span class="badge-soft-success mt-2 d-inline-block">+{{ $statistics['new_contacts_this_month'] }} kontak baru</span>
                    @endif
                </div>
                <div class="icon-tile" style="background: rgba(111,66,193,.12); color: #6f42c1;">
                    <i class="bi bi-person-lines-fill fs-5"></i>
                </div>
            </div>
        </div>

        @if($user->hasPermission('borrowed_items', 'view'))
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem; letter-spacing:.06em;">Belum Dikembalikan</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $borrowedUnitsCount }}</h3>
                    </div>
                    <div class="icon-tile" style="background: rgba(220,53,69,.12); color: #b02a37;">
                        <i class="bi bi-box-arrow-in-left fs-5"></i>
                    </div>
                </div>
            </div>
        @endif

        @if($user->hasPermission('user_management', 'view_user'))
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem; letter-spacing:.06em;">Users</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_user'] }}</h3>
                        @if($statistics['new_users_this_month'] > 0)
                            <span class="badge-soft-success mt-2 d-inline-block">+{{ $statistics['new_users_this_month'] }} bulan ini</span>
                        @endif
                    </div>
                    <div class="icon-tile" style="background: rgba(11,36,71,.1); color: var(--c-navy);">
                        <i class="bi bi-people fs-5"></i>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- Task Saya + Project Hari Ini & 7 Hari Ke Depan - dipasangkan 2
         kolom (bukan ditumpuk full-width) supaya tidak ada card yang
         kelihatan terlalu besar. Tinggi tabel dibatasi scroll internal
         (.dashboard-list-panel di theme.css, max-height 300px) - berapa
         pun jumlah baris datanya, card-nya tidak akan membengkak. --}}
    <div class="row g-4 mb-4">

        @if($user->hasPermission('tracking_progress', 'view'))
            <div class="col-lg-6">
                <div class="app-panel dashboard-list-panel overflow-hidden h-100">
                    <div class="app-panel-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-tile" style="background: rgba(255,193,7,.18); color: #997404;">
                                <i class="bi bi-list-task fs-5"></i>
                            </div>
                            <h5 class="fw-bold text-navy m-0">Task Saya</h5>
                        </div>
                        <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-kanban me-1"></i>Lihat Project
                        </a>
                    </div>

                    @if($myTasks->isEmpty())
                        <div class="empty-state">
                            <div class="empty-icon"><i class="bi bi-check2-circle"></i></div>
                            <span class="fw-medium">Tidak ada task yang perlu dikerjakan saat ini. Mantap!</span>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4" style="width:40%;">Task</th>
                                        <th style="width:30%;">Project</th>
                                        <th class="pe-4" style="width:30%;">Deadline</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    @foreach($myTasks as $task)
                                        @php
                                            $deadline = \Carbon\Carbon::parse($task->deadline);
                                            $isOverdue = $deadline->startOfDay()->lt(now()->startOfDay());
                                        @endphp
                                        <tr>
                                            <td class="ps-4 py-2 fw-semibold text-dark">{{ $task->title }}</td>
                                            <td class="py-2 text-secondary">{{ $task->project->name ?? '-' }}</td>
                                            <td class="py-2 pe-4">
                                                @if($isOverdue)
                                                    <span class="badge-soft-danger">
                                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $deadline->format('d/m/Y') }}
                                                    </span>
                                                @else
                                                    <span class="text-secondary">{{ $deadline->format('d/m/Y') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-6">
                <div class="app-panel dashboard-list-panel overflow-hidden h-100">
                    <div class="app-panel-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-tile" style="background: rgba(25,135,84,.12); color: #198754;">
                                <i class="bi bi-calendar-event fs-5"></i>
                            </div>
                            <h5 class="fw-bold text-navy m-0">Project 7 Hari Ke Depan</h5>
                        </div>
                    </div>

                    @if($upcomingProjects->isEmpty())
                        <div class="empty-state">
                            <div class="empty-icon"><i class="bi bi-calendar-x"></i></div>
                            <span class="fw-medium">Belum ada Project dalam 7 hari ke depan.</span>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4" style="width:40%;">Project</th>
                                        <th style="width:30%;">Client</th>
                                        <th class="pe-4" style="width:30%;">Tanggal Acara</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    @foreach($upcomingProjects as $project)
                                        @php
                                            $eventDate = \Carbon\Carbon::parse($project->event_date);
                                            $isToday = $eventDate->isToday();
                                        @endphp
                                        <tr>
                                            <td class="ps-4 py-2 fw-semibold text-dark">
                                                <a href="{{ route('projects.show', $project->id) }}" class="text-dark text-decoration-none">{{ $project->name }}</a>
                                            </td>
                                            <td class="py-2 text-secondary">{{ $project->client }}</td>
                                            <td class="py-2 pe-4">
                                                @if($isToday)
                                                    <span class="badge-soft-warning">
                                                        <i class="bi bi-star-fill me-1"></i>Hari Ini
                                                    </span>
                                                @else
                                                    <span class="text-secondary">{{ $eventDate->format('d/m/Y') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </div>

    {{-- Data Keuangan + Surat Jalan Terbaru - dipasangkan 2 kolom juga. --}}
    @if($financeSummary || $user->hasPermission('surat_jalan', 'view'))
        <div class="row g-4 mb-4">

            @if($financeSummary)
                <div class="col-lg-5">
                    <div class="app-panel overflow-hidden h-100">
                        <div class="app-panel-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-tile" style="background: rgba(25,135,84,.12); color: #198754;">
                                    <i class="bi bi-cash-coin fs-5"></i>
                                </div>
                                <h5 class="fw-bold text-navy m-0">Data Keuangan</h5>
                            </div>
                        </div>
                        <div class="p-3 p-md-4">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem; letter-spacing:.06em;">Pendapatan Bulan Ini</span>
                                    <h3 class="fw-bolder text-navy mt-2 mb-0">{{ \App\Support\Money::formatRupiah($financeSummary['revenue_this_month']) }}</h3>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem; letter-spacing:.06em;">Estimasi Pipeline</span>
                                    <h3 class="fw-bolder text-navy mt-2 mb-0">{{ \App\Support\Money::formatRupiah($financeSummary['pipeline_estimated_value']) }}</h3>
                                    <small class="text-muted">Estimasi project berjalan, bukan pendapatan riil.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($user->hasPermission('surat_jalan', 'view'))
                <div class="{{ $financeSummary ? 'col-lg-7' : 'col-lg-12' }}">
                    <div class="app-panel dashboard-list-panel overflow-hidden h-100">
                        <div class="app-panel-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-tile" style="background: rgba(13,132,252,.12); color: var(--c-primary);">
                                    <i class="bi bi-file-earmark-text fs-5"></i>
                                </div>
                                <h5 class="fw-bold text-navy m-0">Surat Jalan Terbaru</h5>
                            </div>
                        </div>

                        @if($recentSuratJalan->isEmpty())
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                                <span class="fw-medium">Belum ada Surat Jalan yang dibuat.</span>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover table-modern align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-4" style="width:25%;">Nomor</th>
                                            <th style="width:30%;">Project</th>
                                            <th style="width:25%;">Tanggal Acara</th>
                                            <th class="pe-4" style="width:20%;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        @foreach($recentSuratJalan as $suratJalan)
                                            <tr>
                                                <td class="ps-4 py-2 fw-semibold text-dark">
                                                    <a href="{{ route('surat-jalan.show', $suratJalan->id) }}" class="text-dark text-decoration-none">{{ $suratJalan->nomor }}</a>
                                                </td>
                                                <td class="py-2 text-secondary">{{ $suratJalan->project->name ?? '-' }}</td>
                                                <td class="py-2 text-secondary">
                                                    {{ $suratJalan->tanggal_acara ? \Carbon\Carbon::parse($suratJalan->tanggal_acara)->format('d/m/Y') : '-' }}
                                                </td>
                                                <td class="py-2 pe-4">
                                                    @if($suratJalan->status === 'Selesai')
                                                        <span class="badge-soft-success">{{ $suratJalan->status }}</span>
                                                    @else
                                                        <span class="badge-soft-warning">{{ $suratJalan->status }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    @endif

    {{-- Recent Activity - tetap full-width (log audit utama), tapi tinggi
         tabel tetap dibatasi supaya tidak membengkak kalau datanya banyak. --}}
    <div class="app-panel dashboard-list-panel overflow-hidden">
        <div class="app-panel-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-tile" style="background: rgba(13,132,252,.12); color: var(--c-primary);">
                    <i class="bi bi-clock-history fs-5"></i>
                </div>
                <h5 class="fw-bold text-navy m-0">
                    {{ $user->hasRole('super_admin', 'admin') ? 'Recent Activity Log' : 'Aktivitas Saya Terbaru' }}
                </h5>
            </div>
            {{-- Kondisi disamakan dengan helper hasRole() yang dipakai di sidebar,
                 karena kolom `role` disimpan snake_case ('super_admin','admin'), bukan 'Super Admin'. --}}
            @if($user->hasRole('super_admin', 'admin'))
                <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-eye me-1"></i>Lihat Semua
                </a>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:20%;">Waktu</th>
                        <th style="width:25%;">User</th>
                        <th style="width:30%;">Modul</th>
                        <th class="pe-4" style="width:25%;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($recentActivities as $activity)
                        <tr>
                            <td class="ps-4 py-2 fw-medium text-secondary">
                                <i class="bi bi-calendar-event me-2 text-muted"></i>{{ $activity->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-initial" style="width:30px;height:30px;font-size:.75rem;">
                                        {{ strtoupper(substr($activity->user->name ?? 'SY', 0, 2)) }}
                                    </div>
                                    <span class="fw-semibold text-dark">{{ $activity->user->name ?? 'System / Deleted User' }}</span>
                                </div>
                            </td>
                            <td class="py-2">
                                <span class="badge-soft-secondary">{{ $activity->module }}</span>
                            </td>
                            <td class="py-2 pe-4">
                                @if(in_array($activity->action, ['Delete', 'Logout']))
                                    <span class="badge-soft-danger">{{ $activity->action }}</span>
                                @elseif(in_array($activity->action, ['Create', 'Login', 'Upload']))
                                    <span class="badge-soft-success">{{ $activity->action }}</span>
                                @else
                                    <span class="badge-soft-warning">{{ $activity->action }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                                    <span class="fw-medium">Belum ada rekaman aktivitas terbaru saat ini.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
