@extends('layouts.app') {{-- Sesuai layout dashboard asli sistem Anda --}}

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">My Files</h4>
            <p class="text-muted small mb-0">Tempat penyimpanan berkas pribadi Anda</p>
        </div>

        <button class="btn btn-primary px-3" type="button" data-bs-toggle="modal" data-bs-target="#uploadPrivateFileModal">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Upload File
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th scope="col" class="ps-4 py-3" style="width: 45%;">File Name</th>
                            <th scope="col" class="py-3">Type</th>
                            <th scope="col" class="py-3">Size</th>
                            <th scope="col" class="py-3">Created At</th>
                            <th scope="col" class="pe-4 py-3 text-end" style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($files->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-cloud-slash display-6 mb-2 d-block"></i>
                                    Anda belum mengunggah file pribadi apa pun.
                                </td>
                            </tr>
                        @endif

                        @foreach($files as $file)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        {{-- Visual Pembeda: Ikon gembok menandakan file bersifat private --}}
                                        <i class="bi bi-file-earmark-lock2-fill text-success fs-4 me-3"></i>
                                        <span class="fw-medium text-truncate">{{ $file->file_name }}</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary text-uppercase small" style="font-size: 0.75rem;">{{ $file->file_type }}</span></td>
                                <td>
                                    <span class="text-secondary small">
                                        {{-- IMPLEMENTASI AKSESOR: Menggunakan property readable_size dari model File.php --}}
                                        {{ $file->readable_size }}
                                    </span>
                                </td>
                                <td><span class="text-secondary small">{{ $file->created_at->format('Y-m-d H:i') }}</span></td>
                                <td class="pe-4 text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-link text-secondary p-1 m-0 border-0 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item small py-2" href="{{ route('files.download', $file->id) }}"><i class="bi bi-download me-2 text-muted"></i> Download</a></li>
                                            <li>
                                                <a class="dropdown-item small py-2" href="#" onclick="openRenameModal('{{ route('files.rename', $file->id) }}', '{{ $file->file_name }}', 'file_name')">
                                                    <i class="bi bi-pencil me-2 text-muted"></i> Rename
                                                </a>
                                            </li>
                                            <li>
                                                {{-- FIXES: Menambahkan route files.move ke dalam argument agar ditangkap sempurna oleh JavaScript --}}
                                                <a class="dropdown-item small py-2" href="#" onclick="openMoveModal('{{ route('files.move', $file->id) }}')">
                                                    <i class="bi bi-file-symlink me-2 text-muted"></i> Move to Shared Space
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item small py-2 text-danger" href="#" onclick="openDeleteModal('{{ route('files.destroy', $file->id) }}', '{{ $file->file_name }}')">
                                                    <i class="bi bi-trash me-2"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadPrivateFileModal" tabindex="-1" aria-labelledby="uploadPrivateFileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
            @csrf
            {{-- Karena diupload dari My Files, folder_id dikosongkan secara mutlak --}}
            <input type="hidden" name="folder_id" value="">
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold" id="uploadPrivateFileModalLabel">Upload Private File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="mb-3">
                    <label for="choose_private_file" class="form-label fw-medium text-secondary">Choose File</label>
                    <input class="form-control @error('file') is-invalid @enderror" type="file" id="choose_private_file" name="file" required>
                    <div class="form-text text-muted mt-2 small">
                        Berkas ini hanya akan tampil di ruang penyimpanan pribadi Anda.
                    </div>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Submit</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="dynamicRenameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="dynamicRenameForm" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('PATCH')
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold">Rename File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="mb-3">
                    <label class="form-label fw-medium text-secondary">File Name</label>
                    <input type="text" class="form-control" id="dynamicRenameInput" name="" required>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="dynamicMoveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="dynamicMoveForm" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('PATCH')
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold">Move to Shared Space</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="mb-3">
                    <label for="target_folder_id" class="form-label fw-medium text-secondary">Select Target Folder</label>
                    <select class="form-select" id="target_folder_id" name="target_folder_id" required>
                        <option value="" disabled selected>-- Pilih Folder Tujuan --</option>
                        @foreach(\App\Models\Folder::whereNull('deleted_at')->get() as $targetOption)
                            <option value="{{ $targetOption->id }}">
                                📂 {{ $targetOption->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text text-muted mt-2 small">
                        Memindahkan file pribadi ke folder bersama akan membuat file tersebut dapat dilihat oleh seluruh rekan kerja yang memiliki akses.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Move</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="dynamicDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="dynamicDeleteForm" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('DELETE')
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold text-danger">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-3">Apakah Anda yakin ingin menghapus file pribadi ini?</p>
                <div class="p-2 bg-light rounded border text-truncate">
                    <strong class="text-secondary small">Nama File: </strong>
                    <span id="dynamicDeleteNameText" class="fw-medium text-dark"></span>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger px-4">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
    /**
     * Menampilkan modal ganti nama file
     */
    function openRenameModal(actionUrl, currentName, inputFieldName) {
        const form = document.getElementById('dynamicRenameForm');
        const input = document.getElementById('dynamicRenameInput');
        
        form.action = actionUrl;
        input.name = inputFieldName;
        input.value = currentName;
        
        const modal = new bootstrap.Modal(document.getElementById('dynamicRenameModal'));
        modal.show();
    }

    /**
     * Menampilkan modal pemindahan file ke ruang bersama (Folder Management)
     */
    function openMoveModal(actionUrl) {
        const form = document.getElementById('dynamicMoveForm');
        form.action = actionUrl;
        
        const modal = new bootstrap.Modal(document.getElementById('dynamicMoveModal'));
        modal.show();
    }

    /**
     * Menampilkan modal konfirmasi hapus file
     */
    function openDeleteModal(actionUrl, itemName) {
        document.getElementById('dynamicDeleteForm').action = actionUrl;
        document.getElementById('dynamicDeleteNameText').innerText = itemName;
        
        const modal = new bootstrap.Modal(document.getElementById('dynamicDeleteModal'));
        modal.show();
    }
</script>
@endsection