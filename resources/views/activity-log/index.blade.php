@extends('layouts.app')

@section('title', 'Activity Logs Audit Trail')

@section('content')
<div class="container-fluid px-4 py-3">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Activity Logs</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Activity Logs</li>
                </ol>
            </nav>
        </div>

        @if(auth()->user()->isSuperAdmin())
            <button type="button" class="btn btn-sm btn-outline-danger fw-medium" data-bs-toggle="modal" data-bs-target="#deleteRangeModal">
                <i class="bi bi-trash3 me-1"></i>Hapus Rentang Tanggal
            </button>
        @endif
    </div>

    <div id="activityLogAlertPlaceholder"></div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4 border-0 rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-funnel me-2"></i>Filter Activity Logs</h6>
        </div>
        <div class="card-body bg-light bg-opacity-25">
            <form action="{{ route('activity-logs.index') }}" method="GET">
                <div class="row g-3">

                    {{-- Module --}}
                    <div class="col-md-4">
                        <label for="module" class="form-label small fw-semibold text-muted">Module</label>
                        <select class="form-select select-sm text-dark small" id="module" name="module">
                            <option value="">All Modules</option>
                            @foreach($modules as $value => $label)
                                <option value="{{ $value }}" {{ request('module') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- User --}}
                    <div class="col-md-4">
                        <label for="user_id" class="form-label small fw-semibold text-muted">User</label>
                        <select class="form-select select-sm text-dark small" id="user_id" name="user_id">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Action --}}
                    <div class="col-md-4">
                        <label for="action" class="form-label small fw-semibold text-muted">Action</label>
                        <select class="form-select select-sm text-dark small" id="action" name="action">
                            <option value="">All Actions</option>
                            @foreach($actions as $group => $items)
                                <optgroup label="{{ $group }}">
                                    @foreach($items as $act)
                                        <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>
                                            {{ $act }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="col-md-6">
                        <label for="date_from" class="form-label small fw-semibold text-muted">Date From</label>
                        <input type="date" class="form-control small" id="date_from" name="date_from" value="{{ request('date_from') }}">
                    </div>

                    {{-- Date To --}}
                    <div class="col-md-6">
                        <label for="date_to" class="form-label small fw-semibold text-muted">Date To</label>
                        <input type="date" class="form-control small" id="date_to" name="date_to" value="{{ request('date_to') }}">
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-outline-secondary px-3 fw-medium">Reset</a>
                    <button type="submit" class="btn btn-sm btn-primary px-3 fw-medium">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-list-ul me-2"></i>Data Audit Trail Logs</h6>
            <span class="badge bg-secondary text-white fw-medium rounded-pill px-3 py-1.5" style="font-size: 0.8rem;">
                {{ $logs->total() }} Total Logs
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-stack align-middle mb-0 text-nowrap">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3" style="width: 20%">Date Time</th>
                            <th style="width: 25%">User</th>
                            <th style="width: 30%">Module</th>
                            <th class="pe-4" style="width: 25%">Action</th>
                        </tr>
                    </thead>
                    <tbody class="small text-dark">
                        @forelse($logs as $log)
                            <tr>
                                <td class="ps-4 py-3 text-secondary fw-medium" data-label="Date Time">
                                    <i class="bi bi-calendar-event me-2"></i>{{ $log->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td data-label="User">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-semibold" style="width: 28px; height: 28px; font-size: 0.75rem; border: 1px solid #e2e8f0;">
                                            {{ strtoupper(substr($log->user->name ?? 'SY', 0, 2)) }}
                                        </div>
                                        <span class="fw-semibold">{{ $log->user->name ?? 'System / Deleted User' }}</span>
                                    </div>
                                </td>
                                <td data-label="Module">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-2 py-2 fw-medium" style="font-size: 0.8rem;">
                                        {{ class_basename($log->module) }}
                                    </span>
                                </td>
                                <td data-label="Action">
                                    @php $lowerAction = strtolower($log->action); @endphp
                                    @if(in_array($lowerAction, ['delete', 'deleted', 'logout']))
                                        <span class="text-danger fw-semibold"><i class="bi bi-circle-fill me-1 small" style="font-size: 0.5rem;"></i>{{ ucfirst($log->action) }}</span>
                                    @elseif(in_array($lowerAction, ['create', 'created', 'login', 'upload']))
                                        <span class="text-success fw-semibold"><i class="bi bi-circle-fill me-1 small" style="font-size: 0.5rem;"></i>{{ ucfirst($log->action) }}</span>
                                    @else
                                        <span class="text-warning fw-semibold"><i class="bi bi-circle-fill me-1 small" style="font-size: 0.5rem;"></i>{{ ucfirst($log->action) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    Tidak ditemukan catatan jejak log aktivitas yang cocok dengan kriteria filter Anda.
                                </td>
                            </tr>
                        @endforelse {{-- DIPERBAIKI: Mengubah @forelse menjadi @endforelse --}}
                    </tbody>
                </table>
            </div>
        </div>

        @if($logs->hasPages())
            <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
                {{ $logs->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>

@if(auth()->user()->isSuperAdmin())
    {{-- Modal Konfirmasi Hapus Activity Log Berdasarkan Rentang Tanggal --}}
    <div class="modal fade" id="deleteRangeModal" tabindex="-1" aria-labelledby="deleteRangeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="deleteRangeModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Hapus Activity Log Berdasarkan Rentang Tanggal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-dark fw-medium mb-3">Pilih rentang tanggal Activity Log yang ingin dihapus secara permanen.</p>

                    <div class="mb-3">
                        <label for="deleteRangeFrom" class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                        <input type="date" class="form-control" id="deleteRangeFrom" name="date_from">
                    </div>

                    <div class="mb-3">
                        <label for="deleteRangeTo" class="form-label small fw-semibold text-muted">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="deleteRangeTo" name="date_to">
                    </div>

                    <div id="deleteRangeError" class="text-danger small mb-2 d-none"></div>

                    <small class="text-danger d-block">
                        <i class="bi bi-info-circle me-1"></i>Seluruh Activity Log pada rentang tanggal ini akan dihapus permanen dan <strong>tidak dapat dipulihkan</strong>.
                    </small>
                </div>
                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-secondary px-3 fw-medium" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="deleteRangeSubmit" class="btn btn-danger px-4 fw-medium shadow-sm">
                        <span class="btn-text">Ya, Hapus</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const alertPlaceholder = document.getElementById('activityLogAlertPlaceholder');

        const deleteRangeModalEl = document.getElementById('deleteRangeModal');
        const deleteRangeModal = new bootstrap.Modal(deleteRangeModalEl);
        const errorBox = document.getElementById('deleteRangeError');

        function showAlert(type, message) {
            const alertEl = document.createElement('div');
            alertEl.className = `alert alert-${type} alert-dismissible fade show mb-4`;
            alertEl.role = 'alert';
            alertEl.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
            alertPlaceholder.innerHTML = '';
            alertPlaceholder.appendChild(alertEl);
        }

        function resetModalState() {
            document.getElementById('deleteRangeFrom').value = '';
            document.getElementById('deleteRangeTo').value = '';
            errorBox.classList.add('d-none');
            errorBox.textContent = '';
        }

        deleteRangeModalEl.addEventListener('show.bs.modal', resetModalState);

        document.getElementById('deleteRangeSubmit').addEventListener('click', function () {
            const submitBtn = this;
            const dateFrom = document.getElementById('deleteRangeFrom').value;
            const dateTo = document.getElementById('deleteRangeTo').value;

            errorBox.classList.add('d-none');
            errorBox.textContent = '';

            if (!dateFrom || !dateTo) {
                errorBox.textContent = 'Tanggal awal dan tanggal akhir wajib diisi.';
                errorBox.classList.remove('d-none');
                return;
            }

            if (dateTo < dateFrom) {
                errorBox.textContent = 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.';
                errorBox.classList.remove('d-none');
                return;
            }

            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner-border');

            submitBtn.disabled = true;
            btnText.classList.add('d-none');
            spinner.classList.remove('d-none');

            fetch(`{{ route('activity-logs.delete-range') }}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ date_from: dateFrom, date_to: dateTo }),
            })
                .then(async (response) => {
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Terjadi kesalahan, silakan coba lagi.');
                    return data;
                })
                .then(function (data) {
                    deleteRangeModal.hide();
                    showAlert('success', data.message);
                    setTimeout(() => window.location.reload(), 1200);
                })
                .catch(function (err) {
                    deleteRangeModal.hide();
                    showAlert('danger', err.message);
                })
                .finally(function () {
                    submitBtn.disabled = false;
                    btnText.classList.remove('d-none');
                    spinner.classList.add('d-none');
                });
        });
    });
    </script>
@endif
@endsection
