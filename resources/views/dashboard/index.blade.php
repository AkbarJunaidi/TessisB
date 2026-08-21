@extends('layouts.app')

@section('title', 'Dashboard Utama')

@section('content')

    <div class="page-heading">
        <div>
            <h3>Dashboard Overview</h3>
            <p>Selamat datang, {{ Auth::user()->name }} berikut ringkasan sistem hari ini.</p>
        </div>
    </div>

    {{-- Bento grid statistik --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-5 g-3 g-md-4 mb-4">

        <div class="col">
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem; letter-spacing:.06em;">Inventory</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_inventory'] }}</h3>
                    </div>
                    <div class="icon-tile" style="background: rgba(13,132,252,.12); color: var(--c-primary);">
                        <i class="bi bi-box-seam fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem; letter-spacing:.06em;">Project</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_project'] }}</h3>
                    </div>
                    <div class="icon-tile" style="background: rgba(25,135,84,.12); color: #198754;">
                        <i class="bi bi-kanban fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
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
        </div>

        <div class="col">
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
        </div>

        <div class="col">
            <div class="stat-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem; letter-spacing:.06em;">Users</span>
                        <h3 class="fw-bolder text-navy mt-2 mb-0">{{ $statistics['total_user'] }}</h3>
                    </div>
                    <div class="icon-tile" style="background: rgba(11,36,71,.1); color: var(--c-navy);">
                        <i class="bi bi-people fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="app-panel overflow-hidden">
        <div class="app-panel-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-tile" style="background: rgba(13,132,252,.12); color: var(--c-primary);">
                    <i class="bi bi-clock-history fs-5"></i>
                </div>
                <h5 class="fw-bold text-navy m-0">Recent Activity Log</h5>
            </div>
            {{-- Kondisi disamakan dengan helper hasRole() yang dipakai di sidebar,
                 karena kolom `role` disimpan snake_case ('super_admin','admin'), bukan 'Super Admin'. --}}
            @if(auth()->user()->hasRole('super_admin', 'admin'))
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
                            <td class="ps-4 py-3 fw-medium text-secondary">
                                <i class="bi bi-calendar-event me-2 text-muted"></i>{{ $activity->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-initial" style="width:30px;height:30px;font-size:.75rem;">
                                        {{ strtoupper(substr($activity->user->name ?? 'SY', 0, 2)) }}
                                    </div>
                                    <span class="fw-semibold text-dark">{{ $activity->user->name ?? 'System / Deleted User' }}</span>
                                </div>
                            </td>
                            <td class="py-3">
                                <span class="badge-soft-secondary">{{ $activity->module }}</span>
                            </td>
                            <td class="py-3 pe-4">
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
