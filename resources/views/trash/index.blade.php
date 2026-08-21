@extends('layouts.app')

@section('title', 'Trash')

@section('content')
<div class="container-fluid px-4 py-3">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Trash</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Trash</li>
                </ol>
            </nav>
        </div>
    </div>

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

    <div id="trashAlertPlaceholder"></div>

    <div class="card shadow-sm mb-4 border-0 rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-funnel me-2"></i>Filter Trash</h6>
        </div>
        <div class="card-body bg-light bg-opacity-25">
            <form action="{{ route('trash.index') }}" method="GET">
                <div class="row g-3">

                    {{-- Search --}}
                    <div class="col-md-4">
                        <label for="search" class="form-label small fw-semibold text-muted">Cari Nama Data</label>
                        <input type="text" class="form-control form-control-sm text-dark small" id="search" name="search"
                               placeholder="Nama / judul data..." value="{{ request('search') }}">
                    </div>

                    {{-- Tipe Data --}}
                    <div class="col-md-4">
                        <label for="type" class="form-label small fw-semibold text-muted">Tipe Data</label>
                        <select class="form-select select-sm text-dark small" id="type" name="type">
                            <option value="">Semua Tipe</option>
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}" {{ request('type') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Dihapus Oleh --}}
                    <div class="col-md-4">
                        <label for="deleted_by" class="form-label small fw-semibold text-muted">Dihapus Oleh</label>
                        <select class="form-select select-sm text-dark small" id="deleted_by" name="deleted_by">
                            <option value="">Semua User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('deleted_by') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tanggal Dari --}}
                    <div class="col-md-6">
                        <label for="date_from" class="form-label small fw-semibold text-muted">Tanggal Dari</label>
                        <input type="date" class="form-control small" id="date_from" name="date_from" value="{{ request('date_from') }}">
                    </div>

                    {{-- Tanggal Sampai --}}
                    <div class="col-md-6">
                        <label for="date_to" class="form-label small fw-semibold text-muted">Tanggal Sampai</label>
                        <input type="date" class="form-control small" id="date_to" name="date_to" value="{{ request('date_to') }}">
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('trash.index') }}" class="btn btn-sm btn-outline-secondary px-3 fw-medium">Reset</a>
                    <button type="submit" class="btn btn-sm btn-primary px-3 fw-medium">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-trash me-2"></i>Data Terhapus</h6>
            <span class="badge bg-secondary text-white fw-medium rounded-pill px-3 py-1.5" style="font-size: 0.8rem;" id="trashTotalBadge">
                {{ $trashItems->total() }} Total Data
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-stack align-middle mb-0 text-nowrap">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3" style="width: 18%">Dihapus Pada</th>
                            <th style="width: 27%">Nama Data</th>
                            <th style="width: 12%">Tipe</th>
                            <th style="width: 20%">Dihapus Oleh</th>
                            <th class="pe-4 text-center" style="width: 23%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small text-dark" id="trashTableBody">
                        @forelse($trashItems as $item)
                            @include('trash.partials.row', ['item' => $item])
                        @empty
                            <tr id="trashEmptyRow">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-check2-circle fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    Trash kosong. Tidak ada data yang cocok dengan kriteria filter Anda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($trashItems->hasPages())
            <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
                {{ $trashItems->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>

{{-- Modal Konfirmasi Pulihkan --}}
<div class="modal fade" id="restoreTrashModal" tabindex="-1" aria-labelledby="restoreTrashModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="restoreTrashModalLabel">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Konfirmasi Pulihkan Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-dark fw-medium mb-3">Apakah Anda yakin ingin memulihkan data ini?</p>
                <div class="bg-light p-3 rounded-3 border">
                    <div class="mb-2">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">NAMA DATA:</small>
                        <span id="restore-item-name" class="fw-bold text-dark fs-6">-</span>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">TIPE:</small>
                        <span id="restore-item-type" class="fw-semibold text-secondary">-</span>
                    </div>
                </div>
                <small class="text-muted d-block mt-3">
                    <i class="bi bi-info-circle me-1"></i>Data akan dikembalikan seperti semula dan dapat diakses kembali.
                </small>
            </div>
            <div class="modal-footer bg-light border-top p-3">
                <button type="button" class="btn btn-secondary px-3 fw-medium" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="restoreTrashSubmit" class="btn btn-primary px-4 fw-medium shadow-sm">
                    <span class="btn-text">Ya, Pulihkan</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Hapus Permanen --}}
