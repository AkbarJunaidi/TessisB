@extends('layouts.app')

@section('title', 'Daftar Barang')

@section('content')

<div class="page-heading">
    <div>
        <h3>Daftar Barang</h3>
        <p>Kelola dan pantau seluruh data aset barang fisik perusahaan.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        {{-- FITUR: Scan Barcode - izin SENDIRI (scan_barang.view), tidak
             ikut permission 'inventory' - lihat InventoryController::scanLookup(). --}}
        @if(auth()->user()->hasPermission('scan_barang', 'view'))
        <a href="{{ route('scan.index') }}" id="btnScanBarcode" class="btn btn-outline-primary d-flex align-items-center gap-2">
            <i class="bi bi-upc-scan"></i> <span class="d-none d-sm-inline">Scan Barcode</span>
        </a>
        @endif
        {{-- FITUR: Proses Laporan Massal (Semua Inventaris) bertahap via AJAX, notifikasi saat siap --}}
        <button
            type="button"
            id="btnGenerateAllReport"
            class="btn btn-outline-danger d-flex align-items-center gap-2"
        >
            <i class="bi bi-file-earmark-pdf-fill"></i> <span class="d-none d-sm-inline">Proses Laporan Semua Inventory</span>
        </button>
        <a href="{{ route('inventory.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle"></i> Tambah Inventory
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 rounded-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Form Search & Filter Status --}}
<div class="app-panel mb-4">
    <div class="p-3 p-md-4">
        <form action="{{ route('inventory.index') }}" method="GET" class="row g-3 align-items-center">
            <div class="col-12 col-md-6 col-lg-7">
                <label for="search" class="visually-hidden">Cari inventory</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="search" name="search"
                           class="form-control bg-light border-start-0 ps-0"
                           placeholder="Cari berdasarkan nama barang atau brand..."
                           value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-12 col-md-4 col-lg-3">
                <label for="status" class="visually-hidden">Filter status</label>
                <select name="status" id="status" class="form-select bg-light" onchange="this.form.submit()">
                    <option value="Semua Status" {{ request('status') == 'Semua Status' || !request('status') ? 'selected' : '' }}>Semua Status</option>
                    @foreach(\App\Support\InventoryStatus::all() as $statusName)
                        <option value="{{ $statusName }}" {{ request('status') == $statusName ? 'selected' : '' }}>{{ $statusName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-2 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                @if(request('search') || (request('status') && request('status') !== 'Semua Status'))
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary" title="Reset Filter" aria-label="Reset filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Table Inventory List --}}
<div class="app-panel overflow-hidden">
    <div class="px-3 px-md-4 pt-3 pt-md-4">
        <h6 class="fw-bold mb-3">Semua Barang ({{ $inventories->total() }})</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-modern table-stack align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4 u-w-10pct">Foto</th>
                    <th class="u-w-25pct">Nama Barang</th>
                    <th class="u-w-20pct">No. Seri</th>
                    <th class="u-w-15pct">Status</th>
                    <th class="u-w-15pct">Tanggal Input</th>
                    <th class="text-center pe-4 u-w-15pct">Aksi</th>
                </tr>
            </thead>
            <tbody class="small">
                @forelse($inventories as $index => $item)
                    <tr>
                        <td class="ps-4 py-3" data-label="Foto">
                            @if($item->image)
                                <img src="{{ asset('storage/' . $item->image) }}"
                                     alt="Foto {{ $item->name }}"
                                     class="rounded-3 border u-w-56px u-h-46px u-of-cover"
                                    >
                            @else
                                <div class="bg-light rounded-3 d-flex align-items-center justify-content-center text-muted border u-w-56px u-h-46px u-fs-0p7rem"
                                    >
                                    <i class="bi bi-image opacity-50"></i>
                                </div>
                            @endif
                        </td>

                        <td class="py-3 fw-bold text-dark" data-label="Nama Barang">{{ $item->name }}</td>

                        <td class="py-3 text-secondary" data-label="No. Seri">
                            <span class="badge bg-light text-dark border px-2 py-1 font-monospace fw-medium">
                                {{ $item->serial_number }}
                            </span>
                        </td>

                        {{-- Status Barang - otomatis "Tersedia" jika masih ada unit available (logika tidak diubah) --}}
                        <td class="py-3" data-label="Status">
                            <span class="{{ \App\Support\InventoryStatus::softClass($item->display_status) }}">{{ $item->display_status ?? 'Tersedia' }}</span>
                            <div class="small text-muted mt-1">{{ $item->qty_available }}/{{ $item->quantity_total }} unit tersedia</div>
                            @if($item->punyaJadwalServis())
                                @php $servisCounts = $item->servisCounts(); @endphp
                                @if($servisCounts['terlambat'] > 0)
                                    <div class="mt-1"><span class="badge-soft-danger">Servis jatuh tempo: {{ $servisCounts['terlambat'] }} unit</span></div>
                                @elseif($servisCounts['segera'] > 0)
                                    <div class="mt-1"><span class="badge-soft-warning">Servis segera: {{ $servisCounts['segera'] }} unit</span></div>
                                @endif
                            @endif
                        </td>

                        <td class="py-3 text-secondary" data-label="Tanggal Input">{{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}</td>

                        <td class="py-3 text-center pe-4 cell-block" data-label="Aksi">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('inventory.show', $item->id) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Lihat Detail" aria-label="Lihat detail {{ $item->name }}">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('inventory.download-pdf', $item->id) }}"
                                   class="btn btn-sm btn-outline-danger"
                                   title="Download Report PDF" aria-label="Download report PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteInventoryModal"
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->name }}"
                                        data-sn="{{ $item->serial_number }}"
                                        title="Hapus" aria-label="Hapus item">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-box-seam"></i></div>
                                <p class="mb-1 fw-bold text-dark">Belum Ada Data Inventory</p>
                                <p class="text-muted small mb-0">Klik tombol "Tambah Inventory" di atas untuk menambahkan barang pertama Anda.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
    <div class="text-muted small">
        Menampilkan {{ $inventories->firstItem() ?? 0 }} - {{ $inventories->lastItem() ?? 0 }} dari {{ $inventories->total() }} inventaris
    </div>
    <div>
        {{ $inventories->links('pagination::bootstrap-5') }}
    </div>
