@extends('layouts.app') {{-- Sesuai layout dashboard yang sudah Anda buat --}}

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('folders.index') }}" class="text-decoration-none">Root</a></li>
                @if(isset($current_folder) && $current_folder)
                    <li class="breadcrumb-item active" aria-current="page">{{ $current_folder->name }}</li>
                @endif
            </ol>
        </nav>

        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle px-3" type="button" id="btnNewDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-plus-lg me-1"></i> New
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="btnNewDropdown">
                <li>
                    <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#createFolderModal">
                        <i class="bi bi-folder-plus text-warning me-2"></i> Folder
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                        <i class="bi bi-file-earmark-arrow-up text-info me-2"></i> Upload File
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th scope="col" class="ps-4 py-3" style="width: 40%;">Name</th>
                            <th scope="col" class="py-3">Type</th>
                            <th scope="col" class="py-3">Owner</th>
                            <th scope="col" class="py-3">Created At</th>
                            <th scope="col" class="pe-4 py-3 text-end" style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Tampilkan Tombol Kembali Jika Sedang Berada di dalam Sub-Folder --}}
                        @if(isset($current_folder) && $current_folder)
                            <tr>
                                <td colspan="5" class="ps-4 py-3">
                                    <a href="{{ $current_folder->parent_id ? route('folders.show', $current_folder->parent_id) : route('folders.index') }}" class="text-decoration-none text-secondary">
                                        <i class="bi bi-arrow-90deg-up me-2"></i> <span class="fw-medium">.. (Kembali ke folder induk)</span>
                                    </a>
                                </td>
                            </tr>
                        @endif

                        {{-- Kosong State --}}
                        @if($folders->isEmpty() && $files->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-folder2-open display-6 mb-2 d-block"></i>
                                    Folder ini masih kosong.
                                </td>
                            </tr>
                        @endif

                        {{-- Iterasi Render Folder --}}
                        @foreach($folders as $folder)
                            <tr>
                                <td class="ps-4 py-3">
                                    <a href="{{ route('folders.show', $folder->id) }}" class="text-decoration-none text-dark fw-medium d-flex align-items-center">
                                        <i class="bi bi-folder-fill text-warning fs-4 me-3"></i>
                                        <span class="text-truncate">{{ $folder->name }}</span>
                                    </a>
                                </td>
                                <td><span class="text-muted small">Folder</span></td>
                                <td><span class="badge bg-light text-dark border">{{ $folder->user->name ?? 'System' }}</span></td>
                                <td><span class="text-secondary small">{{ $folder->created_at->format('Y-m-d H:i') }}</span></td>
                                <td class="pe-4 text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-link text-secondary p-1 m-0 border-0 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item small py-2" href="#"><i class="bi bi-pencil me-2 text-muted"></i> Rename</a></li>
                                            <li><a class="dropdown-item small py-2" href="#"><i class="bi bi-folder-symlink me-2 text-muted"></i> Move</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item small py-2 text-danger" href="#"><i class="bi bi-trash me-2"></i> Delete</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        {{-- Iterasi Render Berkas Berbagi --}}
                        @foreach($files as $file)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-earmark-text-fill text-primary fs-4 me-3"></i>
                                        <span class="fw-medium text-truncate">{{ $file->file_name }}</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary text-uppercase small" style="font-size: 0.75rem;">{{ $file->file_type }}</span></td>
                                <td><span class="badge bg-light text-dark border">{{ $file->user->name ?? 'System' }}</span></td>
                                <td><span class="text-secondary small">{{ $file->created_at->format('Y-m-d H:i') }}</span></td>
                                <td class="pe-4 text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-link text-secondary p-1 m-0 border-0 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item small py-2" href="{{ route('files.download', $file->id) }}"><i class="bi bi-download me-2 text-muted"></i> Download</a></li>
                                            <li><a class="dropdown-item small py-2" href="#"><i class="bi bi-pencil me-2 text-muted"></i> Rename</a></li>
                                            <li><a class="dropdown-item small py-2" href="#"><i class="bi bi-file-symlink me-2 text-muted"></i> Move</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item small py-2 text-danger" href="#"><i class="bi bi-trash me-2"></i> Delete</a></li>
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

<div class="modal fade" id="createFolderModal" tabindex="-1" aria-labelledby="createFolderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('folders.store') }}" method="POST" class="modal-content border-0 shadow">
            @csrf
            <input type="hidden" name="parent_id" value="{{ $current_folder->id ?? '' }}">
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold" id="createFolderModalLabel">New Folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="mb-3">
                    <label for="folder_name" class="form-label fw-medium text-secondary">Folder Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="folder_name" name="name" required placeholder="Masukkan nama folder...">
                    @error('name')
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

<div class="modal fade" id="uploadFileModal" tabindex="-1" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
            @csrf
            <input type="hidden" name="folder_id" value="{{ $current_folder->id ?? '' }}">
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold" id="uploadFileModalLabel">Upload File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="mb-3">
                    <label for="choose_file" class="form-label fw-medium text-secondary">Choose File</label>
                    <input class="form-control @error('file') is-invalid @enderror" type="file" id="choose_file" name="file" required>
                    <div class="form-text text-muted mt-2 small">
                        Ekstensi diizinkan: pdf, doc, docx, xls, xlsx, jpg, jpeg, png (Maks. 10MB)
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
@endsection
