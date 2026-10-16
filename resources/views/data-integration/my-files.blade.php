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
@endphp

<div class="container-fluid px-4 py-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" data-ajax-region="header">
        <div>
            <h3 class="fw-bold text-dark m-0">File Saya</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    @if(!$isShared && $currentFolder)
                        <li class="breadcrumb-item"><a href="{{ route('files.my-files') }}" class="text-decoration-none">File Saya</a></li>
                        @foreach($breadcrumb as $crumb)
                            @if($loop->last)
                                <li class="breadcrumb-item active" aria-current="page">{{ $crumb->name }}</li>
                            @else
                                <li class="breadcrumb-item"><a href="{{ route('files.my-files', ['folder' => $crumb->id]) }}" class="text-decoration-none">{{ $crumb->name }}</a></li>
                            @endif
                        @endforeach
                    @else
                        <li class="breadcrumb-item active" aria-current="page">File Saya</li>
                    @endif
                </ol>
            </nav>
        </div>

        @unless($isShared)
            @if($canUpload || $canMkdir)
                <div class="dropdown">
                    <button class="btn btn-primary d-flex align-items-center gap-2 fw-medium dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-plus-lg"></i> Baru
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        @if($canMkdir)
                            <li>
                                <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#createPrivateFolderModal">
                                    <i class="bi bi-folder-plus text-muted me-2"></i> Folder
                                </a>
                            </li>
                        @endif
                        @if($canUpload)
                            <li>
                                <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#uploadPrivateFileModal">
                                    <i class="bi bi-file-earmark-arrow-up text-muted me-2"></i> Unggah File
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
        @endunless
    </div>

    {{-- Tab di luar panel, sama seperti halaman Project; pindah tab lewat AJAX (ajax-tabs.js) --}}
    <ul class="nav nav-tabs mb-3" data-ajax-region="tabs">
        <li class="nav-item">
            <a class="nav-link {{ !$isShared ? 'active' : '' }}" href="{{ route('files.my-files') }}" data-ajax-tab data-no-progress @if(!$isShared) aria-current="page" @endif>File saya</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $isShared ? 'active' : '' }}" href="{{ route('files.my-files', ['tab' => 'dibagikan']) }}" data-ajax-tab data-no-progress @if($isShared) aria-current="page" @endif>Dibagikan</a>
        </li>
    </ul>

    <div data-ajax-region="body">
        {{-- Filter: pencarian langsung di daftar yang tampil; Jenis dan Sumber hanya di tab Dibagikan --}}
        <div class="card filter-card mb-4">
            <div class="card-body">
                <form action="{{ route('files.my-files') }}" method="GET" class="row g-3 align-items-end">
                    @if($isShared)
                        <input type="hidden" name="tab" value="dibagikan">
                    @endif
                    <div class="col-12 col-md">
                        <label for="drvSearch" class="form-label">Cari</label>
                        <div class="search-field">
                            <i class="bi bi-search"></i>
                            <input type="search" id="drvSearch" class="form-control" placeholder="Cari di daftar ini" autocomplete="off" aria-label="Cari di daftar ini">
                        </div>
                    </div>
                    @if($isShared)
                        <div class="col-6 col-md-3">
                            <label for="filterJenis" class="form-label">Jenis</label>
                            <select class="form-select" id="filterJenis" name="jenis">
                                <option value="">Semua jenis</option>
                                <option value="folder" {{ $jenis === 'folder' ? 'selected' : '' }}>Folder</option>
                                <option value="file" {{ $jenis === 'file' ? 'selected' : '' }}>File</option>
                            </select>
                        </div>
                        {{-- Folder tidak punya sumber, jadi tersaring keluar saat Sumber dipakai --}}
                        <div class="col-6 col-md-3">
                            <label for="filterSumber" class="form-label">Sumber</label>
                            <select class="form-select" id="filterSumber" name="sumber">
                                <option value="">Semua sumber</option>
                                <option value="upload" {{ $sumber === 'upload' ? 'selected' : '' }}>Diunggah</option>
                                <option value="generate" {{ $sumber === 'generate' ? 'selected' : '' }}>Digenerate (PDF)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-auto filter-actions">
                            <a href="{{ route('files.my-files', ['tab' => 'dibagikan']) }}" class="btn btn-outline-secondary px-3">Reset</a>
                            <button type="submit" class="btn btn-primary px-3"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <div class="app-panel overflow-hidden">
            <div class="px-3 px-md-4 pt-3 pt-md-4 mb-3">
                <div>
                    <h6 class="fw-bold m-0">{{ $isShared ? 'Dibagikan' : ($currentFolder->name ?? 'File Saya') }}</h6>
                    <div class="text-muted small d-none d-md-block">
                        {{ $isShared ? 'Yang Anda unggah, generate, dan buat di ruang bersama.' : 'Berkas pribadi, hanya Anda yang bisa melihat.' }}
                    </div>
                </div>
            </div>

            <div class="table-responsive">
            @if(!$isShared)
                {{-- ===================== TAB: FILE SAYA (pribadi) ===================== --}}
                <table class="table table-hover table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="ps-4 py-3 u-w-50pct">Nama</th>
                            <th scope="col" class="d-none d-md-table-cell u-w-14pct">Ukuran</th>
                            <th scope="col" class="d-none d-md-table-cell u-w-22pct">Dibuat</th>
                            <th scope="col" class="pe-4 u-w-64px text-center u-w-14pct">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Folder pribadi lebih dulu (seperti Drive) --}}
                        @foreach($folders as $folder)
                            <tr class="drv-row" data-drv-row data-search="{{ \Illuminate\Support\Str::lower($folder->name) }}">
                                <td class="ps-4">
                                    <a href="{{ route('files.my-files', ['folder' => $folder->id]) }}" class="drv-name text-decoration-none text-body">
                                        <i class="bi bi-folder-fill text-primary drv-ico"></i>
                                        <div class="min-w-0">
                                            <div class="drv-title">{{ $folder->name }}</div>
                                            <div class="drv-meta-mobile d-md-none">Folder &middot; {{ $folder->created_at->translatedFormat('d M Y') }}</div>
                                        </div>
                                    </a>
                                </td>
                                <td class="d-none d-md-table-cell text-secondary small text-nowrap">-</td>
                                <td class="d-none d-md-table-cell text-secondary small text-nowrap">{{ $folder->created_at->translatedFormat('d M Y H:i') }}</td>
                                <td class="pe-4 text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light text-secondary" type="button" data-row-menu data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi folder">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item small py-2" href="{{ route('files.my-files', ['folder' => $folder->id]) }}"><i class="bi bi-folder2-open me-2 text-muted"></i> Buka</a></li>
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
                                            @if($canDelete)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item small py-2 text-danger" href="#"
                                                       onclick="openDeleteModal({{ \Illuminate\Support\Js::from(route('folders.destroy', $folder->id)) }}, {{ \Illuminate\Support\Js::from($folder->name) }}, 'folder'); return false;">
                                                        <i class="bi bi-trash me-2"></i> Hapus
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
                                <td class="ps-4">
                                    <div class="drv-name">
                                        <i class="bi {{ $file->icon_class }} drv-ico"></i>
                                        <div class="min-w-0">
                                            <div class="drv-title">{{ $file->file_name }}</div>
                                            {{-- Mobile: info sekunder di bawah nama --}}
                                            <div class="drv-meta-mobile d-md-none">{{ $file->readable_size }} &middot; {{ $file->created_at->translatedFormat('d M Y') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell text-secondary small text-nowrap">{{ $file->readable_size }}</td>
                                <td class="d-none d-md-table-cell text-secondary small text-nowrap">{{ $file->created_at->translatedFormat('d M Y H:i') }}</td>
                                <td class="pe-4 text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light text-secondary" type="button" data-row-menu data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi file">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            @if($canDownload && $file->preview_type)
                                                <li><a class="dropdown-item small py-2" href="{{ route('files.preview', $file) }}" target="_blank" rel="noopener"><i class="bi bi-eye me-2 text-muted"></i> Pratinjau</a></li>
                                            @endif
                                            <li><a class="dropdown-item small py-2" href="{{ route('files.download', $file->id) }}"><i class="bi bi-download me-2 text-muted"></i> Unduh</a></li>
                                            @if($canRename)
                                                <li>
                                                    <a class="dropdown-item small py-2" href="#"
                                                       onclick="openRenameModal({{ \Illuminate\Support\Js::from(route('files.rename', $file->id)) }}, {{ \Illuminate\Support\Js::from($file->base_name) }}, 'file_name', {{ \Illuminate\Support\Js::from($file->locked_extension) }}, 'file'); return false;">
                                                        <i class="bi bi-pencil me-2 text-muted"></i> Ganti Nama
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item small py-2" href="#"
                                                       onclick="openMoveModal({{ \Illuminate\Support\Js::from(route('files.move', $file->id)) }}, true, 'file'); return false;">
                                                        <i class="bi bi-folder-symlink me-2 text-muted"></i> Pindahkan
                                                    </a>
                                                </li>
                                            @endif
                                            @if($canDelete)
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

                        @if($folders->isEmpty() && $files->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-cloud-slash display-6 mb-2 d-block"></i>
                                    @if($currentFolder)
                                        Folder ini masih kosong.
                                    @else
                                        Belum ada folder atau file pribadi. Klik <strong>Baru</strong> untuk menambahkan.
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            @else
                {{-- ===================== TAB: DIBAGIKAN (aktivitas saya di ruang bersama) ===================== --}}
                <table class="table table-hover table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="ps-4 py-3 u-w-38pct">Nama</th>
                            <th scope="col" class="d-none d-md-table-cell u-w-20pct">Lokasi</th>
                            <th scope="col" class="d-none d-md-table-cell u-w-14pct">Sumber</th>
                            <th scope="col" class="d-none d-md-table-cell u-w-18pct">Tanggal</th>
                            <th scope="col" class="pe-4 u-w-64px text-center u-w-10pct">Aksi</th>
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
                                <td class="ps-4">
                                    <div class="drv-name">
                                        @if($isFile)
                                            <i class="bi {{ $model->icon_class }} drv-ico"></i>
                                        @else
                                            <i class="bi bi-folder-fill text-primary drv-ico"></i>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="drv-title">
                                                {{ $item['name'] }}
                                                @if($model->isLocked())
                                                    <i class="bi bi-lock-fill text-warning ms-1"
                                                       title="Dikunci oleh {{ $model->lockedBy->name ?? 'pengguna yang sudah dihapus' }} pada {{ $model->locked_at->translatedFormat('d M Y H:i') }}"></i>
                                                @endif
                                            </div>
                                            {{-- Mobile: lokasi, sumber, dan tanggal di bawah nama --}}
                                            <div class="drv-meta-mobile d-md-none">
                                                <i class="bi bi-geo-alt"></i> {{ $item['location'] }}
                                                &middot; {{ $item['source_label'] }} &middot; {{ $item['date']->translatedFormat('d M') }}
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
                                <td class="d-none d-md-table-cell text-secondary small text-nowrap">{{ $item['date']->translatedFormat('d M Y H:i') }}</td>
                                <td class="pe-4 text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light text-secondary" type="button" data-row-menu data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        {{-- Tab ini hanya untuk melihat: tidak ada rename/pindah/hapus (hanya Kunci/Buka Kunci bagi pemegang hak `lock`) --}}
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            @if($isFile)
                                                @if($canDownload && $model->preview_type)
                                                    <li><a class="dropdown-item small py-2" href="{{ route('files.preview', $model) }}" target="_blank" rel="noopener"><i class="bi bi-eye me-2 text-muted"></i> Pratinjau</a></li>
                                                @endif
                                                <li><a class="dropdown-item small py-2" href="{{ route('files.download', $model->id) }}"><i class="bi bi-download me-2 text-muted"></i> Unduh</a></li>
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

        <div class="modal fade" id="createPrivateFolderModal" tabindex="-1" aria-labelledby="createPrivateFolderLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('folders.store') }}" method="POST" class="modal-content">
                    @csrf
                    {{-- space=private: folder ini pribadi (hanya tampil di My Files pemiliknya) --}}
                    <input type="hidden" name="space" value="private">
                    <input type="hidden" name="parent_id" value="{{ $currentFolder->id ?? '' }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createPrivateFolderLabel">Folder Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="private_folder_name" class="form-label fw-medium text-secondary">Nama Folder</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="private_folder_name" name="name"
                                   value="{{ old('name') }}" required maxlength="255" placeholder="Masukkan nama folder...">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted mt-2 small">Folder ini bersifat pribadi dan hanya terlihat oleh Anda.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="uploadPrivateFileModal" tabindex="-1" aria-labelledby="uploadPrivateFileModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" class="modal-content" data-ajax data-ajax-progress>
                    @csrf
                    {{-- Diunggah dari My Files: masuk ke folder pribadi yang sedang dibuka (kosong = akar My Files) --}}
                    <input type="hidden" name="folder_id" value="{{ $currentFolder->id ?? '' }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadPrivateFileModalLabel">Unggah File Pribadi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="choose_private_file" class="form-label fw-medium text-secondary">Pilih File</label>
                            <input class="form-control @error('file') is-invalid @enderror" type="file" id="choose_private_file" name="file" required>
                            <div class="form-text text-muted mt-2 small">
                                Berkas ini hanya akan tampil di ruang penyimpanan pribadi Anda.
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
    </div>
</div>

{{-- Pencarian sisi-klien: menyaring baris yang sudah tampil, tanpa reload. --}}
<script src="{{ \App\Support\AppAsset::url('js/data-integration/my-files.js') }}"></script>

@include('data-integration.partials.item-action-modals', [
    'moveGroups'    => $moveGroups,
    'moveRootLabel' => 'File Saya (tingkat atas)',
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
