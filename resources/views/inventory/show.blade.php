@extends('layouts.app')

@section('title', 'Detail Inventory - ' . $inventory->name)

@section('content')
<div class="container-fluid p-0">

    <!-- Header Page & Back Button (Hanya Tampil di Desktop) -->
    <div class="d-none d-md-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                {{ $inventory->name }}
                <span class="badge {{ \App\Support\InventoryStatus::subtleClass($inventory->display_status) }} px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.65rem;"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>{{ strtoupper($inventory->display_status ?? 'TERSEDIA') }}</span>
            </h3>
            <p class="text-muted small m-0">Menampilkan informasi lengkap dan identitas aset barang.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 fw-medium">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
            </a>
            <a href="{{ route('inventory.preview-qr', $inventory->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 fw-medium">
                <i class="bi bi-printer"></i> Print QR Label
            </a>
            <div class="dropdown">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 fw-medium" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-file-earmark-text"></i> Report
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('inventory.preview', $inventory->id) }}" target="_blank"><i class="bi bi-eye"></i> Preview</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('inventory.download', $inventory->id) }}"><i class="bi bi-download"></i> Download</a></li>
                </ul>
            </div>
            @if(auth()->user()->hasPermission('inventory', 'edit'))
            <a href="{{ route('inventory.edit', $inventory->id) }}" class="btn btn-sm btn-primary d-flex align-items-center gap-2 fw-medium">
                <i class="bi bi-pencil"></i> Edit Aset
            </a>
            @endif
        </div>
    </div>

    <!-- Header Page Mobile (Sederhana) -->
    <div class="d-md-none mb-3 px-1">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <h4 class="fw-bold text-dark m-0">{{ $inventory->name }}</h4>
            <span class="badge {{ \App\Support\InventoryStatus::subtleClass($inventory->display_status) }} px-3 py-2 rounded-pill fw-semibold"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> {{ strtoupper($inventory->display_status ?? 'TERSEDIA') }}</span>
        </div>
        <p class="text-muted small mb-2">Informasi lengkap aset barang</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2 fw-medium">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            @if(auth()->user()->hasPermission('inventory', 'edit'))
            <a href="{{ route('inventory.edit', $inventory->id) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2 fw-medium">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endif
            <a href="{{ route('inventory.preview-qr', $inventory->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2 fw-medium">
                <i class="bi bi-qr-code"></i> Cetak QR
            </a>
            <div class="dropdown">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-2 fw-medium" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-file-earmark-text"></i> Report
                </button>
                <ul class="dropdown-menu shadow-sm">
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('inventory.preview', $inventory->id) }}" target="_blank"><i class="bi bi-eye"></i> Preview</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('inventory.download', $inventory->id) }}"><i class="bi bi-download"></i> Download</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif


    <!-- ================= LAYOUT MOBILE (TAMPIL HANYA DI HP/TABLET KECIL) ================= -->
    <div class="d-md-none">
        <div class="card shadow-sm border-0 rounded-4 bg-white mb-4">
            <div class="card-body p-4">

                <!-- Foto Utama Barang -->
                <div class="text-center my-3 py-2">
                    @if($inventory->image)
                        <img src="{{ asset('storage/' . $inventory->image) }}"
                             alt="Foto {{ $inventory->name }}"
                             class="img-fluid rounded-3"
                             style="max-height: 220px; width: 100%; object-fit: contain;">
                    @else
                        <div class="text-center py-5 text-muted border border-dashed rounded-3 bg-light">
                            <i class="bi bi-image opacity-25 d-block mb-2" style="font-size: 3rem;"></i>
                            <span class="small fw-medium">Foto barang belum diunggah</span>
                        </div>
                    @endif
                </div>

                <!-- Nama Barang & Sub-Deskripsi Ringkas -->
                <div class="mb-4 text-center text-sm-start">
                    <h4 class="fw-bold text-dark mb-1">{{ $inventory->name }}</h4>
                    <p class="text-muted small mb-0">{{ Str::limit($inventory->description, 90, '...') }}</p>
                </div>

                <hr class="border-light my-4">

                <!-- 1. Informasi Identitas Aset -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam text-primary"></i> Informasi Identitas Aset
                    </h6>
                    <table class="table table-borderless table-sm small align-middle mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted py-2 ps-0" style="width: 40%;"><i class="bi bi-tag text-secondary me-2"></i>Brand</td>
                                <td class="fw-bold text-dark py-2 text-end">{{ $inventory->brand ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted py-2 ps-0" style="width: 40%;"><i class="bi bi-hash text-secondary me-2"></i>Serial Number</td>
                                <td class="fw-bold text-dark py-2 text-end font-monospace">{{ $inventory->serial_number }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted py-2 ps-0"><i class="bi bi-calendar-event text-secondary me-2"></i>Tanggal Input</td>
                                <td class="text-dark py-2 text-end">{{ $inventory->created_at ? $inventory->created_at->format('d M Y, H:i') . ' WIB' : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted py-2 ps-0"><i class="bi bi-clock text-secondary me-2"></i>Terakhir Update</td>
                                <td class="text-dark py-2 text-end">{{ $inventory->updated_at ? $inventory->updated_at->format('d M Y, H:i') . ' WIB' : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr class="border-light my-4">

                <!-- 2. Deskripsi Barang Lengkap -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-file-text text-primary"></i> Deskripsi Barang
                    </h6>
                    @if(!empty($inventory->description))
                        <p class="text-dark small mb-0" style="white-space: pre-line; line-height: 1.6;">
                            {{ $inventory->description }}
                        </p>
                    @else
                        <p class="text-muted fst-italic small mb-0">Belum ada deskripsi.</p>
                    @endif
                </div>

                <!-- 3. Informasi Tambahan (Atribut Dinamis) -->
                @if($inventory->attributes && $inventory->attributes->count() > 0)
                    <hr class="border-light my-4">
                    <div class="mb-2">
                        <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-sliders text-primary"></i> Informasi Tambahan
                        </h6>
                        <table class="table table-borderless table-sm small align-middle mb-0">
                            <tbody>
                                @foreach($inventory->attributes as $attr)
                                    <tr>
                                        <td class="text-muted py-2 ps-0"><i class="bi bi-tag text-secondary me-2"></i>{{ $attr->attribute_name }}</td>
                                        <td class="fw-semibold text-dark py-2 text-end">{{ $attr->attribute_value }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>


    <!-- ================= LAYOUT DESKTOP (TAMPIL HANYA DI LAYAR LEBAR / DESKTOP) ================= -->
    {{-- PENTING: mb-4 ditaruh di SINI (pembungkus paling luar), BUKAN di
         salah satu row di dalamnya - supaya jaraknya ke "Status Unit
         Fisik" (section terpisah, di luar grid ini, bukan bagian dari
         .row g-4) tetap ada TANPA numpuk gutter row lain di dalamnya
         seperti bug sebelumnya. --}}
    <div class="d-none d-md-block mb-4">
        <div class="row g-4">

            <!-- BARIS 1 (lebar penuh 12-kol): Foto Fisik Barang (sempit) + Deskripsi Barang
                 (lebar, bentuk persegi panjang horizontal - menggantikan slot Deskripsi +
                 Aksi Cepat yang lama sekaligus, sesuai sketsa yang dikirim). -->
            <div class="col-12">
                {{-- PENTING: cuma "row g-4", TANPA mb-4 di sini - baris ini
                     sudah jadi kolom (col-12) langsung di dalam .row g-4
                     yang membungkusnya (baris 225), jadi jarak vertikal ke
                     baris berikutnya SUDAH otomatis dari gutter row itu.
                     Kalau ditambah mb-4 lagi di sini, jaraknya jadi dobel
                     (gutter row + mb-4 numpuk) - itu penyebab jarak ke
                     "Informasi Identitas Aset" kelihatan lebih lebar dari
                     jarak-jarak lain di halaman ini. --}}
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0 rounded-3 bg-white h-100">
                            <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                                <h6 class="fw-bold text-dark m-0">Foto Fisik Barang</h6>
                            </div>
                            <div class="card-body p-4 d-flex align-items-center justify-content-center">
                                @if($inventory->image)
                                    <img src="{{ asset('storage/' . $inventory->image) }}"
                                         alt="Foto {{ $inventory->name }}"
                                         class="img-fluid rounded"
                                         style="max-height: 240px; width: 100%; object-fit: contain;">
                                @else
                                    <div class="text-center py-5 text-muted border border-dashed rounded w-100 bg-light">
                                        <i class="bi bi-image opacity-25 d-block mb-2" style="font-size: 3rem;"></i>
                                        <span class="small fw-medium">Foto barang belum diunggah</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0 rounded-3 bg-white h-100">
                            <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                                <h6 class="fw-bold text-dark m-0">Deskripsi Barang</h6>
                            </div>
                            <div class="card-body p-4">
                                @if(!empty($inventory->description))
                                    <p class="text-dark small mb-0" style="white-space: pre-line; line-height: 1.6;">
                                        {{ $inventory->description }}
                                    </p>
                                @else
                                    <p class="text-muted fst-italic small mb-0">Belum ada deskripsi.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $hasAttributes = $inventory->attributes && $inventory->attributes->count() > 0;
            @endphp

            <!-- SISI KIRI (8 KOLOM): Informasi Identitas Aset, di bawahnya Informasi Tambahan (selebar sama) -->
            <div class="col-12 col-lg-8">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 rounded-3 bg-white h-100">
                            <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                                <h6 class="fw-bold text-dark m-0">Informasi Identitas Aset</h6>
                            </div>
                            <div class="card-body p-4">
                                <table class="table table-borderless table-sm align-middle small mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted py-2" style="width: 40%;">Nama Barang</td>
                                            <td class="fw-bold text-dark py-2">: {{ $inventory->name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-2">Serial Number</td>
                                            <td class="py-2">: <span class="font-monospace fw-semibold text-secondary">{{ $inventory->serial_number }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-2">Status Barang</td>
                                            <td class="py-2">: <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">{{ strtoupper($inventory->display_status ?? 'TERSEDIA') }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-2">Jumlah Barang</td>
                                            <td class="py-2">:
                                                <span class="fw-semibold">{{ $inventory->quantity_total }} unit total</span>
                                                <span class="text-success">({{ $inventory->qty_available }} tersedia</span>,
                                                <span class="text-secondary">{{ $inventory->qty_in_use }} sedang dipakai)</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-2">Brand</td>
                                            <td class="fw-bold text-dark py-2">: {{ $inventory->brand ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-2">Tanggal Input</td>
                                            <td class="text-dark py-2">: {{ $inventory->created_at ? $inventory->created_at->format('d F Y H:i') . ' WIB' : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-2">Terakhir Update</td>
                                            <td class="text-dark py-2">: {{ $inventory->updated_at ? $inventory->updated_at->format('d F Y H:i') . ' WIB' : '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    @if($hasAttributes)
                        <div class="col-12">
                            <div class="card shadow-sm border-0 rounded-3 bg-white h-100">
                                <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                                    <h6 class="fw-bold text-dark m-0">Informasi Tambahan</h6>
                                </div>
                                <div class="card-body p-4">
                                    <table class="table table-borderless table-sm align-middle small mb-0">
                                        <tbody>
                                            @foreach($inventory->attributes as $attr)
                                                <tr>
                                                    <td class="text-muted py-2" style="width: 45%;"><i class="bi bi-tag text-primary me-2"></i>{{ $attr->attribute_name }}</td>
                                                    <td class="fw-semibold text-dark py-2">: {{ $attr->attribute_value }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- SISI KANAN (4 KOLOM): QR Code Aset -->
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm border-0 rounded-3 bg-white mb-3">
                    <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                        <h6 class="fw-bold text-dark m-0">QR Code Label</h6>
                        <small class="text-muted">Dipakai di QR Label - isinya Serial Number</small>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="p-3 bg-white rounded-3 border d-inline-block shadow-sm mb-3">
                            @if($inventory->qr_code_url)
                                <img src="{{ $inventory->qr_code_url }}" alt="QR Code {{ $inventory->serial_number }}" class="img-fluid" style="width: 180px; height: 180px; object-fit: contain;">
                            @else
                                <div class="d-flex flex-column align-items-center justify-content-center text-muted" style="width: 180px; height: 180px;">
                                    <i class="bi bi-qr-code opacity-25 fs-1 mb-2"></i>
                                    <span class="small">QR Code belum tersedia</span>
                                </div>
                            @endif
                        </div>
                        <div class="font-monospace fw-semibold text-secondary">SN: {{ $inventory->serial_number }}</div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-3 bg-white">
                    <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                        <h6 class="fw-bold text-dark m-0">QR Code Report</h6>
                        <small class="text-muted">Dipakai di Inventory Report - isinya link detail barang</small>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="p-3 bg-white rounded-3 border d-inline-block shadow-sm mb-3">
                            @if($inventory->qr_code_report_url)
                                <img src="{{ $inventory->qr_code_report_url }}" alt="QR Code Report {{ $inventory->serial_number }}" class="img-fluid" style="width: 180px; height: 180px; object-fit: contain;">
                            @else
                                <div class="d-flex flex-column align-items-center justify-content-center text-muted" style="width: 180px; height: 180px;">
                                    <i class="bi bi-qr-code opacity-25 fs-1 mb-2"></i>
                                    <span class="small">QR Code belum tersedia</span>
                                </div>
                            @endif
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>Scan untuk buka halaman detail (tanpa login)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD BARU: Status Unit Fisik (ringkasan status per-unit, terintegrasi dengan Surat Jalan) + lokasi unit -->
    @php
        // Unit yang boleh dipindah lokasi: tidak sedang dipinjam dan tidak berstatus Hilang.
        $movableCount = $inventory->units->filter(fn ($u) => !$u->isOnLoan() && $u->status !== 'Hilang')->count();
    @endphp
    <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h6 class="fw-bold m-0">Status Unit Fisik</h6>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="text-muted small">{{ $inventory->qty_available }} dari {{ $inventory->quantity_total }} unit bisa dipinjam sekarang</span>
                    @php
                        $servisCounts = $inventory->punyaJadwalServis() ? $inventory->servisCounts() : ['terlambat' => 0, 'segera' => 0];
                        $inRepairCount = $inventory->units->filter(fn ($u) => $u->isInRepair())->count();
                        $needServiceIds = $inventory->units->filter(
                            fn ($u) => !$u->isOnLoan() && ($inventory->servisStatusFor($u)['state'] ?? 'ok') !== 'ok'
                        )->pluck('id')->all();
                    @endphp
                    @if($servisCounts['terlambat'] > 0)
                        <span class="badge-soft-danger px-2 py-1 rounded-pill small"><i class="bi bi-exclamation-octagon me-1"></i>{{ $servisCounts['terlambat'] }} unit servis terlewat</span>
                    @endif
                    @if($servisCounts['segera'] > 0)
                        <span class="badge-soft-warning px-2 py-1 rounded-pill small"><i class="bi bi-clock-history me-1"></i>{{ $servisCounts['segera'] }} unit segera servis</span>
                    @endif
                    @if($inRepairCount > 0)
                        <span class="badge-soft-info px-2 py-1 rounded-pill small"><i class="bi bi-wrench-adjustable me-1"></i>{{ $inRepairCount }} unit sedang diperbaiki</span>
                    @endif
                    @if($canManageRepairs)
                        <a href="{{ route('inventory.repairs.create', $needServiceIds ? ['unit_ids' => $needServiceIds] : []) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-tools me-1"></i> Kirim ke Servis
                        </a>
                    @endif
                    @if($inventory->punyaJadwalServis())
                        <span class="text-muted small">
                            <i class="bi bi-tools me-1"></i>Servis:
                            {{ collect([
                                $inventory->servis_interval_hari ? "setiap {$inventory->servis_interval_hari} hari" : null,
                                $inventory->servis_interval_pemakaian ? "setiap {$inventory->servis_interval_pemakaian} pemakaian" : null,
                            ])->filter()->implode(' / ') }}
                        </span>
                    @endif

                    @if($canMoveLocation && $movableCount > 0)
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox" id="unitSelectAll">
                            <label class="form-check-label small" for="unitSelectAll">Pilih semua</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnMoveLocation" disabled>
                            <i class="bi bi-geo-alt me-1"></i> Pindah Lokasi <span id="unitSelectedCount"></span>
                        </button>
                    @endif
                </div>
            </div>
            <div class="row g-2">
                @forelse($inventory->units as $unit)
                    @php
                        $badgeClass = \App\Support\InventoryStatus::subtleClass($unit->display_status, false);
                        $onLoan  = $unit->isOnLoan();
                        $movable = !$onLoan && $unit->status !== 'Hilang';
                    @endphp
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="border rounded-3 p-2 text-center position-relative {{ $badgeClass }} {{ ($inventory->servisStatusFor($unit)['state'] ?? '') === 'terlambat' ? 'border-danger' : '' }}">
                            @if($canMoveLocation && $movable)
                                <input class="form-check-input unit-select position-absolute top-0 start-0 m-2" type="checkbox"
                                       value="{{ $unit->id }}" data-number="{{ $unit->unit_number }}"
                                       aria-label="Pilih unit {{ $unit->unit_number }}">
                            @endif
                            <div class="fw-bold">#{{ $unit->unit_number }}</div>
                            <div class="small">{{ $unit->display_status }}</div>
                            @if($onLoan)
                                <div class="small text-truncate" style="font-size:.65rem;"><i class="bi bi-truck me-1"></i>Di lapangan</div>
                                <div class="small text-truncate" style="font-size:.65rem;">{{ $unit->suratJalanItem->suratJalan->nomor ?? '' }}</div>
                            @elseif($unit->isInRepair())
                                @php $repair = $unit->repairItem->repair ?? null; @endphp
                                <div class="small fw-semibold" style="font-size:.65rem; line-height:1.2;" title="Perbaikan di {{ $repair->tempat_nama ?? '-' }}">
                                    <i class="bi bi-wrench-adjustable me-1"></i>Perbaikan di {{ $repair->tempat_nama ?? '-' }}
                                </div>
                                @if($repair)
                                    <a href="{{ route('inventory.repairs.show', $repair) }}" class="small text-decoration-none d-block" style="font-size:.6rem;">{{ $repair->code }}</a>
                                @endif
                            @elseif($unit->lokasiSekarang)
                                <div class="small text-truncate" style="font-size:.65rem;" title="Lokasi sekarang: {{ $unit->lokasiSekarang->name }}">
                                    <i class="bi bi-geo-alt me-1"></i>{{ $unit->lokasiSekarang->name }}
                                </div>
                                @if($unit->isOffHome())
                                    <div class="small text-truncate fw-semibold" style="font-size:.6rem;"
                                         title="Lokasi utama: {{ $unit->lokasiUtama->name ?? '-' }}">Belum di lokasi utama</div>
                                @endif
                            @endif
                            @php $servis = $inventory->servisStatusFor($unit); @endphp
                            @if($servis && $servis['state'] !== 'ok')
                                <div class="small fw-semibold {{ $servis['state'] === 'terlambat' ? 'text-danger' : '' }}" style="font-size:.6rem; line-height:1.2;" title="{{ implode(' / ', $servis['pesan']) }}">
                                    <i class="bi bi-tools me-1"></i>{{ $servis['state'] === 'terlambat' ? 'Servis terlewat' : 'Segera servis' }}
                                    <span class="d-block fw-normal">{{ implode(' / ', $servis['pesan']) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted small m-0">Belum ada data unit fisik untuk barang ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if($canMoveLocation)
        {{-- Modal Pindah Lokasi: lokasi tujuan terpilih otomatis bila GPS cocok dengan radius sebuah lokasi,
             selain itu dipilih manual dari daftar lokasi penyimpanan. --}}
        <div class="modal fade" id="moveLocationModal" tabindex="-1" aria-labelledby="moveLocationTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
                <form id="moveLocationForm" method="POST" action="{{ route('inventory.units.move-location', $inventory) }}"
                      class="modal-content border-0 shadow"
                      data-detect-url="{{ route('inventory.locations.detect') }}">
                    @csrf
                    <div id="moveUnitInputs"></div>
                    <input type="hidden" name="lat" id="moveLat">
                    <input type="hidden" name="lng" id="moveLng">
                    <input type="hidden" name="accuracy" id="moveAcc">

                    <div class="modal-header border-0 bg-light py-3">
                        <h5 class="modal-title fw-semibold" id="moveLocationTitle">Pindah Lokasi Unit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body py-4">
                        <p class="small text-muted mb-3">Unit dipilih: <strong id="moveUnitList"></strong></p>

                        <div id="moveGpsBox" class="alert alert-light border small py-2 d-flex justify-content-between align-items-center gap-2" role="status">
                            <span id="moveGpsText">Mendeteksi lokasi...</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" id="moveGpsRetry">
                                <i class="bi bi-arrow-repeat me-1"></i>Ulangi
                            </button>
                        </div>

                        <div class="mb-3">
                            <label for="moveTarget" class="form-label fw-medium text-secondary">Lokasi Tujuan</label>
                            <select class="form-select" id="moveTarget" name="target_location_id" required>
                                <option value="" disabled selected>-- Pilih lokasi tujuan --</option>
                                @foreach($storageLocations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                            @if($storageLocations->isEmpty())
                                <div class="form-text text-danger small">Belum ada lokasi penyimpanan aktif. Tambahkan di halaman Lokasi.</div>
                            @endif
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="make_home" value="1" id="moveMakeHome">
                            <label class="form-check-label small" for="moveMakeHome">
                                Jadikan juga lokasi utama unit (tempat simpan semestinya)
                            </label>
                        </div>
                    </div>

                    <div class="modal-footer border-0 bg-light py-2">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">Pindahkan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Dimuat lewat stack agar berjalan SETELAH Bootstrap JS (layout memuatnya di akhir body). --}}
        @push('scripts')
        <script>
            (function () {
                'use strict';

                const modalEl   = document.getElementById('moveLocationModal');
                const form      = document.getElementById('moveLocationForm');
                const btnOpen   = document.getElementById('btnMoveLocation');
                const selectAll = document.getElementById('unitSelectAll');
                const countEl   = document.getElementById('unitSelectedCount');
                const inputsBox = document.getElementById('moveUnitInputs');
                const listEl    = document.getElementById('moveUnitList');
                const targetEl  = document.getElementById('moveTarget');
                const gpsBox    = document.getElementById('moveGpsBox');
                const gpsText   = document.getElementById('moveGpsText');
                const latEl     = document.getElementById('moveLat');
                const lngEl     = document.getElementById('moveLng');
                const accEl     = document.getElementById('moveAcc');

                if (!btnOpen) return;

                const checkboxes = Array.from(document.querySelectorAll('.unit-select'));
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

                // Naik setiap deteksi baru / modal ditutup; hasil deteksi yang basi dibuang.
                let detectToken = 0;

                function selected() {
                    return checkboxes.filter(function (c) { return c.checked; });
                }

                function refreshSelection() {
                    const n = selected().length;
                    btnOpen.disabled = n === 0;
                    countEl.textContent = n > 0 ? '(' + n + ')' : '';
                    selectAll.checked = n > 0 && n === checkboxes.length;
                    selectAll.indeterminate = n > 0 && n < checkboxes.length;
                }

                checkboxes.forEach(function (c) { c.addEventListener('change', refreshSelection); });
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(function (c) { c.checked = selectAll.checked; });
                    refreshSelection();
                });

                /** Tampilan status deteksi: kind = info | success | warning | muted. */
                function setStatus(kind, text) {
                    const cls = { info: 'alert-info', success: 'alert-success', warning: 'alert-warning', muted: 'alert-light border' };
                    gpsBox.className = 'alert ' + (cls[kind] || cls.muted) + ' small py-2 d-flex justify-content-between align-items-center gap-2';
                    gpsText.textContent = text;
                }

                function clearCoords() {
                    latEl.value = '';
                    lngEl.value = '';
                    accEl.value = '';
                }

                function detect() {
                    const token = ++detectToken;
                    clearCoords();
                    targetEl.selectedIndex = 0;

                    if (!('geolocation' in navigator)) {
                        setStatus('muted', 'GPS tidak tersedia di perangkat ini. Pilih lokasi secara manual.');
                        return;
                    }
                    if (!window.isSecureContext) {
                        setStatus('muted', 'GPS hanya bisa dipakai lewat HTTPS. Pilih lokasi secara manual.');
                        return;
                    }

                    setStatus('info', 'Mendeteksi lokasi...');

                    navigator.geolocation.getCurrentPosition(function (pos) {
                        if (token !== detectToken) return;

                        const c = pos.coords;
                        latEl.value = c.latitude;
                        lngEl.value = c.longitude;
                        accEl.value = Math.round(c.accuracy);

                        const query = new URLSearchParams({ lat: c.latitude, lng: c.longitude, accuracy: c.accuracy });

                        fetch(form.dataset.detectUrl + '?' + query.toString(), {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        })
                            .then(function (res) {
                                if (!res.ok) throw new Error('detect failed');
                                return res.json();
                            })
                            .then(function (data) {
                                if (token !== detectToken) return;

                                if (data.status === 'terdeteksi' && data.location) {
                                    const option = Array.from(targetEl.options).find(function (o) {
                                        return o.value === String(data.location.id);
                                    });
                                    if (option) {
                                        targetEl.value = option.value;
                                        setStatus('success', 'Terdeteksi: ' + data.location.name + ' (sekitar ' + data.distance_m + ' m)');
                                        return;
                                    }
                                }

                                if (data.status === 'akurasi_rendah') {
                                    setStatus('warning', 'Akurasi GPS rendah (sekitar ' + data.accuracy_m + ' m). Pilih lokasi secara manual.');
                                    return;
                                }

                                setStatus('warning', 'Di luar jangkauan. Pilih lokasi secara manual.');
                            })
                            .catch(function () {
                                if (token !== detectToken) return;
                                setStatus('muted', 'Gagal mendeteksi lokasi. Pilih lokasi secara manual.');
                            });
                    }, function (err) {
                        if (token !== detectToken) return;
                        clearCoords();
                        setStatus('muted', err.code === 1
                            ? 'Izin lokasi ditolak. Pilih lokasi secara manual.'
                            : 'Lokasi tidak dapat dibaca. Pilih lokasi secara manual.');
                    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
                }

                btnOpen.addEventListener('click', function () {
                    const chosen = selected();
                    if (chosen.length === 0) return;

                    // Isi id unit terpilih ke form + ringkasan nomor unit.
                    inputsBox.replaceChildren();
                    chosen.forEach(function (c) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'unit_ids[]';
                        input.value = c.value;
                        inputsBox.appendChild(input);
                    });
                    listEl.textContent = chosen.map(function (c) { return '#' + c.dataset.number; }).join(', ');
                    document.getElementById('moveMakeHome').checked = false;

                    modal.show();
                    detect();
                });

                document.getElementById('moveGpsRetry').addEventListener('click', detect);
                modalEl.addEventListener('hidden.bs.modal', function () { detectToken++; });
            })();
        </script>
        @endpush
    @endif
    <!-- CARD BARU: Riwayat Peminjaman (lintas semua Project, sumber: SuratJalanItem -
         baris ini tidak pernah dihapus saat barang dikembalikan, jadi otomatis
         jadi riwayat permanen). Dibatasi 20 terbaru - lihat InventoryService::getBorrowHistory(). -->
    <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0">Riwayat Peminjaman</h6>
                @if($borrowHistory->isNotEmpty())
                    <span class="text-muted small">{{ $borrowHistory->count() }} Surat Jalan terakhir</span>
                @endif
            </div>

            @if($borrowHistory->isEmpty())
                <p class="text-muted small m-0">Barang ini belum pernah dipinjam lewat Surat Jalan manapun.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-muted small text-uppercase">
                                <th>Project / Peminjam</th>
                                <th>No. Surat Jalan</th>
                                <th class="text-center">Jumlah</th>
                                <th>Tanggal Pinjam</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($borrowHistory as $item)
                                @php
                                    $sisaBelumKembali = $item->qty_dipakai - $item->qty_dikembalikan;
                                    $statusBadge = match(true) {
                                        $sisaBelumKembali <= 0 => ['Sudah Kembali', 'bg-success-subtle text-success border-success-subtle'],
                                        $item->qty_dikembalikan > 0 => ['Sebagian Kembali', 'bg-warning-subtle text-warning border-warning-subtle'],
                                        default => ['Belum Kembali', 'bg-primary-subtle text-primary border-primary-subtle'],
                                    };
                                @endphp
                                <tr>
                                    <td class="fw-semibold text-dark">{{ $item->suratJalan->referensiLabel() }}</td>
                                    <td>
                                        {{-- Peminjaman langsung (tanpa Project) tidak punya halaman
                                             detail Surat Jalan yang valid - tampilkan teks saja. --}}
                                        @if($item->suratJalan->isPeminjamanLangsung())
                                            {{ $item->suratJalan->nomor }}
                                        @else
                                            <a href="{{ route('surat-jalan.show', $item->suratJalan->id) }}" class="text-decoration-none">
                                                {{ $item->suratJalan->nomor }}
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $item->qty_dipakai }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->suratJalan->tanggal_terbit)->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge {{ $statusBadge[1] }} border px-2 py-1 rounded-pill fw-semibold" style="font-size:.7rem;">{{ $statusBadge[0] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
