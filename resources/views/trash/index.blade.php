@extends('layouts.app')

@section('title', 'Sampah')

@section('content')
<div class="container-fluid px-4 py-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Sampah</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Sampah</li>
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
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-funnel me-2"></i>Filter Sampah</h6>
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
            <span class="badge bg-secondary text-white fw-medium rounded-pill px-3 py-1.5 u-fs-0p8rem" id="trashTotalBadge">
                {{ $trashItems->total() }} Total Data
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-stack align-middle mb-0 text-nowrap">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3 u-w-18pct">Dihapus Pada</th>
                            <th class="u-w-27pct">Nama Data</th>
                            <th class="u-w-12pct">Tipe</th>
                            <th class="u-w-20pct">Dihapus Oleh</th>
                            <th class="pe-4 text-center u-w-23pct">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small text-dark" id="trashTableBody">
                        @forelse($trashItems as $item)
                            @include('trash.partials.row', ['item' => $item])
                        @empty
                            <tr id="trashEmptyRow">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-check2-circle fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    Sampah kosong. Tidak ada data yang cocok dengan kriteria filter Anda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($trashItems->hasPages())
            <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
                {{ $trashItems->appends(request()->query())->links('pagination.app') }}
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
                        <small class="text-muted d-block text-uppercase fw-bold u-fs-0p7rem">NAMA DATA:</small>
                        <span id="restore-item-name" class="fw-bold text-dark fs-6">-</span>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-bold u-fs-0p7rem">TIPE:</small>
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
                        <small class="text-muted d-block text-uppercase fw-bold u-fs-0p7rem">NAMA DATA:</small>
                        <span id="force-delete-item-name" class="fw-bold text-dark fs-6">-</span>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-bold u-fs-0p7rem">TIPE:</small>
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

<script src="{{ \App\Support\AppAsset::url('js/trash/index.js') }}"></script>
@endsection
