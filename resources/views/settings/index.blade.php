@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
@php
    $v = fn ($key) => old($key, $settings[$key] ?? '');

    $imageMeta = [
        'logo_pdf'  => ['label' => 'Logo Dokumen', 'hint' => 'Dipakai di Surat Jalan, Kwitansi, dan Laporan Keuangan. PNG atau JPG, maks. 2 MB.'],
        'kop_atas'  => ['label' => 'Kop Atas', 'hint' => 'Laporan Inventory (PDF). Lebar min. 1240 px, perbandingan sekitar 21 : 5 (contoh 2480 x 594 px).'],
        'kop_bawah' => ['label' => 'Kop Bawah', 'hint' => 'Laporan Inventory (PDF). Ukuran dan perbandingan sama dengan kop atas.'],
    ];

    $categories = old('project_categories', $settings['project_categories']);
    $customStatuses = old('inventory_statuses', $settings['inventory_statuses']);
    $statusColors = \App\Support\InventoryStatus::COLORS;
@endphp

<div class="container-fluid px-4 py-3">

    <div class="mb-4">
        <h3 class="fw-bold text-dark m-0">Pengaturan</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pengaturan</li>
            </ol>
        </nav>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="alert alert-danger small">
                <i class="bi bi-exclamation-triangle me-1"></i> Periksa kembali isian yang ditandai merah.
            </div>
        @endif

        {{-- Profil Perusahaan --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-building me-2"></i>Profil Perusahaan</h6>
                <div class="row g-3">
                    @foreach([
                        ['company_name', 'Nama Perusahaan', 'col-md-6', true],
                        ['company_tagline', 'Tagline Nama', 'col-md-6', false],
                        ['company_address', 'Alamat', 'col-12', true],
                        ['company_phone', 'Telepon', 'col-md-6', false],
                        ['company_whatsapp', 'WhatsApp', 'col-md-6', false],
                        ['company_footer_tagline', 'Tagline Footer Dokumen', 'col-12', false],
                        ['company_footer_contact', 'Kontak Footer Dokumen', 'col-12', false],
                        ['company_website', 'Website', 'col-md-4', false],
                        ['company_instagram', 'Instagram', 'col-md-4', false],
                        ['company_tiktok', 'TikTok', 'col-md-4', false],
                    ] as [$field, $label, $col, $required])
                        <div class="{{ $col }}">
                            <label for="{{ $field }}" class="form-label fw-semibold small text-secondary">
                                {{ $label }} @if($required)<span class="text-danger">*</span>@endif
                            </label>
                            <input type="text" name="{{ $field }}" id="{{ $field }}" autocomplete="off"
                                   class="form-control @error($field) is-invalid @enderror"
                                   value="{{ $v($field) }}" @if($required) required @endif>
                            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Gambar Dokumen --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-image me-2"></i>Gambar Dokumen</h6>
                <div class="row g-4">
                    @foreach($imageMeta as $key => $meta)
                        <div class="col-12 col-lg-4">
                            <label for="{{ $key }}" class="form-label fw-semibold small text-secondary">{{ $meta['label'] }}</label>
                            <div class="border rounded-3 bg-light p-2 mb-2 text-center">
                                <img src="{{ $images[$key]['url'] }}" alt="{{ $meta['label'] }}" class="img-fluid" style="max-height: 110px; object-fit: contain;">
                            </div>
                            <input type="file" name="{{ $key }}" id="{{ $key }}" accept=".png,.jpg,.jpeg"
                                   class="form-control form-control-sm @error($key) is-invalid @enderror">
                            @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text small">{{ $meta['hint'] }}</div>
                            @if($images[$key]['custom'])
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="reset_images[]" value="{{ $key }}" id="reset_{{ $key }}">
                                    <label class="form-check-label small" for="reset_{{ $key }}">Kembalikan ke gambar bawaan</label>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Kategori Project --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-1"><i class="bi bi-tags me-2"></i>Kategori Project</h6>
                <p class="text-muted small mb-3">Project lama tetap menampilkan kategorinya walau dihapus dari daftar.</p>

                <div id="categoryList" class="d-flex flex-column gap-2">
                    @foreach($categories as $i => $category)
                        <div class="input-group category-row">
                            <input type="text" name="project_categories[]" maxlength="100" autocomplete="off"
                                   class="form-control @error('project_categories.' . $i) is-invalid @enderror"
                                   value="{{ $category }}">
                            <button type="button" class="btn btn-outline-danger btn-remove-category" aria-label="Hapus kategori">
                                <i class="bi bi-trash"></i>
                            </button>
                            @error('project_categories.' . $i)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
                @error('project_categories')<div class="text-danger small mt-2">{{ $message }}</div>@enderror

                <button type="button" class="btn btn-outline-primary btn-sm mt-3" id="btnAddCategory">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
                </button>
            </div>
        </div>

        {{-- Status Barang --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-1"><i class="bi bi-circle-half me-2"></i>Status Barang</h6>
                <p class="text-muted small mb-3">Status kustom selalu berarti unit tidak bisa dipinjam, dan bisa dipilih di Kelola Unit Fisik.</p>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach(\App\Support\InventoryStatus::SYSTEM as $name => $color)
                        <span class="badge bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }}-subtle px-3 py-2 rounded-pill">{{ $name }}</span>
                    @endforeach
                    <span class="text-muted small align-self-center">Status sistem, tidak bisa diubah.</span>
                </div>

                <div id="statusList" class="d-flex flex-column gap-2">
                    @foreach($customStatuses as $i => $row)
                        <div class="row g-2 status-row">
                            <div class="col-7 col-md-5">
                                <input type="text" name="inventory_statuses[{{ $i }}][name]" maxlength="30" autocomplete="off"
                                       class="form-control @error('inventory_statuses.' . $i . '.name') is-invalid @enderror"
                                       value="{{ $row['name'] }}" placeholder="Nama status">
                                @error('inventory_statuses.' . $i . '.name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-5 col-md-3">
                                <select name="inventory_statuses[{{ $i }}][color]" class="form-select">
                                    @foreach($statusColors as $value => $label)
                                        <option value="{{ $value }}" @selected(($row['color'] ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-outline-danger btn-remove-status" aria-label="Hapus status"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('inventory_statuses')<div class="text-danger small mt-2">{{ $message }}</div>@enderror

                <button type="button" class="btn btn-outline-primary btn-sm mt-3" id="btnAddStatus">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Status
                </button>
            </div>
        </div>

        {{-- Aturan Operasional --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-sliders me-2"></i>Aturan Operasional</h6>
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="servis_segera_hari" class="form-label fw-semibold small text-secondary">Servis Segera (hari)</label>
                        <input type="number" name="servis_segera_hari" id="servis_segera_hari" min="1" max="90"
                               class="form-control @error('servis_segera_hari') is-invalid @enderror" value="{{ $v('servis_segera_hari') }}" required>
                        @error('servis_segera_hari')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text small">Unit ditandai kuning bila sisa hari servis sebanyak ini atau kurang.</div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="repair_warn_percent" class="form-label fw-semibold small text-secondary">Pertimbangkan Ganti (%)</label>
                        <input type="number" name="repair_warn_percent" id="repair_warn_percent" min="10" max="200"
                               class="form-control @error('repair_warn_percent') is-invalid @enderror" value="{{ $v('repair_warn_percent') }}" required>
                        @error('repair_warn_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text small">Biaya servis satu unit setara persen ini dari harga beli baru.</div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="location_default_radius" class="form-label fw-semibold small text-secondary">Radius Lokasi Default (m)</label>
                        <input type="number" name="location_default_radius" id="location_default_radius" min="10" max="5000"
                               class="form-control @error('location_default_radius') is-invalid @enderror" value="{{ $v('location_default_radius') }}" required>
                        @error('location_default_radius')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text small">Dipakai saat radius lokasi baru dikosongkan.</div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="upload_max_mb" class="form-label fw-semibold small text-secondary">Maks. Upload (MB)</label>
                        <input type="number" name="upload_max_mb" id="upload_max_mb" min="1" max="50"
                               class="form-control @error('upload_max_mb') is-invalid @enderror" value="{{ $v('upload_max_mb') }}" required>
                        @error('upload_max_mb')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text small">Data Integration. Batas server (php.ini) tetap berlaku.</div>
                    </div>
                    <div class="col-12">
                        <label for="upload_allowed_extensions" class="form-label fw-semibold small text-secondary">Ekstensi File yang Diizinkan</label>
                        <input type="text" name="upload_allowed_extensions" id="upload_allowed_extensions" autocomplete="off"
                               class="form-control @error('upload_allowed_extensions') is-invalid @enderror" value="{{ $v('upload_allowed_extensions') }}" required>
                        @error('upload_allowed_extensions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text small">Pisahkan dengan koma. Berlaku untuk upload file di Data Integration.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pengaturan yang sudah ada di halaman lain --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-link-45deg me-2"></i>Pengaturan Lainnya</h6>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <a href="{{ route('inventory.locations.index') }}" class="d-flex align-items-center gap-3 border rounded-3 p-3 text-decoration-none text-dark">
                            <i class="bi bi-geo-alt fs-4 text-primary"></i>
                            <span><span class="fw-semibold d-block">Lokasi</span><span class="text-muted small">Tambah dan kelola lokasi penyimpanan</span></span>
                        </a>
                    </div>
                    <div class="col-12 col-md-6">
                        <a href="{{ route('announcements.index') }}" class="d-flex align-items-center gap-3 border rounded-3 p-3 text-decoration-none text-dark">
                            <i class="bi bi-megaphone fs-4 text-primary"></i>
                            <span><span class="fw-semibold d-block">Notifikasi</span><span class="text-muted small">Aktifkan dan urutkan jenis notifikasi otomatis</span></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end pb-4">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg me-1"></i> Simpan Pengaturan
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('categoryList');

    function rowHtml() {
        return '<div class="input-group category-row">'
            + '<input type="text" name="project_categories[]" maxlength="100" autocomplete="off" class="form-control">'
            + '<button type="button" class="btn btn-outline-danger btn-remove-category" aria-label="Hapus kategori"><i class="bi bi-trash"></i></button>'
            + '</div>';
    }

    const statusList = document.getElementById('statusList');
    const colorOptions = @json(\App\Support\InventoryStatus::COLORS);

    document.getElementById('btnAddStatus').addEventListener('click', function () {
        const i = Date.now();
        const options = Object.keys(colorOptions).map(function (k) { return '<option value="' + k + '">' + colorOptions[k] + '</option>'; }).join('');

        statusList.insertAdjacentHTML('beforeend',
            '<div class="row g-2 status-row">'
            + '<div class="col-7 col-md-5"><input type="text" name="inventory_statuses[' + i + '][name]" maxlength="30" autocomplete="off" class="form-control" placeholder="Nama status"></div>'
            + '<div class="col-5 col-md-3"><select name="inventory_statuses[' + i + '][color]" class="form-select">' + options + '</select></div>'
            + '<div class="col-auto"><button type="button" class="btn btn-outline-danger btn-remove-status" aria-label="Hapus status"><i class="bi bi-trash"></i></button></div>'
            + '</div>');
        statusList.lastElementChild.querySelector('input').focus();
    });

    statusList.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-status');
        if (btn) btn.closest('.status-row').remove();
    });

    document.getElementById('btnAddCategory').addEventListener('click', function () {
        list.insertAdjacentHTML('beforeend', rowHtml());
        list.lastElementChild.querySelector('input').focus();
    });

    list.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-category');
        if (!btn) return;

        // Sisakan minimal satu baris; baris terakhir hanya dikosongkan.
        if (list.querySelectorAll('.category-row').length > 1) {
            btn.closest('.category-row').remove();
        } else {
            btn.closest('.category-row').querySelector('input').value = '';
        }
    });
});
</script>
@endsection
