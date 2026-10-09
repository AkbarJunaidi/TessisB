@extends('layouts.app') {{-- Sesuai layout dashboard asli sistem Anda --}}

@section('content')
@php
    // Permission dihitung sekali di atas, dipakai berulang di tiap baris.
    $me          = auth()->user();
    $canDownload = $me->hasPermission('data_integration', 'download');
    $canRename   = $me->hasPermission('data_integration', 'rename');
    $canDelete   = $me->hasPermission('data_integration', 'delete');
    $canUpload   = $me->hasPermission('data_integration', 'upload');
    $canMkdir    = $me->hasPermission('data_integration', 'create_folder');
    $canLock     = $me->hasPermission('data_integration', 'lock');
    $isShared    = $tab === 'dibagikan';

    // Bangun URL dengan mempertahankan tab & filter lain; null = hapus parameter itu.
    $url = fn (array $override) => route('files.my-files', array_filter(
        array_merge(
            ['tab' => $isShared ? 'dibagikan' : null, 'jenis' => $jenis, 'sumber' => $sumber],
            $override
        ),
        fn ($value) => !is_null($value)
    ));

    $jenisLabel  = ['folder' => 'Folder', 'file' => 'File'];
    $sumberLabel = ['upload' => 'Diunggah', 'generate' => 'Digenerate'];
@endphp

