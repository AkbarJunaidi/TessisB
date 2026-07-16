@extends('layouts.app')

@section('title', 'Daftar Inventory')

@section('content')

<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Inventory List</h3>
            <p class="text-muted small m-0">Kelola dan pantau seluruh data aset barang fisik perusahaan.</p>
        </div>
        <div class="d-flex gap-2">
            <!-- FITUR BARU: Download All Report (Massal) -->
            <a href="{{ route('inventory.download-all') }}" class="btn btn-outline-danger d-flex align-items-center gap-2 shadow-sm fw-medium">
                <i class="bi bi-file-earmark-pdf-fill"></i> Download All Report
            </a>
            <!-- Tombol Tambah Barang Bawaan Proyek -->
            <a href="{{ route('inventory.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm fw-medium">
                <i class="bi bi-plus-circle"></i> Add New Inventory
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th scope="col" class="ps-4 py-3" style="width: 8%;">No</th>
                            <th scope="col" class="py-3" style="width: 15%;">Image</th>
                            <th scope="col" class="py-3" style="width: 35%;">Nama Barang</th>
                            <th scope="col" class="py-3" style="width: 25%;">Serial Number</th>
                            <th scope="col" class="py-3 text-center pe-4" style="width: 17%;">Action</th>
                        </tr>
                    </thead>
                    <tbody class="small text-dark">
                        @forelse($inventories as $index => $item)
                            <tr>
                                <td class="ps-4 py-3 fw-semibold text-secondary">
                                    {{ $inventories->firstItem() + $index }}
                                </td>
                                <td class="py-3">
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
                                <td class="py-3 fw-bold text-dark">
                                    {{ $item->name }}
                                </td>
                                <td class="py-3 text-secondary">
                                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace fw-medium">
                                        {{ $item->serial_number }}
                                    </span>
                                </td>
                                <td class="py-3 text-center pe-4">
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- Tombol View Bawaan -->
                                        <a href="{{ route('inventory.show', $item->id) }}"
                                           class="btn btn-sm btn-outline-primary px-2 fw-medium rounded-2 d-flex align-items-center gap-1"
                                           title="View Detail">
                                            <i class="bi bi-eye"></i> View
                                        </a>

                                        <!-- FITUR BARU: Preview Report (Membuka PDF di Tab Baru Browser) -->
                                        <a href="{{ route('inventory.preview', $item->id) }}"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-secondary px-2 fw-medium rounded-2 d-flex align-items-center gap-1"
                                           title="Preview PDF Report">
                                            {{-- <i class="bi bi-file-pdf"></i> Preview Report --}}
                                            <i class="bi bi-download text-success"></i>
                                        </a>

                                        <!-- Tombol Delete Bawaan -->
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
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted bg-white rounded-bottom">
                                    <i class="bi bi-box-seam text-secondary opacity-25 d-block mb-3" style="font-size: 3rem;"></i>
                                    <p class="mb-1 fw-bold text-dark">Belum Ada Data Inventory</p>
                                    <p class="text-muted small mb-0">Klik tombol "Add New Inventory" di atas untuk menambahkan barang pertama Anda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        {{ $inventories->links('pagination::bootstrap-5') }}
    </div>

</div>

<div class="modal fade" id="deleteInventoryModal" isset-modal tabindex="-1" aria-labelledby="deleteInventoryModalLabel" aria-hidden="true">
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
                            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.7rem;">NAMA BARANG:</small>
                            <span id="modal-inventory-name" class="fw-bold text-dark fs-6">-</span>
                        </div>
                        <div>
                            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.7rem;">SERIAL NUMBER:</small>
                            <span id="modal-inventory-sn" class="font-monospace fw-semibold text-secondary">-</span>
                        </div>
                    </div>

                    <small class="text-danger d-block mt-3">
                        <i class="bi bi-info-circle me-1"></i>Catatan: Data ini akan dipindahkan ke sistem arsip (Soft Delete).
                    </small>
                </div>

                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-secondary px-3 fw-medium" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 fw-medium shadow-sm">Delete</button>
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
