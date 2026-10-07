<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $inventory->name }} - Detail Inventory</title>

    {{-- Halaman publik hasil scan QR Report: berdiri sendiri (tanpa layout admin), hanya baca. --}}
    {{ \App\Support\VendorAsset::style('bootstrap-css') }}
    {{ \App\Support\VendorAsset::style('bootstrap-icons') }}

    <style>
        body { background-color: #f4f6f9; }
        .scan-wrap { max-width: 960px; }
        .scan-photo { max-height: 240px; width: 100%; object-fit: contain; }
        .scan-value { word-break: break-word; }
    </style>
</head>
<body>

    @php
        $badgeMap = collect(\App\Support\InventoryStatus::colors())->map(fn ($c) => "bg-{$c}-subtle text-{$c} border-{$c}-subtle")->all();
        $status      = $inventory->display_status ?? 'Tersedia';
        $statusClass = $badgeMap[$status] ?? 'bg-success-subtle text-success border-success-subtle';
    @endphp

    <!-- Top bar: tombol login mengarah ke detail barang ini (guest otomatis dialihkan ke halaman login dulu) -->
    <header class="bg-white border-bottom sticky-top">
        <div class="scan-wrap container d-flex align-items-center justify-content-between gap-2 py-2 px-3">
            <div class="lh-sm">
                <div class="fw-bold text-dark">Detail Inventory</div>
                <div class="text-muted small d-none d-sm-block">Sistem Informasi Manajemen</div>
            </div>
            <a href="{{ route('inventory.show', $inventory) }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 fw-medium flex-shrink-0">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </a>
        </div>
    </header>

    <main class="scan-wrap container px-3 py-3 py-md-4">

        <!-- Judul + status -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <h4 class="fw-bold text-dark m-0 scan-value">{{ $inventory->name }}</h4>
            <span class="badge border {{ $statusClass }} px-3 py-2 rounded-pill fw-semibold flex-shrink-0">
                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>{{ strtoupper($status) }}
            </span>
        </div>

        <!-- Foto + Deskripsi -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 rounded-3 bg-white h-100">
                    <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                        <h6 class="fw-bold text-dark m-0">Foto Fisik Barang</h6>
                    </div>
                    <div class="card-body p-4 d-flex align-items-center justify-content-center">
                        @if($inventory->image)
                            <img src="{{ asset('storage/' . $inventory->image) }}" alt="Foto {{ $inventory->name }}" class="img-fluid rounded scan-photo">
                        @else
                            <div class="text-center py-5 text-muted border border-dashed rounded w-100 bg-light">
                                <i class="bi bi-image opacity-25 d-block mb-2" style="font-size: 3rem;"></i>
                                <span class="small fw-medium">Foto barang belum diunggah</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-8">
                <div class="card shadow-sm border-0 rounded-3 bg-white h-100">
                    <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                        <h6 class="fw-bold text-dark m-0">Deskripsi Barang</h6>
                    </div>
                    <div class="card-body p-4">
                        @if(!empty($inventory->description))
                            <p class="text-dark small mb-0 scan-value" style="white-space: pre-line; line-height: 1.6;">{{ $inventory->description }}</p>
                        @else
                            <p class="text-muted fst-italic small mb-0">Belum ada deskripsi.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Informasi Identitas Aset -->
        <div class="card shadow-sm border-0 rounded-3 bg-white mb-3">
            <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                <h6 class="fw-bold text-dark m-0">Informasi Identitas Aset</h6>
            </div>
            <div class="card-body p-4">
                <table class="table table-borderless table-sm align-middle small mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted py-2" style="width: 40%;">Nama Barang</td>
                            <td class="fw-bold text-dark py-2 scan-value">{{ $inventory->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-2">Serial Number</td>
                            <td class="py-2"><span class="font-monospace fw-semibold text-secondary scan-value">{{ $inventory->serial_number }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted py-2">Status Barang</td>
                            <td class="py-2"><span class="badge border {{ $statusClass }} px-2 py-1">{{ strtoupper($status) }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted py-2">Jumlah Barang</td>
                            <td class="py-2">
                                <span class="fw-semibold">{{ $inventory->quantity_total }} unit total</span>
                                <span class="text-success">({{ $inventory->qty_available }} tersedia</span>,
                                <span class="text-secondary">{{ $inventory->qty_in_use }} sedang dipakai)</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted py-2">Brand</td>
                            <td class="fw-bold text-dark py-2 scan-value">{{ $inventory->brand ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-2">Tanggal Input</td>
                            <td class="text-dark py-2">{{ $inventory->created_at ? $inventory->created_at->format('d F Y H:i') . ' WIB' : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-2">Terakhir Update</td>
                            <td class="text-dark py-2">{{ $inventory->updated_at ? $inventory->updated_at->format('d F Y H:i') . ' WIB' : '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Informasi Tambahan -->
        @if($inventory->attributes->isNotEmpty())
            <div class="card shadow-sm border-0 rounded-3 bg-white mb-3">
                <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                    <h6 class="fw-bold text-dark m-0">Informasi Tambahan</h6>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless table-sm align-middle small mb-0">
                        <tbody>
                            @foreach($inventory->attributes as $attr)
                                <tr>
                                    <td class="text-muted py-2" style="width: 40%;"><i class="bi bi-tag text-primary me-2"></i>{{ $attr->attribute_name }}</td>
                                    <td class="fw-semibold text-dark py-2 scan-value">{{ $attr->attribute_value }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Status Unit Fisik (hanya nomor dan status unit) -->
        <div class="card shadow-sm border-0 rounded-3 bg-white mb-3">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="fw-bold m-0">Status Unit Fisik</h6>
                    <span class="text-muted small">{{ $inventory->qty_available }} dari {{ $inventory->quantity_total }} unit bisa dipinjam sekarang</span>
                </div>
                <div class="row g-2">
                    @forelse($inventory->units as $unit)
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <div class="border rounded-3 p-2 text-center {{ $badgeMap[$unit->display_status] ?? 'bg-light text-dark border' }}">
                                <div class="fw-bold">#{{ $unit->unit_number }}</div>
                                <div class="small">{{ $unit->display_status }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-muted small">Belum ada data unit.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <p class="text-center text-muted small mb-0">
            <i class="bi bi-shield-check me-1"></i>
            Halaman ini hanya bisa dibuka lewat QR Code resmi dari sistem inventory.
        </p>

    </main>

</body>
</html>
