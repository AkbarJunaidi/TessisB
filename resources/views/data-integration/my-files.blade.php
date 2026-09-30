@extends('layouts.app') {{-- Sesuai layout dashboard asli sistem Anda --}}

@push('styles')
<style>
    /* ===== My Files: tampilan daftar bergaya Drive ===== */
    .drv-tabs .nav-link { border-radius: 50rem; padding: .4rem 1rem; font-size: .875rem; color: var(--bs-secondary-color, #6c757d); }
    .drv-tabs .nav-link.active { background: var(--bs-primary-bg-subtle, #cfe2ff); color: var(--bs-primary-text-emphasis, #052c65); font-weight: 600; }

    .drv-search { position: relative; max-width: 420px; flex: 1 1 260px; }
    .drv-search i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--bs-secondary-color, #6c757d); pointer-events: none; }
    .drv-search input { border-radius: 50rem; padding-left: 2.6rem; background: var(--bs-tertiary-bg, #f1f3f5); border-color: transparent; }
    .drv-search input:focus { background: #fff; border-color: var(--bs-primary, #0d6efd); }

    .drv-chip { border-radius: .5rem; font-size: .8125rem; padding: .3rem .75rem; }
    .drv-chip.is-active { background: var(--bs-primary-bg-subtle, #cfe2ff); border-color: transparent; color: var(--bs-primary-text-emphasis, #052c65); }

    .drv-list { width: 100%; border-collapse: collapse; }
    .drv-list thead th { font-size: .8125rem; font-weight: 600; color: var(--bs-secondary-color, #6c757d);
                         padding: .75rem .75rem; border-bottom: 1px solid var(--bs-border-color, #dee2e6); white-space: nowrap; }
    .drv-list tbody td { padding: .7rem .75rem; border-bottom: 1px solid var(--bs-border-color-translucent, rgba(0,0,0,.1)); vertical-align: middle; }
    .drv-list tbody tr.drv-row:hover { background: var(--bs-tertiary-bg, #f1f3f5); }
    .drv-group td { padding: 1rem .75rem .35rem !important; border-bottom: 0 !important; font-size: .8125rem; font-weight: 600; color: var(--bs-secondary-color, #6c757d); }
    .drv-name { display: flex; align-items: center; gap: .85rem; min-width: 0; }
    .drv-name .drv-ico { font-size: 1.35rem; flex: 0 0 auto; }
    .drv-name .drv-title { font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .drv-loc { color: var(--bs-secondary-color, #6c757d); font-size: .8125rem; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: bottom; }
    .drv-meta-mobile { color: var(--bs-secondary-color, #6c757d); font-size: .75rem; }

    /* Mobile (< md): kolom sekunder disembunyikan; lokasi/sumber/tanggal pindah ke baris kecil di bawah nama. */
    @media (max-width: 767.98px) {
        .drv-name .drv-title { max-width: 55vw; }
    }
</style>
@endpush

@section('content')
@php
    // Permission dihitung sekali di atas, dipakai berulang di tiap baris.
    $me          = auth()->user();
    $canDownload = $me->hasPermission('data_integration', 'download');
    $canRename   = $me->hasPermission('data_integration', 'rename');
    $canDelete   = $me->hasPermission('data_integration', 'delete');
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
            <button class="btn btn-primary rounded-pill px-3" type="button" data-bs-toggle="modal" data-bs-target="#uploadPrivateFileModal">
                <i class="bi bi-file-earmark-arrow-up me-1"></i> Upload File
            </button>
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
                    <th scope="col" class="text-end pe-3" style="width: 64px;"><span class="visually-hidden">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($files as $file)
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
                                               onclick="openRenameModal({{ \Illuminate\Support\Js::from(route('files.rename', $file->id)) }}, {{ \Illuminate\Support\Js::from($file->base_name) }}, 'file_name', {{ \Illuminate\Support\Js::from($file->locked_extension) }}); return false;">
                                                <i class="bi bi-pencil me-2 text-muted"></i> Rename
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item small py-2" href="#"
                                               onclick="openMoveModal({{ \Illuminate\Support\Js::from(route('files.move', $file->id)) }}); return false;">
                                                <i class="bi bi-file-symlink me-2 text-muted"></i> Move to Shared Space
                                            </a>
                                        </li>
                                    @endif
                                    @if($canDelete)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item small py-2 text-danger" href="#"
                                               onclick="openDeleteModal({{ \Illuminate\Support\Js::from(route('files.destroy', $file->id)) }}, {{ \Illuminate\Support\Js::from($file->file_name) }}); return false;">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-cloud-slash display-6 mb-2 d-block"></i>
                            Belum ada file pribadi. Klik <strong>Upload File</strong> untuk menambahkan.
                        </td>
                    </tr>
                @endforelse
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
                    <th scope="col" class="text-end pe-3" style="width: 64px;"><span class="visually-hidden">Aksi</span></th>
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
                                    <div class="drv-title">{{ $item['name'] }}</div>
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
                                {{-- Tab ini hanya untuk melihat: tidak ada rename/pindah/hapus --}}
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
<script>
    (function () {
        'use strict';

        const input    = document.getElementById('drvSearch');
        const noResult = document.getElementById('drvNoResult');
        if (!input) return;

        const rows   = Array.from(document.querySelectorAll('[data-drv-row]'));
        const groups = Array.from(document.querySelectorAll('[data-drv-group]'));

        input.addEventListener('input', function () {
            const q = input.value.trim().toLowerCase();
            let visible = 0;

            rows.forEach(function (row) {
                const match = q === '' || (row.dataset.search || '').indexOf(q) !== -1;
                row.classList.toggle('d-none', !match);
                if (match) visible++;
            });

            // Sembunyikan judul kelompok yang seluruh barisnya tersaring.
            groups.forEach(function (group) {
                let next = group.nextElementSibling;
                let hasVisible = false;
                while (next && !next.hasAttribute('data-drv-group')) {
                    if (next.hasAttribute('data-drv-row') && !next.classList.contains('d-none')) hasVisible = true;
                    next = next.nextElementSibling;
                }
                group.classList.toggle('d-none', !hasVisible);
            });

            noResult.classList.toggle('d-none', !(q !== '' && rows.length > 0 && visible === 0));
        });
    })();
</script>

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
                    <div class="form-text small" id="dynamicRenameExtHint"></div>
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
     * Menampilkan modal ganti nama file.
     * currentName sudah TANPA ekstensi; lockedExt ditambahkan kembali otomatis oleh server
     * (lihat File::applyLockedExtension), jadi ekstensi tidak bisa hilang saat rename.
     */
    function openRenameModal(actionUrl, currentName, inputFieldName, lockedExt) {
        const form = document.getElementById('dynamicRenameForm');
        const input = document.getElementById('dynamicRenameInput');
        const hint = document.getElementById('dynamicRenameExtHint');

        form.action = actionUrl;
        input.name = inputFieldName;
        input.value = currentName;
        hint.textContent = lockedExt ? 'Ekstensi .' + lockedExt + ' dipertahankan otomatis.' : '';

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