</div>

{{-- Delete Modal Confirmation --}}
<div class="modal fade" id="deleteInventoryModal" tabindex="-1" aria-labelledby="deleteInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="deleteInventoryModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="deleteInventoryForm" method="POST">
                @csrf
                @method('DELETE')

                <div class="modal-body p-4">
                    <p class="text-dark fw-medium mb-3">Apakah Anda yakin ingin menghapus data inventory ini?</p>

                    <div class="bg-light p-3 rounded-3 border">
                        <div class="mb-2">
                            <small class="text-muted d-block text-uppercase fw-bold u-fs-0p7rem">Nama Barang</small>
                            <span id="modal-inventory-name" class="fw-bold text-dark fs-6">-</span>
                        </div>
                        <div>
                            <small class="text-muted d-block text-uppercase fw-bold u-fs-0p7rem">No. Seri</small>
                            <span id="modal-inventory-sn" class="font-monospace fw-semibold text-secondary">-</span>
                        </div>
                    </div>

                    <small class="text-danger d-block mt-3">
                        <i class="bi bi-info-circle me-1"></i>Catatan: Data ini akan dipindahkan ke sistem arsip (Soft Delete).
                    </small>
                </div>

                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ \App\Support\AppAsset::url('js/inventory/index-1.js') }}"></script>

{{-- Modal progres Laporan Massal - lihat InventoryService::processAllReportBatch --}}
<div class="modal fade" id="generateReportModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h6 class="modal-title fw-bold">Memproses Laporan Semua Inventory</h6>
            </div>

            <div class="modal-body">
                <div class="progress mb-2 u-h-20px">
                    <div
                        id="generateReportProgressBar"
                        class="progress-bar progress-bar-striped progress-bar-animated"
                        role="progressbar"
                        style="width: 0%"
                    >0%</div>
                </div>
                <div id="generateReportStatusText" class="small text-muted">Memulai...</div>
            </div>

            <div class="modal-footer">
                <a
                    id="generateReportDownloadBtn"
                    href="#"
                    class="btn btn-primary d-none"
                >
                    <i class="bi bi-download me-1"></i> Unduh Laporan
                </a>
                <button
                    type="button"
                    id="generateReportCancelBtn"
                    class="btn btn-secondary"
                >Batal</button>
                <button
                    type="button"
                    id="generateReportCloseBtn"
                    class="btn btn-secondary d-none"
                    data-bs-dismiss="modal"
                >Tutup</button>
            </div>

        </div>
    </div>
</div>

<script src="{{ \App\Support\AppAsset::url('js/inventory/index-2.js') }}"></script>

@endsection
