@extends('layouts.app')

@section('title', 'Daftar Inventory')

@section('content')

<div class="page-heading">
    <div>
        <h3>Inventory List</h3>
        <p>Kelola dan pantau seluruh data aset barang fisik perusahaan.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        {{-- FITUR: Download Semua Report (Massal) --}}
        <a href="{{ route('inventory.download-all-pdf') }}" class="btn btn-outline-danger d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-pdf-fill"></i> <span class="d-none d-sm-inline">Download Semua Report</span>
        </a>
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
                    <option value="Tersedia" {{ request('status') == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                    <option value="Dipinjam" {{ request('status') == 'Dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                    <option value="Perbaikan" {{ request('status') == 'Perbaikan' ? 'selected' : '' }}>Perbaikan</option>
                    <option value="Rusak" {{ request('status') == 'Rusak' ? 'selected' : '' }}>Rusak</option>
                    <option value="Hilang" {{ request('status') == 'Hilang' ? 'selected' : '' }}>Hilang</option>
                </select>
            </div>

            <div class="col-12 col-md-2 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
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
                    <th class="ps-4" style="width: 10%;">Foto</th>
                    <th style="width: 25%;">Nama Barang</th>
                    <th style="width: 20%;">Serial Number</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 15%;">Tanggal Input</th>
                    <th class="text-center pe-4" style="width: 15%;">Aksi</th>
                </tr>
            </thead>
            <tbody class="small">
                @forelse($inventories as $index => $item)
                    <tr>
                        <td class="ps-4 py-3" data-label="Foto">
                            @if($item->image)
                                <img src="{{ asset('storage/' . $item->image) }}"
                                     alt="Foto {{ $item->name }}"
                                     class="rounded-3 border"
                                     style="width: 56px; height: 46px; object-fit: cover;">
                            @else
                                <div class="bg-light rounded-3 d-flex align-items-center justify-content-center text-muted border"
                                     style="width: 56px; height: 46px; font-size: 0.7rem;">
                                    <i class="bi bi-image opacity-50"></i>
                                </div>
                            @endif
                        </td>

                        <td class="py-3 fw-bold text-dark" data-label="Nama Barang">{{ $item->name }}</td>

                        <td class="py-3 text-secondary" data-label="Serial Number">
                            <span class="badge bg-light text-dark border px-2 py-1 font-monospace fw-medium">
                                {{ $item->serial_number }}
                            </span>
                        </td>

                        {{-- Status Barang - otomatis "Tersedia" jika masih ada unit available (logika tidak diubah) --}}
                        <td class="py-3" data-label="Status">
                            @switch($item->display_status)
                                @case('Tersedia')
                                    <span class="badge-soft-success">Tersedia</span>
                                    @break
                                @case('Dipinjam')
                                    <span class="badge-soft-primary">Dipinjam</span>
                                    @break
                                @case('Perbaikan')
                                    <span class="badge-soft-warning">Perbaikan</span>
                                    @break
                                @case('Rusak')
                                    <span class="badge-soft-danger">Rusak</span>
                                    @break
                                @case('Hilang')
                                    <span class="badge-soft-secondary">Hilang</span>
                                    @break
                                @default
                                    <span class="badge-soft-success">{{ $item->status ?? 'Tersedia' }}</span>
                            @endswitch
                            <div class="small text-muted mt-1">{{ $item->qty_available }}/{{ $item->quantity_total }} unit tersedia</div>
                        </td>

                        <td class="py-3 text-secondary" data-label="Tanggal Input">{{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}</td>

                        <td class="py-3 text-center pe-4 cell-block" data-label="Aksi">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('inventory.show', $item->id) }}"
                                   class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                   title="Lihat Detail">
                                    <i class="bi bi-eye"></i> <span class="d-none d-xl-inline">View</span>
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
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Nama Barang</small>
                            <span id="modal-inventory-name" class="fw-bold text-dark fs-6">-</span>
                        </div>
                        <div>
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Serial Number</small>
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

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const deleteModal = document.getElementById('deleteInventoryModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const sn = button.getAttribute('data-sn');

                document.getElementById('modal-inventory-name').textContent = name;
                document.getElementById('modal-inventory-sn').textContent = sn;
                document.getElementById('deleteInventoryForm').action = `/inventory/${id}`;
            });
        }
    });
</script>
@endsection
