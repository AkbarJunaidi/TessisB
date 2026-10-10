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

    {{-- Pusat pantau: kartu KPI "Perlu perhatian" (Super Admin/Admin) dan feed notifikasi terbaru. --}}
    @if(!empty($attention) || !empty($latestNotifications))
        <div class="row g-4 mb-4">
            @if(!empty($attention))
                <div class="col-xl-7">
                    <div class="row g-3">
                        @foreach($attention as $card)
                            <div class="col-6">
                                <a href="{{ route($card['route']) }}" class="stat-card p-3 p-md-4 d-block text-decoration-none">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="text-muted text-uppercase fw-bold u-fs-p7rem u-ls-p06em">{{ $card['label'] }}</span>
                                            <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $card['text'] }}</h3>
                                        </div>
                                        <div class="icon-tile {{ $card['count'] > 0 ? 'icon-tile-danger' : '' }}">
                                            <i class="bi {{ $card['icon'] }} fs-5"></i>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="{{ !empty($attention) ? 'col-xl-5' : 'col-12' }}">
                <div class="app-panel overflow-hidden h-100">
                    <div class="app-panel-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-tile">
                                <i class="bi bi-bell fs-5"></i>
                            </div>
                            <h5 class="fw-bold text-navy m-0">Notifikasi Terbaru</h5>
                        </div>
                        <a href="{{ route('announcements.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
                    </div>

                    <div class="list-group list-group-flush">
                        @forelse($latestNotifications as $item)
                            <a href="{{ $item['url'] }}" class="list-group-item list-group-item-action d-flex gap-3 align-items-start py-3">
                                <i class="bi {{ $item['icon'] }} fs-5"></i>
                                <div class="min-w-0">
                                    <div class="fw-semibold small">{{ $item['title'] }}</div>
                                    <div class="text-muted small text-truncate">{{ $item['message'] }}</div>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                                <span class="fw-medium">Tidak ada notifikasi.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Grid statistik permission-aware; jumlah kartu berbeda per role itu wajar (lihat .dashboard-stat-grid). --}}
    <div class="dashboard-stat-grid mb-4">

        @if($user->hasPermission('inventory', 'view'))
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold u-fs-p7rem u-ls-p06em">Inventaris</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_inventory'] }}</h3>
                        @if($statistics['new_inventory_this_month'] > 0)
                            <span class="badge-soft-success mt-2 d-inline-block">+{{ $statistics['new_inventory_this_month'] }} barang baru</span>
                        @endif
                    </div>
                    <div class="icon-tile">
                        <i class="bi bi-box-seam fs-5"></i>
                    </div>
                </div>
            </div>
        @endif

        @if($user->hasPermission('tracking_progress', 'view'))
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold u-fs-p7rem u-ls-p06em">Project</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_project'] }}</h3>
                        @if($statistics['new_projects_this_month'] > 0)
                            <span class="badge-soft-success mt-2 d-inline-block">+{{ $statistics['new_projects_this_month'] }} project baru</span>
                        @endif
                    </div>
                    <div class="icon-tile">
                        <i class="bi bi-kanban fs-5"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold u-fs-p7rem u-ls-p06em">Task</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_task'] }}</h3>
                    </div>
                    <div class="icon-tile">
                        <i class="bi bi-list-task fs-5"></i>
                    </div>
                </div>
            </div>
        @endif

        @if($user->hasPermission('data_integration', 'view'))
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold u-fs-p7rem u-ls-p06em">Files</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_files'] }}</h3>
                    </div>
                    <div class="icon-tile">
                        <i class="bi bi-file-earmark-arrow-up fs-5"></i>
                    </div>
                </div>
            </div>
        @endif

        {{-- Kontak tidak digate hasPermission(): aksesnya berbasis role (routes/web.php). --}}
        <div class="stat-card p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted text-uppercase fw-bold u-fs-p7rem u-ls-p06em">Kontak</span>
                    <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_contact'] }}</h3>
                    @if($statistics['new_contacts_this_month'] > 0)
                        <span class="badge-soft-success mt-2 d-inline-block">+{{ $statistics['new_contacts_this_month'] }} kontak baru</span>
                    @endif
                </div>
                <div class="icon-tile">
                    <i class="bi bi-person-lines-fill fs-5"></i>
                </div>
            </div>
        </div>

        @if($user->hasPermission('borrowed_items', 'view'))
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold u-fs-p7rem u-ls-p06em">Belum Dikembalikan</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $borrowedUnitsCount }}</h3>
                    </div>
                    <div class="icon-tile icon-tile-danger">
                        <i class="bi bi-box-arrow-in-left fs-5"></i>
                    </div>
                </div>
            </div>
        @endif

        @if($user->hasPermission('user_management', 'view_user'))
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold u-fs-p7rem u-ls-p06em">Users</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_user'] }}</h3>
                        @if($statistics['new_users_this_month'] > 0)
                            <span class="badge-soft-success mt-2 d-inline-block">+{{ $statistics['new_users_this_month'] }} bulan ini</span>
                        @endif
                    </div>
                    <div class="icon-tile icon-tile-navy">
                        <i class="bi bi-people fs-5"></i>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- Task Saya dan Project 7 hari dalam dua kolom; tinggi tabel dibatasi scroll internal (.dashboard-list-panel). --}}
    <div class="row g-4 mb-4">

        @if($user->hasPermission('tracking_progress', 'view'))
            <div class="col-lg-6">
                <div class="app-panel dashboard-list-panel overflow-hidden h-100">
                    <div class="app-panel-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-tile">
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
                                        <th class="ps-4 u-w-40pct">Task</th>
                                        <th class="u-w-30pct">Project</th>
                                        <th class="pe-4 u-w-30pct">Deadline</th>
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
                                            <td class="py-2 text-secondary">{{ $task->project->short_name ?? '-' }}</td>
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
                            <div class="icon-tile">
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
                                        <th class="ps-4 u-w-40pct">Project</th>
                                        <th class="u-w-30pct">Client</th>
                                        <th class="pe-4 u-w-30pct">Tanggal Acara</th>
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
                                                <a href="{{ route('projects.show', $project->id) }}" class="text-dark text-decoration-none" title="{{ $project->name }}">{{ $project->short_name }}</a>
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
                                <div class="icon-tile">
                                    <i class="bi bi-cash-coin fs-5"></i>
                                </div>
                                <h5 class="fw-bold text-navy m-0">Data Keuangan</h5>
                            </div>
                        </div>
                        <div class="p-3 p-md-4">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <span class="text-muted text-uppercase fw-bold u-fs-p7rem u-ls-p06em">Pendapatan Bulan Ini</span>
                                    <h3 class="fw-bolder text-navy mt-2 mb-0">{{ \App\Support\Money::formatRupiah($financeSummary['revenue_this_month']) }}</h3>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted text-uppercase fw-bold u-fs-p7rem u-ls-p06em">Estimasi Pipeline</span>
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
                                <div class="icon-tile">
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
                                            <th class="ps-4 u-w-25pct">Nomor</th>
                                            <th class="u-w-30pct">Project</th>
                                            <th class="u-w-25pct">Tanggal Acara</th>
                                            <th class="pe-4 u-w-20pct">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        @foreach($recentSuratJalan as $suratJalan)
                                            <tr>
                                                <td class="ps-4 py-2 fw-semibold text-dark">
                                                    <a href="{{ route('surat-jalan.show', $suratJalan->id) }}" class="text-dark text-decoration-none">{{ $suratJalan->nomor }}</a>
                                                </td>
                                                <td class="py-2 text-secondary">{{ $suratJalan->project->short_name ?? '-' }}</td>
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
                <div class="icon-tile">
                    <i class="bi bi-clock-history fs-5"></i>
                </div>
                <h5 class="fw-bold text-navy m-0">
                    {{ $user->hasRole('super_admin', 'admin') ? 'Log Aktivitas Terbaru' : 'Aktivitas Saya Terbaru' }}
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
                        <th class="ps-4 u-w-20pct">Waktu</th>
                        <th class="u-w-25pct">User</th>
                        <th class="u-w-30pct">Modul</th>
                        <th class="pe-4 u-w-25pct">Aksi</th>
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
                                    <div class="avatar-initial u-w-30px u-h-30px u-fs-p75rem">
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