<div class="modal fade" id="forceDeleteTrashModal" tabindex="-1" aria-labelledby="forceDeleteTrashModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="forceDeleteTrashModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus Permanen
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-dark fw-medium mb-3">Apakah Anda yakin ingin menghapus data ini secara permanen?</p>
                <div class="bg-light p-3 rounded-3 border">
                    <div class="mb-2">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">NAMA DATA:</small>
                        <span id="force-delete-item-name" class="fw-bold text-dark fs-6">-</span>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">TIPE:</small>
                        <span id="force-delete-item-type" class="fw-semibold text-secondary">-</span>
                    </div>
                </div>
                <small class="text-danger d-block mt-3">
                    <i class="bi bi-info-circle me-1"></i>Data yang sudah dihapus permanen <strong>tidak dapat dipulihkan lagi</strong>.
                </small>
            </div>
            <div class="modal-footer bg-light border-top p-3">
                <button type="button" class="btn btn-secondary px-3 fw-medium" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="forceDeleteTrashSubmit" class="btn btn-danger px-4 fw-medium shadow-sm">
                    <span class="btn-text">Ya, Hapus Permanen</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const tableBody = document.getElementById('trashTableBody');
    const totalBadge = document.getElementById('trashTotalBadge');
    const alertPlaceholder = document.getElementById('trashAlertPlaceholder');

    const restoreModalEl = document.getElementById('restoreTrashModal');
    const restoreModal = new bootstrap.Modal(restoreModalEl);
    const forceDeleteModalEl = document.getElementById('forceDeleteTrashModal');
    const forceDeleteModal = new bootstrap.Modal(forceDeleteModalEl);

    let activeType = null;
    let activeId = null;
    let activeRow = null;

    function showAlert(type, message) {
        const alertEl = document.createElement('div');
        alertEl.className = `alert alert-${type} alert-dismissible fade show mb-4`;
        alertEl.role = 'alert';
        alertEl.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        alertPlaceholder.innerHTML = '';
        alertPlaceholder.appendChild(alertEl);
    }

    function decrementTotalBadge() {
        if (!totalBadge) return;
        const current = parseInt(totalBadge.textContent, 10) || 0;
        const next = Math.max(0, current - 1);
        totalBadge.textContent = `${next} Total Data`;
    }

    function removeRowAndCheckEmpty(row) {
        if (row) row.remove();

        if (!tableBody.querySelector('tr[data-trash-row]')) {
            tableBody.innerHTML = `
                <tr id="trashEmptyRow">
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-check2-circle fs-2 d-block mb-2 text-secondary opacity-50"></i>
                        Trash kosong. Tidak ada data yang cocok dengan kriteria filter Anda.
                    </td>
                </tr>`;
        }
    }

    // --- Buka modal Pulihkan ---
    restoreModalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        activeType = button.getAttribute('data-type');
        activeId = button.getAttribute('data-id');
        activeRow = button.closest('tr[data-trash-row]');

        document.getElementById('restore-item-name').textContent = button.getAttribute('data-name');
        document.getElementById('restore-item-type').textContent = button.getAttribute('data-type-label');
    });

    // --- Buka modal Hapus Permanen ---
    forceDeleteModalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        activeType = button.getAttribute('data-type');
        activeId = button.getAttribute('data-id');
        activeRow = button.closest('tr[data-trash-row]');

        document.getElementById('force-delete-item-name').textContent = button.getAttribute('data-name');
        document.getElementById('force-delete-item-type').textContent = button.getAttribute('data-type-label');
    });

    function submitAction(url, method, submitBtn, modal, successPrefix) {
        const btnText = submitBtn.querySelector('.btn-text');
        const spinner = submitBtn.querySelector('.spinner-border');

        submitBtn.disabled = true;
        btnText.classList.add('d-none');
        spinner.classList.remove('d-none');

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        })
            .then(async (response) => {
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Terjadi kesalahan, silakan coba lagi.');
                return data;
            })
            .then(function (data) {
                modal.hide();
                showAlert('success', data.message || successPrefix);
                removeRowAndCheckEmpty(activeRow);
                decrementTotalBadge();
                activeType = null;
                activeId = null;
                activeRow = null;
            })
            .catch(function (err) {
                modal.hide();
                showAlert('danger', err.message);
            })
            .finally(function () {
                submitBtn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
            });
    }

    document.getElementById('restoreTrashSubmit').addEventListener('click', function () {
        if (!activeType || !activeId) return;
        submitAction(
            `/trash/${activeType}/${activeId}/restore`,
            'PATCH',
            this,
            restoreModal,
            'Data berhasil dipulihkan.'
        );
    });

    document.getElementById('forceDeleteTrashSubmit').addEventListener('click', function () {
        if (!activeType || !activeId) return;
        submitAction(
            `/trash/${activeType}/${activeId}`,
            'DELETE',
            this,
            forceDeleteModal,
            'Data berhasil dihapus permanen.'
        );
    });
});
</script>
@endsection
