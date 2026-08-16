@extends('layouts.app')

@section('title', 'Daftar Inventory')

@section('content')

<div class="container-fluid p-0">

    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Inventory List</h3>
            <p class="text-muted small m-0">Kelola dan pantau seluruh data aset barang fisik perusahaan.</p>
        </div>
        <div class="d-flex gap-2">
            <!-- FITUR: Download Semua Report (Massal) -->
            <a href="{{ route('inventory.download-all-pdf') }}" class="btn btn-outline-danger d-flex align-items-center gap-2 shadow-sm fw-medium">
                <i class="bi bi-file-earmark-pdf-fill"></i> Download Semua Report
            </a>
            @if(auth()->user()->hasPermission('inventory', 'create'))
            <!-- Tombol Tambah Inventory -->
            <a href="{{ route('inventory.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm fw-medium">
                <i class="bi bi-plus-circle"></i> Tambah Inventory
            </a>
            @endif
        </div>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Form Search & Filter Status -->
    <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
        <div class="card-body p-3">
            <form action="{{ route('inventory.index') }}" method="GET" class="row g-3 align-items-center">
                <!-- Search HANYA berdasarkan Nama Barang -->
                <div class="col-12 col-md-6 col-lg-7">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                               name="search"
                               class="form-control bg-light border-start-0 ps-0"
                               placeholder="Cari berdasarkan nama barang atau brand..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Dropdown Filter Status Barang -->
                <div class="col-12 col-md-4 col-lg-3">
                    <select name="status" class="form-select bg-light" onchange="this.form.submit()">
                        <option value="Semua Status" {{ request('status') == 'Semua Status' || !request('status') ? 'selected' : '' }}>
                            Semua Status
                        </option>
                        <option value="Tersedia" {{ request('status') == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                        <option value="Dipinjam" {{ request('status') == 'Dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                        <option value="Perbaikan" {{ request('status') == 'Perbaikan' ? 'selected' : '' }}>Perbaikan</option>
                        <option value="Rusak" {{ request('status') == 'Rusak' ? 'selected' : '' }}>Rusak</option>
                        <option value="Hilang" {{ request('status') == 'Hilang' ? 'selected' : '' }}>Hilang</option>
                    </select>
                </div>

                <!-- Action Button Filter & Reset -->
                <div class="col-12 col-md-2 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary w-100 fw-medium">
                        Filter
                    </button>
                    @if(request('search') || (request('status') && request('status') !== 'Semua Status'))
                        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Table Inventory List -->
    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th scope="col" class="ps-4 py-3" style="width: 10%;">Foto Barang</th>
                            <th scope="col" class="py-3" style="width: 25%;">Nama Barang</th>
                            <th scope="col" class="py-3" style="width: 20%;">Serial Number</th>
                            <th scope="col" class="py-3" style="width: 15%;">Status Barang</th>
                            <th scope="col" class="py-3" style="width: 15%;">Tanggal Input</th>
                            <th scope="col" class="py-3 text-center pe-4" style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small text-dark">
                        @forelse($inventories as $index => $item)
                            <tr>
                                <!-- Foto Barang -->
                                <td class="ps-4 py-3">
                                    @if($item->image)
                                        <img src="{{ asset('storage/' . $item->image) }}"
                                             alt="Foto {{ $item->name }}"
                                             class="img-thumbnail rounded shadow-sm"
                                             style="width: 60px; height: 50px; object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted border border-dashed shadow-sm"
                                             style="width: 60px; height: 50px; font-size: 0.75rem;">
                                            <i class="bi bi-image opacity-50 me-1"></i> No Pic
                                        </div>
                                    @endif
                                </td>

                                <!-- Nama Barang -->
                                <td class="py-3 fw-bold text-dark">
                                    {{ $item->name }}
                                </td>

                                <!-- Serial Number -->
                                <td class="py-3 text-secondary">
                                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace fw-medium">
                                        {{ $item->serial_number }}
                                    </span>
                                </td>

                                <!-- Status Barang (Badge Bootstrap) - otomatis "Tersedia" jika masih ada unit available -->
                                <td class="py-3">
                                    @switch($item->display_status)
                                        @case('Tersedia')
                                            <span class="badge bg-success px-2 py-1">Tersedia</span>
                                            @break
                                        @case('Dipinjam')
                                            <span class="badge bg-primary px-2 py-1">Dipinjam</span>
                                            @break
                                        @case('Perbaikan')
                                            <span class="badge bg-warning text-dark px-2 py-1">Perbaikan</span>
                                            @break
                                        @case('Rusak')
                                            <span class="badge bg-danger px-2 py-1">Rusak</span>
                                            @break
                                        @case('Hilang')
                                            <span class="badge bg-dark px-2 py-1">Hilang</span>
                                            @break
                                        @default
                                            <span class="badge bg-success px-2 py-1">{{ $item->status ?? 'Tersedia' }}</span>
                                    @endswitch
                                    <div class="small text-muted mt-1">{{ $item->qty_available }}/{{ $item->quantity_total }} unit tersedia</div>
                                </td>

                                <!-- Tanggal Input -->
                                <td class="py-3 text-secondary">
                                    {{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}
                                </td>

                                <!-- Action Buttons (View, Download Report, Delete HANYA) -->
                                <td class="py-3 text-center pe-4">
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- View Detail -->
                                        <a href="{{ route('inventory.show', $item->id) }}"
                                           class="btn btn-sm btn-outline-primary px-2 fw-medium rounded-2 d-flex align-items-center gap-1"
                                           title="View Detail">
                                            <i class="bi bi-eye"></i> View
                                        </a>

                                        <!-- Download Report (PDF Single) -->
                                        <a href="{{ route('inventory.download-pdf', $item->id) }}"
                                           class="btn btn-sm btn-outline-danger px-2 fw-medium rounded-2 d-flex align-items-center gap-1"
                                           title="Download Report PDF">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>

                                        <!-- Delete Item -->
                                        @if(auth()->user()->hasPermission('inventory', 'delete'))
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger px-2 fw-medium rounded-2 d-flex align-items-center gap-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteInventoryModal"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->name }}"
                                                data-sn="{{ $item->serial_number }}"
                                                title="Delete Item">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted bg-white rounded-bottom">
                                    <i class="bi bi-box-seam text-secondary opacity-25 d-block mb-3" style="font-size: 3rem;"></i>
                                    <p class="mb-1 fw-bold text-dark">Belum Ada Data Inventory</p>
                                    <p class="text-muted small mb-0">Klik tombol "Tambah Inventory" di atas untuk menambahkan barang pertama Anda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            Menampilkan {{ $inventories->firstItem() ?? 0 }} - {{ $inventories->lastItem() ?? 0 }} dari {{ $inventories->total() }} inventaris
        </div>
        <div>
            {{ $inventories->links('pagination::bootstrap-5') }}
        </div>
    </div>

</div>

<!-- Delete Modal Confirmation -->
<div class="modal fade" id="deleteInventoryModal" tabindex="-1" aria-labelledby="deleteInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
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
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">NAMA BARANG:</small>
                            <span id="modal-inventory-name" class="fw-bold text-dark fs-6">-</span>
                        </div>
                        <div>
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">SERIAL NUMBER:</small>
                            <span id="modal-inventory-sn" class="font-monospace fw-semibold text-secondary">-</span>
                        </div>
                    </div>

                    <small class="text-danger d-block mt-3">
                        <i class="bi bi-info-circle me-1"></i>Catatan: Data ini akan dipindahkan ke sistem arsip (Soft Delete).
                    </small>
                </div>

                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-secondary px-3 fw-medium" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4 fw-medium shadow-sm">Ya, Hapus</button>
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
