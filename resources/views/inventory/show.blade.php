@extends('layouts.app')

@section('title', 'Detail Inventory - ' . $inventory->name)

@section('content')
<div class="container-fluid p-0 pb-5 pb-lg-0">

    <!-- Header Page & Back Button (Hanya Tampil di Desktop) -->
    <div class="d-none d-md-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                {{ $inventory->name }}
                @switch($inventory->display_status)
                    @case('Tersedia')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.65rem;"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>TERSEDIA</span>
                        @break
                    @case('Dipinjam')
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.65rem;"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>DIPINJAM</span>
                        @break
                    @case('Perbaikan')
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.65rem;"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>PERBAIKAN</span>
                        @break
                    @case('Rusak')
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.65rem;"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>RUSAK</span>
                        @break
                    @case('Hilang')
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.65rem;"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>HILANG</span>
                        @break
                    @default
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.65rem;">{{ strtoupper($inventory->display_status ?? 'TERSEDIA') }}</span>
                @endswitch
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
    <div class="d-md-none d-flex align-items-center justify-content-between mb-3 px-1">
        <div>
            <h4 class="fw-bold text-dark m-0 d-flex align-items-center gap-2 flex-wrap">
                {{ $inventory->name }}
                @switch($inventory->display_status)
                    @case('Tersedia')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.6rem;">TERSEDIA</span>
                        @break
                    @case('Dipinjam')
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.6rem;">DIPINJAM</span>
                        @break
                    @case('Perbaikan')
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.6rem;">PERBAIKAN</span>
                        @break
                    @case('Rusak')
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.6rem;">RUSAK</span>
                        @break
                    @case('Hilang')
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.6rem;">HILANG</span>
                        @break
                    @default
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 0.6rem;">{{ strtoupper($inventory->display_status ?? 'TERSEDIA') }}</span>
                @endswitch
            </h4>
            <p class="text-muted small m-0">Informasi lengkap aset barang</p>
        </div>
        @if(auth()->user()->hasPermission('inventory', 'edit'))
        <a href="{{ route('inventory.edit', $inventory->id) }}" class="btn btn-sm btn-outline-primary fw-medium rounded-2">
            <i class="bi bi-pencil me-1"></i> Edit Aset
        </a>
        @endif
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

                <!-- Badge Status & Edit Aset Row -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        @switch($inventory->display_status)
                            @case('Tersedia')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> TERSEDIA</span>
                                @break
                            @case('Dipinjam')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> DIPINJAM</span>
                                @break
                            @case('Perbaikan')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill fw-semibold"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> PERBAIKAN</span>
                                @break
                            @case('Rusak')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fw-semibold"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> RUSAK</span>
                                @break
                            @case('Hilang')
                                <span class="badge bg-dark-subtle text-dark border border-dark-subtle px-3 py-2 rounded-pill fw-semibold"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> HILANG</span>
                                @break
                            @default
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> {{ strtoupper($inventory->status ?? 'TERSEDIA') }}</span>
                        @endswitch
                    </div>
                </div>

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

            <!-- SISI KIRI (8 KOLOM): Informasi Identitas Aset & Informasi Tambahan -->
            <div class="col-12 col-lg-8">
                <div class="row g-4">
                    <div class="{{ $hasAttributes ? 'col-6' : 'col-12' }}">
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
                        <div class="col-6">
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
                <div class="card shadow-sm border-0 rounded-3 bg-white">
                    <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                        <h6 class="fw-bold text-dark m-0">QR Code Aset</h6>
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
            </div>
        </div>
    </div>

    <!-- CARD BARU: Status Unit Fisik (ringkasan status per-unit, terintegrasi dengan Surat Jalan) -->
    <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0">Status Unit Fisik</h6>
                <span class="text-muted small">{{ $inventory->qty_available }} dari {{ $inventory->quantity_total }} unit bisa dipinjam sekarang</span>
            </div>
            <div class="row g-2">
                @forelse($inventory->units as $unit)
                    @php
                        $badgeClass = match($unit->display_status) {
                            'Tersedia'  => 'bg-success-subtle text-success border-success-subtle',
                            'Dipinjam'  => 'bg-primary-subtle text-primary border-primary-subtle',
                            'Perbaikan' => 'bg-warning-subtle text-warning border-warning-subtle',
                            'Rusak'     => 'bg-danger-subtle text-danger border-danger-subtle',
                            'Hilang'    => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                            default     => 'bg-light text-dark border',
                        };
                    @endphp
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="border rounded-3 p-2 text-center {{ $badgeClass }}">
                            <div class="fw-bold">#{{ $unit->unit_number }}</div>
                            <div class="small">{{ $unit->display_status }}</div>
                            @if($unit->surat_jalan_item_id)
                                <div class="small text-truncate" style="font-size:.65rem;">{{ $unit->suratJalanItem->suratJalan->nomor ?? '' }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted small m-0">Belum ada data unit fisik untuk barang ini.</p>
                @endforelse
            </div>
        </div>
    </div>

</div>


<!-- FIXED BOTTOM ACTION BAR UNTUK MOBILE (SEPERTI CONTOH GAMBAR) -->
<div class="d-md-none fixed-bottom bg-white border-top shadow-lg py-2 px-3">
    <div class="row g-2 text-center">
        <div class="col-3">
            <a href="{{ route('inventory.preview-qr', $inventory->id) }}" target="_blank" class="btn btn-light border w-100 py-2 rounded-3 text-primary d-flex flex-column align-items-center justify-content-center">
                <i class="bi bi-qr-code-scan fs-5 mb-1"></i>
                <span style="font-size: 0.65rem;" class="fw-semibold text-dark">Preview QR</span>
            </a>
        </div>
        <div class="col-3">
            <a href="{{ route('inventory.preview', $inventory->id) }}" target="_blank" class="btn btn-light border w-100 py-2 rounded-3 text-primary d-flex flex-column align-items-center justify-content-center">
                <i class="bi bi-file-earmark-pdf fs-5 mb-1"></i>
                <span style="font-size: 0.65rem;" class="fw-semibold text-dark">Preview Report</span>
            </a>
        </div>
        <div class="col-3">
            <a href="{{ route('inventory.download', $inventory->id) }}" class="btn btn-light border w-100 py-2 rounded-3 text-success d-flex flex-column align-items-center justify-content-center">
                <i class="bi bi-download fs-5 mb-1"></i>
                <span style="font-size: 0.65rem;" class="fw-semibold text-dark">Download Report</span>
            </a>
        </div>
        <div class="col-3">
            <a href="{{ route('inventory.index') }}" class="btn btn-light border w-100 py-2 rounded-3 text-secondary d-flex flex-column align-items-center justify-content-center">
                <i class="bi bi-arrow-left fs-5 mb-1"></i>
                <span style="font-size: 0.65rem;" class="fw-semibold text-dark">Kembali</span>
            </a>
        </div>
    </div>
</div>
@endsection
