@extends('layouts.app')

@section('title', 'Dashboard Utama')

@section('content')
    <style>
        .hover-elevate {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .hover-elevate:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
        }
        .table-custom-header th {
            letter-spacing: 0.5px;
            font-weight: 600;
        }
    </style>

    <div class="container-fluid p-0">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3">
            <div>
                <h3 class="fw-bold text-dark m-0" style="letter-spacing: -0.5px;">Dashboard Overview</h3>
                <p class="text-muted small m-0 mt-1">Selamat datang di Pusat Navigasi Informasi Manajemen Sistem.</p>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-5 g-4 mb-5">

            <div class="col">
                <div class="card h-100 shadow-sm border-0 bg-white rounded-4 hover-elevate" style="border-left: 5px solid #0d6efd !important;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Inventory</span>
                                <h3 class="fw-bolder text-dark mt-2 mb-0">{{ $statistics['total_inventory'] }}</h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-box-seam fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm border-0 bg-white rounded-4 hover-elevate" style="border-left: 5px solid #198754 !important;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Project</span>
                                <h3 class="fw-bolder text-dark mt-2 mb-0">{{ $statistics['total_project'] }}</h3>
                            </div>
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-kanban fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm border-0 bg-white rounded-4 hover-elevate" style="border-left: 5px solid #ffc107 !important;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Task</span>
                                <h3 class="fw-bolder text-dark mt-2 mb-0">{{ $statistics['total_task'] }}</h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-list-task fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm border-0 bg-white rounded-4 hover-elevate" style="border-left: 5px solid #0dcaf0 !important;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Files</span>
                                <h3 class="fw-bolder text-dark mt-2 mb-0">{{ $statistics['total_files'] }}</h3>
                            </div>
                            <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-file-earmark-arrow-up fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm border-0 bg-white rounded-4 hover-elevate" style="border-left: 5px solid #6c757d !important;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Users</span>
                                <h3 class="fw-bolder text-dark mt-2 mb-0">{{ $statistics['total_user'] }}</h3>
                            </div>
                            <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-people fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded p-2 d-flex align-items-center justify-content-center">
                                <i class="bi bi-clock-history fs-5"></i>
                            </div>
                            <h5 class="card-title fw-bold text-dark m-0">Recent Activity Log</h5>
                        </div>
                        @if(in_array(auth()->user()->role, ['Super Admin', 'Admin']))
                            <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 small fw-medium">
                                <i class="bi bi-eye me-1"></i>View All
                            </a>
                        @endif
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive ap-table-stack">
                            <table class="table table-hover align-middle table-nowrap mb-0">
                                <thead class="table-light text-muted small text-uppercase table-custom-header">
                                    <tr>
                                        <th scope="col" class="ps-4 py-3 border-bottom-0" style="width: 20%;">Date Time</th>
                                        <th scope="col" class="py-3 border-bottom-0" style="width: 25%;">User</th>
                                        <th scope="col" class="py-3 border-bottom-0" style="width: 30%;">Module</th>
                                        <th scope="col" class="py-3 pe-4 border-bottom-0" style="width: 25%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="small text-dark border-top-0">
                                    @forelse($recentActivities as $activity)
                                        <tr>
                                            <td class="ps-4 py-3 fw-medium text-secondary" data-label="Date Time">
                                                <i class="bi bi-calendar-event me-2 text-muted"></i>{{ $activity->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="py-3" data-label="User">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center fw-bold text-primary shadow-sm" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                        {{ strtoupper(substr($activity->user->name ?? 'SY', 0, 2)) }}
                                                    </div>
                                                    <span class="fw-semibold text-dark">{{ $activity->user->name ?? 'System / Deleted User' }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3" data-label="Module">
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 fw-semibold rounded-pill" style="font-size: 0.75rem; letter-spacing: 0.3px;">
                                                    {{ $activity->module }}
                                                </span>
                                            </td>
                                            <td class="py-3 pe-4" data-label="Action">
                                                @if(in_array($activity->action, ['Delete', 'Logout']))
                                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 fw-semibold rounded-pill" style="font-size: 0.75rem; letter-spacing: 0.3px;">{{ $activity->action }}</span>
                                                @elseif(in_array($activity->action, ['Create', 'Login', 'Upload']))
                                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 fw-semibold rounded-pill" style="font-size: 0.75rem; letter-spacing: 0.3px;">{{ $activity->action }}</span>
                                                @else
                                                    <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 fw-semibold rounded-pill" style="font-size: 0.75rem; letter-spacing: 0.3px;">{{ $activity->action }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <div class="d-flex flex-column align-items-center justify-content-center">
                                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                                        <i class="bi bi-inbox text-secondary fs-3"></i>
                                                    </div>
                                                    <span class="fw-medium">Belum ada rekaman aktivitas terbaru saat ini.</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