<div class="container-fluid px-4 py-3">

    {{-- Judul + aksi utama --}}
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <div>
            <h4 class="fw-bold mb-1 text-dark">My Files</h4>
            <p class="text-muted small mb-0">
                @if($isShared)
                    Yang Anda unggah, generate, dan buat di ruang bersama &mdash; beserta lokasinya. Daftar ini khusus milik Anda.
                @else
                    Berkas pribadi Anda. Hanya Anda yang dapat melihatnya sampai dipindahkan ke folder bersama.
                @endif
            </p>
        </div>

        @unless($isShared)
            @if($canUpload || $canMkdir)
                <div class="dropdown">
                    <button class="btn btn-primary rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-plus-lg me-1"></i> New
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        @if($canMkdir)
                            <li>
                                <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#createPrivateFolderModal">
                                    <i class="bi bi-folder-plus text-secondary me-2"></i> Folder
                                </a>
                            </li>
                        @endif
                        @if($canUpload)
                            <li>
                                <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#uploadPrivateFileModal">
                                    <i class="bi bi-file-earmark-arrow-up text-secondary me-2"></i> Upload File
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
        @endunless
    </div>

    {{-- Tab --}}
    <ul class="nav drv-tabs gap-1 mb-3">
        <li class="nav-item">
            <a class="nav-link {{ !$isShared ? 'active' : '' }}" href="{{ route('files.my-files') }}">
                <i class="bi bi-lock me-1"></i> File saya
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $isShared ? 'active' : '' }}" href="{{ route('files.my-files', ['tab' => 'dibagikan']) }}">
                <i class="bi bi-folder-symlink me-1"></i> Dibagikan
            </a>
        </li>
    </ul>

    {{-- Breadcrumb folder pribadi (hanya saat berada di dalam sebuah folder) --}}
    @if(!$isShared && $currentFolder)
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('files.my-files') }}" class="text-decoration-none">My Files</a></li>
                @foreach($breadcrumb as $crumb)
                    @if($loop->last)
                        <li class="breadcrumb-item active" aria-current="page">{{ $crumb->name }}</li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ route('files.my-files', ['folder' => $crumb->id]) }}" class="text-decoration-none">{{ $crumb->name }}</a></li>
                    @endif
                @endforeach
            </ol>
        </nav>
    @endif

    {{-- Toolbar: pencarian (di halaman ini) + chip filter --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <div class="drv-search">
            <i class="bi bi-search"></i>
            <input type="search" id="drvSearch" class="form-control" placeholder="Cari di daftar ini" autocomplete="off" aria-label="Cari di daftar ini">
        </div>

        @if($isShared)
            {{-- Chip "Jenis" --}}
            <div class="dropdown">
                <button class="btn btn-outline-secondary drv-chip dropdown-toggle {{ $jenis ? 'is-active' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ $jenis ? $jenisLabel[$jenis] : 'Jenis' }}
                </button>
                <ul class="dropdown-menu shadow-sm">
                    <li><a class="dropdown-item small" href="{{ $url(['jenis' => null]) }}">Semua</a></li>
                    <li><a class="dropdown-item small" href="{{ $url(['jenis' => 'folder']) }}"><i class="bi bi-folder-fill me-2 text-secondary"></i>Folder</a></li>
                    <li><a class="dropdown-item small" href="{{ $url(['jenis' => 'file']) }}"><i class="bi bi-file-earmark-fill me-2 text-secondary"></i>File</a></li>
                </ul>
            </div>

            {{-- Chip "Sumber" (folder tidak punya sumber, jadi tersaring keluar saat dipakai) --}}
            <div class="dropdown">
                <button class="btn btn-outline-secondary drv-chip dropdown-toggle {{ $sumber ? 'is-active' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ $sumber ? $sumberLabel[$sumber] : 'Sumber' }}
                </button>
                <ul class="dropdown-menu shadow-sm">
                    <li><a class="dropdown-item small" href="{{ $url(['sumber' => null]) }}">Semua</a></li>
                    <li><a class="dropdown-item small" href="{{ $url(['sumber' => 'upload']) }}"><i class="bi bi-cloud-arrow-up me-2 text-secondary"></i>Diunggah</a></li>
                    <li><a class="dropdown-item small" href="{{ $url(['sumber' => 'generate']) }}"><i class="bi bi-file-earmark-pdf me-2 text-secondary"></i>Digenerate (PDF)</a></li>
                </ul>
            </div>

            @if($jenis || $sumber)
                <a href="{{ route('files.my-files', ['tab' => 'dibagikan']) }}" class="btn btn-link btn-sm text-decoration-none">
                    <i class="bi bi-x-circle me-1"></i>Reset filter
                </a>
            @endif
        @endif
    </div>

    <div class="table-responsive">
    @if(!$isShared)
        {{-- ===================== TAB: FILE SAYA (pribadi) ===================== --}}
        <table class="drv-list">
            <thead>
                <tr>
                    <th scope="col" class="ps-3">Nama</th>
                    <th scope="col" class="d-none d-md-table-cell">Ukuran</th>
                    <th scope="col" class="d-none d-md-table-cell">Dibuat</th>
                    <th scope="col" class="text-end pe-3 u-w-64px"><span class="visually-hidden">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                {{-- Folder pribadi lebih dulu (seperti Drive) --}}
                @foreach($folders as $folder)
                    <tr class="drv-row" data-drv-row data-search="{{ \Illuminate\Support\Str::lower($folder->name) }}">
                        <td class="ps-3">
                            <a href="{{ route('files.my-files', ['folder' => $folder->id]) }}" class="drv-name text-decoration-none text-body">
                                <i class="bi bi-folder-fill text-secondary drv-ico"></i>
                                <div class="min-w-0">
                                    <div class="drv-title">{{ $folder->name }}</div>
                                    <div class="drv-meta-mobile d-md-none">Folder &middot; {{ $folder->created_at->format('d M Y') }}</div>
                                </div>
                            </a>
                        </td>
                        <td class="d-none d-md-table-cell text-secondary small">-</td>
                        <td class="d-none d-md-table-cell text-secondary small">{{ $folder->created_at->format('d M Y H:i') }}</td>
                        <td class="text-end pe-3">
                            <div class="dropdown">
                                <button class="btn btn-link text-secondary p-1 m-0 border-0 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi folder">
                                    <i class="bi bi-three-dots-vertical fs-5"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><a class="dropdown-item small py-2" href="{{ route('files.my-files', ['folder' => $folder->id]) }}"><i class="bi bi-folder2-open me-2 text-muted"></i> Buka</a></li>
                                    @if($canRename)
                                        <li>
                                            <a class="dropdown-item small py-2" href="#"
                                               onclick="openRenameModal({{ \Illuminate\Support\Js::from(route('folders.rename', $folder->id)) }}, {{ \Illuminate\Support\Js::from($folder->name) }}, 'name', '', 'folder'); return false;">
                                                <i class="bi bi-pencil me-2 text-muted"></i> Rename
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item small py-2" href="#"
                                               onclick="openMoveModal({{ \Illuminate\Support\Js::from(route('folders.move', $folder->id)) }}, true, 'folder'); return false;">
                                                <i class="bi bi-folder-symlink me-2 text-muted"></i> Move
                                            </a>
                                        </li>
                                    @endif
                                    @if($canDelete)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item small py-2 text-danger" href="#"
                                               onclick="openDeleteModal({{ \Illuminate\Support\Js::from(route('folders.destroy', $folder->id)) }}, {{ \Illuminate\Support\Js::from($folder->name) }}, 'folder'); return false;">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @foreach($files as $file)
                    <tr class="drv-row" data-drv-row data-search="{{ \Illuminate\Support\Str::lower($file->file_name) }}">
                        <td class="ps-3">
                            <div class="drv-name">
                                <i class="bi {{ $file->icon_class }} drv-ico"></i>
                                <div class="min-w-0">
                                    <div class="drv-title">{{ $file->file_name }}</div>
                                    {{-- Mobile: info sekunder di bawah nama --}}
                                    <div class="drv-meta-mobile d-md-none">{{ $file->readable_size }} &middot; {{ $file->created_at->format('d M Y') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell text-secondary small">{{ $file->readable_size }}</td>
                        <td class="d-none d-md-table-cell text-secondary small">{{ $file->created_at->format('d M Y H:i') }}</td>
                        <td class="text-end pe-3">
                            <div class="dropdown">
                                <button class="btn btn-link text-secondary p-1 m-0 border-0 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi file">
                                    <i class="bi bi-three-dots-vertical fs-5"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    @if($canDownload && $file->preview_type)
                                        <li><a class="dropdown-item small py-2" href="{{ route('files.preview', $file) }}" target="_blank" rel="noopener"><i class="bi bi-eye me-2 text-muted"></i> Preview</a></li>
                                    @endif
                                    <li><a class="dropdown-item small py-2" href="{{ route('files.download', $file->id) }}"><i class="bi bi-download me-2 text-muted"></i> Download</a></li>
                                    @if($canRename)
                                        <li>
                                            <a class="dropdown-item small py-2" href="#"
                                               onclick="openRenameModal({{ \Illuminate\Support\Js::from(route('files.rename', $file->id)) }}, {{ \Illuminate\Support\Js::from($file->base_name) }}, 'file_name', {{ \Illuminate\Support\Js::from($file->locked_extension) }}, 'file'); return false;">
                                                <i class="bi bi-pencil me-2 text-muted"></i> Rename
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item small py-2" href="#"
                                               onclick="openMoveModal({{ \Illuminate\Support\Js::from(route('files.move', $file->id)) }}, true, 'file'); return false;">
                                                <i class="bi bi-folder-symlink me-2 text-muted"></i> Move
                                            </a>
                                        </li>
                                    @endif
                                    @if($canDelete)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item small py-2 text-danger" href="#"
                                               onclick="openDeleteModal({{ \Illuminate\Support\Js::from(route('files.destroy', $file->id)) }}, {{ \Illuminate\Support\Js::from($file->file_name) }}, 'file'); return false;">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @if($folders->isEmpty() && $files->isEmpty())
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-cloud-slash display-6 mb-2 d-block"></i>
                            @if($currentFolder)
                                Folder ini masih kosong.
                            @else
                                Belum ada folder atau file pribadi. Klik <strong>New</strong> untuk menambahkan.
                            @endif
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        {{-- ===================== TAB: DIBAGIKAN (aktivitas saya di ruang bersama) ===================== --}}
        <table class="drv-list">
            <thead>
                <tr>
                    <th scope="col" class="ps-3">Nama</th>
                    <th scope="col" class="d-none d-md-table-cell">Lokasi</th>
                    <th scope="col" class="d-none d-md-table-cell">Sumber</th>
                    <th scope="col" class="d-none d-md-table-cell">Tanggal</th>
                    <th scope="col" class="text-end pe-3 u-w-64px"><span class="visually-hidden">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @php $lastGroup = null; @endphp
                @forelse($items as $item)
                    @php
                        $model  = $item['model'];
                        $isFile = $item['kind'] === 'file';
                    @endphp

                    {{-- Judul kelompok tanggal --}}
                    @if($item['group'] !== $lastGroup)
                        <tr class="drv-group" data-drv-group><td colspan="5">{{ $item['group'] }}</td></tr>
                        @php $lastGroup = $item['group']; @endphp
                    @endif

                    <tr class="drv-row" data-drv-row data-search="{{ \Illuminate\Support\Str::lower($item['name'] . ' ' . $item['location']) }}">
                        <td class="ps-3">
                            <div class="drv-name">
                                @if($isFile)
                                    <i class="bi {{ $model->icon_class }} drv-ico"></i>
                                @else
                                    <i class="bi bi-folder-fill text-secondary drv-ico"></i>
                                @endif
                                <div class="min-w-0">
                                    <div class="drv-title">
                                        {{ $item['name'] }}
                                        @if($model->isLocked())
                                            <i class="bi bi-lock-fill text-warning ms-1"
                                               title="Dikunci oleh {{ $model->lockedBy->name ?? 'pengguna yang sudah dihapus' }} pada {{ $model->locked_at->format('d M Y H:i') }}"></i>
                                        @endif
                                    </div>
                                    {{-- Mobile: lokasi, sumber, dan tanggal di bawah nama --}}
                                    <div class="drv-meta-mobile d-md-none">
                                        <i class="bi bi-geo-alt"></i> {{ $item['location'] }}
                                        &middot; {{ $item['source_label'] }} &middot; {{ $item['date']->format('d M') }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            @if($item['location_url'])
                                <a href="{{ $item['location_url'] }}" class="drv-loc text-decoration-none" title="{{ $item['location'] }}">
                                    <i class="bi bi-folder2 me-1"></i>{{ $item['location'] }}
                                </a>
                            @else
                                <span class="drv-loc" title="{{ $item['location'] }}">{{ $item['location'] }}</span>
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell text-secondary small">{{ $item['source_label'] }}</td>
                        <td class="d-none d-md-table-cell text-secondary small">{{ $item['date']->format('d M Y H:i') }}</td>
                        <td class="text-end pe-3">
                            <div class="dropdown">
                                <button class="btn btn-link text-secondary p-1 m-0 border-0 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi">
                                    <i class="bi bi-three-dots-vertical fs-5"></i>
                                </button>
                                {{-- Tab ini hanya untuk melihat: tidak ada rename/pindah/hapus (hanya Kunci/Buka Kunci bagi pemegang hak `lock`) --}}
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    @if($isFile)
                                        @if($canDownload && $model->preview_type)
                                            <li><a class="dropdown-item small py-2" href="{{ route('files.preview', $model) }}" target="_blank" rel="noopener"><i class="bi bi-eye me-2 text-muted"></i> Preview</a></li>
                                        @endif
                                        <li><a class="dropdown-item small py-2" href="{{ route('files.download', $model->id) }}"><i class="bi bi-download me-2 text-muted"></i> Download</a></li>
                                        @if($item['location_url'])
                                            <li><a class="dropdown-item small py-2" href="{{ $item['location_url'] }}"><i class="bi bi-folder2-open me-2 text-muted"></i> Buka lokasi</a></li>
                                        @endif
                                    @else
                                        <li><a class="dropdown-item small py-2" href="{{ route('folders.show', $model) }}"><i class="bi bi-folder2-open me-2 text-muted"></i> Buka folder</a></li>
                                    @endif

                                    @if($canLock)
                                        <li><hr class="dropdown-divider"></li>
                                        @include('data-integration.partials.lock-toggle', ['item' => $model, 'kind' => $item['kind']])
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-folder-symlink display-6 mb-2 d-block"></i>
                            @if($jenis || $sumber)
                                Tidak ada yang cocok dengan filter ini.
                            @else
                                Belum ada file yang Anda unggah atau generate, maupun folder yang Anda buat di ruang bersama.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
    </div>

    {{-- Pesan saat pencarian tidak menemukan apa pun (diatur JS di bawah) --}}
    <div id="drvNoResult" class="text-center text-muted py-4 d-none">Tidak ada hasil untuk pencarian tersebut.</div>
</div>

{{-- Pencarian sisi-klien: menyaring baris yang sudah tampil, tanpa reload. --}}
<script src="{{ \App\Support\AppAsset::url('js/data-integration/my-files.js') }}"></script>

<div class="modal fade" id="createPrivateFolderModal" tabindex="-1" aria-labelledby="createPrivateFolderLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('folders.store') }}" method="POST" class="modal-content border-0 shadow">
            @csrf
            {{-- space=private: folder ini pribadi (hanya tampil di My Files pemiliknya) --}}
            <input type="hidden" name="space" value="private">
            <input type="hidden" name="parent_id" value="{{ $currentFolder->id ?? '' }}">
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold" id="createPrivateFolderLabel">New Folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="mb-3">
                    <label for="private_folder_name" class="form-label fw-medium text-secondary">Folder Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="private_folder_name" name="name"
                           value="{{ old('name') }}" required maxlength="255" placeholder="Masukkan nama folder...">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted mt-2 small">Folder ini bersifat pribadi dan hanya terlihat oleh Anda.</div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Submit</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="uploadPrivateFileModal" tabindex="-1" aria-labelledby="uploadPrivateFileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
            @csrf
            {{-- Diunggah dari My Files: masuk ke folder pribadi yang sedang dibuka (kosong = akar My Files) --}}
            <input type="hidden" name="folder_id" value="{{ $currentFolder->id ?? '' }}">
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

@include('data-integration.partials.item-action-modals', [
    'moveGroups'    => $moveGroups,
    'moveRootLabel' => 'My Files (tingkat atas)',
])

{{-- Buka kembali modal Folder bila validasi nama gagal (halaman dimuat ulang, modal tertutup). --}}
@if($errors->has('name') && old('space') === 'private')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('createPrivateFolderModal')).show();
        });
    </script>
@endif
@endsection
