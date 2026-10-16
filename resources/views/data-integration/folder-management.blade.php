@extends('layouts.app') {{-- Sesuai layout dashboard yang sudah Anda buat --}}

@section('content')
@php
    // Permission dihitung sekali, dipakai di tiap baris.
    $canRename = auth()->user()->hasPermission('data_integration', 'rename');
    $canDelete = auth()->user()->hasPermission('data_integration', 'delete');
    $canLock   = auth()->user()->hasPermission('data_integration', 'lock');

    // Folder yang sedang dibuka terkunci (atau berada di folder terkunci): semua isinya terlindungi.
    $parentProtected = !empty($currentLock);
@endphp
<div class="container-fluid px-4 py-3">
    {{-- Kesalahan validasi (mis. nama mengandung karakter terlarang); pesan session tampil dari layout. --}}
    @if($errors->any() && old('space') !== 'private')
        <div class="alert alert-danger small py-2">{{ $errors->first() }}</div>
    @endif

    @if($parentProtected)
        <div class="alert alert-warning small py-2 d-flex align-items-start">
            <i class="bi bi-lock-fill me-2 mt-1"></i>
            <div>
                @if($currentLock['source'] === 'self')
                    Folder ini terkunci
                @else
                    Folder ini berada di folder terkunci "{{ $currentLock['folder_name'] }}"
                @endif
                @if($currentLock['by'])
                    (oleh {{ $currentLock['by'] }})
                @endif
                : isi yang sudah ada tidak dapat diubah nama, dipindah, atau dihapus. Upload file baru tetap diperbolehkan.
            </div>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Kelola Folder</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    @if(isset($current_folder) && $current_folder)
                        <li class="breadcrumb-item"><a href="{{ route('folders.index') }}" class="text-decoration-none">Kelola Folder</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $current_folder->name }}</li>
                    @else
                        <li class="breadcrumb-item active" aria-current="page">Kelola Folder</li>
                    @endif
                </ol>
            </nav>
        </div>

        <div class="dropdown">
            <button class="btn btn-primary d-flex align-items-center gap-2 fw-medium dropdown-toggle" type="button" id="btnNewDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-plus-lg"></i> Baru
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="btnNewDropdown">
                <li>
                    <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#createFolderModal">
                        <i class="bi bi-folder-plus text-muted me-2"></i> Folder
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                        <i class="bi bi-file-earmark-arrow-up text-muted me-2"></i> Unggah File
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="app-panel overflow-hidden">
        <div class="px-3 px-md-4 pt-3 pt-md-4 mb-3">
            <h6 class="fw-bold m-0">Isi Folder</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col" class="ps-4 py-3 u-w-40pct">Nama</th>
                        <th scope="col" class="d-none d-md-table-cell u-w-12pct">Tipe</th>
                        <th scope="col" class="d-none d-md-table-cell u-w-18pct">Pemilik</th>
                        <th scope="col" class="d-none d-md-table-cell u-w-18pct">Dibuat</th>
                        <th scope="col" class="pe-4 u-w-64px text-center u-w-12pct">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Tombol kembali bila sedang berada di dalam sub-folder --}}
                    @if(isset($current_folder) && $current_folder)
                        <tr>
                            <td colspan="5" class="ps-4 py-3">
                                <a href="{{ $current_folder->parent_id ? route('folders.show', $current_folder->parent_id) : route('folders.index') }}" class="text-decoration-none text-secondary">
                                    <i class="bi bi-arrow-90deg-up me-2"></i> <span class="fw-medium">.. (Kembali ke folder induk)</span>
                                </a>
                            </td>
                        </tr>
                    @endif

                    @if($folders->isEmpty() && $files->isEmpty())
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-folder2-open display-6 mb-2 d-block"></i>
                                Folder ini masih kosong.
                            </td>
                        </tr>
                    @endif

                    @foreach($folders as $folder)
                        @php
                            $isSystem        = in_array($folder->id, $systemFolderIds, true);
                            $ownLocked       = $folder->isLocked();
                            $protected       = $ownLocked || $parentProtected;
                            $hasLockedInside = in_array($folder->id, $lockedContentIds, true);
                            $ownerName       = $folder->user->name ?? 'System';
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('folders.show', $folder->id) }}" class="drv-name text-decoration-none text-body">
                                    <i class="bi bi-folder-fill text-primary drv-ico"></i>
                                    <div>
                                        <div class="drv-title">
                                            {{ $folder->name }}
                                            @if($isSystem)
                                                <span class="badge-soft-secondary px-2 py-1 rounded-pill small fw-normal ms-1">Sistem</span>
                                            @endif
                                            @if($ownLocked)
                                                <i class="bi bi-lock-fill text-warning ms-1"
                                                   title="Dikunci oleh {{ $folder->lockedBy->name ?? 'pengguna yang sudah dihapus' }} pada {{ $folder->locked_at->translatedFormat('d M Y H:i') }}"></i>
                                            @endif
                                        </div>
                                        <div class="drv-meta-mobile d-md-none">Folder &middot; {{ $ownerName }} &middot; {{ $folder->created_at->translatedFormat('d M Y') }}</div>
                                    </div>
                                </a>
                            </td>
                            <td class="d-none d-md-table-cell text-secondary small">Folder</td>
                            <td class="d-none d-md-table-cell text-secondary small">{{ $ownerName }}</td>
                            <td class="d-none d-md-table-cell text-secondary small text-nowrap">{{ $folder->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="pe-4 text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light text-secondary" type="button" data-row-menu data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi folder">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li><a class="dropdown-item small py-2" href="{{ route('folders.show', $folder->id) }}"><i class="bi bi-folder2-open me-2 text-muted"></i> Buka</a></li>

                                        {{-- Folder sistem (Document Center project dan induknya) tidak dapat diubah/dipindah/dihapus --}}
                                        @unless($isSystem)
                                            @if($protected)
                                                <li><span class="dropdown-item-text small text-muted"><i class="bi bi-lock-fill me-2"></i> Terkunci</span></li>
                                            @else
                                                @if($canRename)
                                                    <li>
                                                        <a class="dropdown-item small py-2" href="#"
                                                           onclick="openRenameModal({{ \Illuminate\Support\Js::from(route('folders.rename', $folder->id)) }}, {{ \Illuminate\Support\Js::from($folder->name) }}, 'name', '', 'folder'); return false;">
                                                            <i class="bi bi-pencil me-2 text-muted"></i> Ganti Nama
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item small py-2" href="#"
                                                           onclick="openMoveModal({{ \Illuminate\Support\Js::from(route('folders.move', $folder->id)) }}, true, 'folder'); return false;">
                                                            <i class="bi bi-folder-symlink me-2 text-muted"></i> Pindahkan
                                                        </a>
                                                    </li>
                                                @endif
                                            @endif
                                        @endunless

                                        @if($canLock)
                                            @include('data-integration.partials.lock-toggle', ['item' => $folder, 'kind' => 'folder'])
                                        @endif

                                        @unless($isSystem || $protected)
                                            @if($canDelete)
                                                <li><hr class="dropdown-divider"></li>
                                                @if($hasLockedInside)
                                                    <li><span class="dropdown-item-text small text-muted"><i class="bi bi-lock me-2"></i> Berisi item terkunci</span></li>
                                                @else
                                                    <li>
                                                        <a class="dropdown-item small py-2 text-danger" href="#"
                                                           onclick="openDeleteModal({{ \Illuminate\Support\Js::from(route('folders.destroy', $folder->id)) }}, {{ \Illuminate\Support\Js::from($folder->name) }}, 'folder'); return false;">
                                                            <i class="bi bi-trash me-2"></i> Hapus
                                                        </a>
                                                    </li>
                                                @endif
                                            @endif
                                        @endunless
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    @foreach($files as $file)
                        @php
                            $fileLocked    = $file->isLocked();
                            $fileProtected = $fileLocked || $parentProtected;
                            $ownerName     = $file->user->name ?? 'System';
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="drv-name">
                                    <i class="bi {{ $file->icon_class }} drv-ico"></i>
                                    <div>
                                        <div class="drv-title">
                                            {{ $file->file_name }}
                                            @if($fileLocked)
                                                <i class="bi bi-lock-fill text-warning ms-1"
                                                   title="Dikunci oleh {{ $file->lockedBy->name ?? 'pengguna yang sudah dihapus' }} pada {{ $file->locked_at->translatedFormat('d M Y H:i') }}"></i>
                                            @endif
                                        </div>
                                        <div class="drv-meta-mobile d-md-none">{{ strtoupper($file->file_type) }} &middot; {{ $ownerName }} &middot; {{ $file->created_at->translatedFormat('d M Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell"><span class="badge-soft-secondary px-2 py-1 rounded-pill small text-uppercase">{{ $file->file_type }}</span></td>
                            <td class="d-none d-md-table-cell text-secondary small">{{ $ownerName }}</td>
                            <td class="d-none d-md-table-cell text-secondary small text-nowrap">{{ $file->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="pe-4 text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light text-secondary" type="button" data-row-menu data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi file">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        @if($file->preview_type && auth()->user()->hasPermission('data_integration', 'download'))
                                            <li><a class="dropdown-item small py-2" href="{{ route('files.preview', $file) }}" target="_blank" rel="noopener"><i class="bi bi-eye me-2 text-muted"></i> Pratinjau</a></li>
                                        @endif
                                        <li><a class="dropdown-item small py-2" href="{{ route('files.download', $file->id) }}"><i class="bi bi-download me-2 text-muted"></i> Unduh</a></li>

                                        @if($fileProtected)
                                            <li><span class="dropdown-item-text small text-muted"><i class="bi bi-lock-fill me-2"></i> Terkunci</span></li>
                                        @elseif($canRename)
                                            <li>
                                                <a class="dropdown-item small py-2" href="#"
                                                   onclick="openRenameModal({{ \Illuminate\Support\Js::from(route('files.rename', $file->id)) }}, {{ \Illuminate\Support\Js::from($file->base_name) }}, 'file_name', {{ \Illuminate\Support\Js::from($file->locked_extension) }}, 'file'); return false;">
                                                    <i class="bi bi-pencil me-2 text-muted"></i> Ganti Nama
                                                </a>
                                            </li>
                                            <li>
                                                {{-- File bersama wajib berada di sebuah folder: opsi "tingkat atas" disembunyikan --}}
                                                <a class="dropdown-item small py-2" href="#"
                                                   onclick="openMoveModal({{ \Illuminate\Support\Js::from(route('files.move', $file->id)) }}, false, 'file'); return false;">
                                                    <i class="bi bi-folder-symlink me-2 text-muted"></i> Pindahkan
                                                </a>
                                            </li>
                                        @endif

                                        @if($canLock)
                                            @include('data-integration.partials.lock-toggle', ['item' => $file, 'kind' => 'file'])
                                        @endif

                                        @if($canDelete && !$fileProtected)
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item small py-2 text-danger" href="#"
                                                   onclick="openDeleteModal({{ \Illuminate\Support\Js::from(route('files.destroy', $file->id)) }}, {{ \Illuminate\Support\Js::from($file->file_name) }}, 'file'); return false;">
                                                    <i class="bi bi-trash me-2"></i> Hapus
                                                </a>
                                            </li>
                                        @endif
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

<div class="modal fade" id="createFolderModal" tabindex="-1" aria-labelledby="createFolderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('folders.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="parent_id" value="{{ $current_folder->id ?? '' }}">
            <div class="modal-header">
                <h5 class="modal-title" id="createFolderModalLabel">Folder Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="folder_name" class="form-label fw-medium text-secondary">Nama Folder</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="folder_name" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Masukkan nama folder...">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="uploadFileModal" tabindex="-1" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" class="modal-content" data-ajax data-ajax-progress>
            @csrf
            <input type="hidden" name="folder_id" value="{{ $current_folder->id ?? '' }}">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadFileModalLabel">Unggah File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="choose_file" class="form-label fw-medium text-secondary">Pilih File</label>
                    <input class="form-control @error('file') is-invalid @enderror" type="file" id="choose_file" name="file" required>
                    <div class="form-text text-muted mt-2 small">
                        Ekstensi diizinkan: {{ implode(', ', \App\Models\AppSetting::allowedExtensions()) }} (Maks. {{ \App\Models\AppSetting::int('upload_max_mb') }}MB)
                    </div>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="progress ajax-progress d-none mt-3" role="progressbar" aria-label="Progres unggah" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Unggah</button>
            </div>
        </form>
    </div>
</div>
@include('data-integration.partials.item-action-modals', [
    'moveGroups'    => $moveGroups,
    'moveRootLabel' => 'Root (tingkat atas)',
])
@endsection
