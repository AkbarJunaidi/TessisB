<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $inventory->name }} - Detail Barang</title>

    {{-- Halaman ini SENGAJA berdiri sendiri (bukan @extends layouts.app) -
         diakses tanpa login lewat scan QR, jadi tidak butuh sidebar/topbar
         admin, dan harus tetap ringan & cepat dibuka dari kamera HP. --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">

    <style>
        body {
            background-color: #f4f6f9;
        }
        .scan-photo {
            width: 100%;
            max-height: 260px;
            object-fit: cover;
        }
    </style>
</head>
<body>

    <div class="container py-4" style="max-width: 480px;">

        <div class="text-center mb-3">
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold">
                <i class="bi bi-qr-code-scan me-1"></i> Hasil Scan Barang
            </span>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">

            @if($inventory->image)
                <img src="{{ asset('storage/' . $inventory->image) }}" alt="{{ $inventory->name }}" class="scan-photo">
            @else
                <div class="scan-photo d-flex align-items-center justify-content-center bg-light text-muted">
                    <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                </div>
            @endif

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h4 class="fw-bold mb-0">{{ $inventory->name }}</h4>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-semibold flex-shrink-0 ms-2">
                        {{ strtoupper($inventory->display_status ?? 'TERSEDIA') }}
                    </span>
                </div>

                <div class="font-monospace text-secondary small mb-3">
                    SN: {{ $inventory->serial_number }}
                </div>

                <hr>

                <div class="row g-2 small">

                    @if($inventory->brand)
                        <div class="col-6">
                            <div class="text-muted">Brand</div>
                            <div class="fw-semibold">{{ $inventory->brand }}</div>
                        </div>
                    @endif

                    <div class="col-6">
                        <div class="text-muted">Jumlah Barang</div>
                        <div class="fw-semibold">{{ $inventory->quantity_total }}</div>
                    </div>

                </div>

                @php
                    // Ringkasan status unit fisik, urutan tetap: Tersedia,
                    // Dipinjam, Perbaikan, Rusak, Hilang - HANYA status yang
                    // jumlahnya > 0 yang ditampilkan (sesuai permintaan).
                    $statusOrder = ['Tersedia', 'Dipinjam', 'Perbaikan', 'Rusak', 'Hilang'];
                    $statusCounts = $inventory->units->countBy(fn ($unit) => $unit->display_status);
                    $summaryParts = collect($statusOrder)
                        ->filter(fn ($status) => ($statusCounts[$status] ?? 0) > 0)
                        ->map(fn ($status) => $statusCounts[$status] . ' ' . $status);
                @endphp

                @if($summaryParts->isNotEmpty())
                    <div class="mt-3 pt-3 border-top">
                        <div class="text-muted small mb-1">Status Unit Fisik</div>
                        <div class="fw-semibold">{{ $summaryParts->implode(', ') }}</div>
                    </div>
                @endif

                @if($inventory->description)
                    <div class="mt-3">
                        <div class="text-muted small mb-1">Deskripsi</div>
                        <div>{{ $inventory->description }}</div>
                    </div>
                @endif

                @if($inventory->attributes->isNotEmpty())
                    <div class="mt-3">
                        <div class="text-muted small mb-2">Informasi Tambahan</div>
                        <div class="row g-2 small">
                            @foreach($inventory->attributes as $attribute)
                                <div class="col-6">
                                    <div class="text-muted">{{ $attribute->attribute_name }}</div>
                                    <div class="fw-semibold">{{ $attribute->attribute_value }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        </div>

        <p class="text-center text-muted small mb-0">
            <i class="bi bi-shield-check me-1"></i>
            Halaman ini hanya bisa dibuka lewat QR Code resmi dari sistem inventory.
        </p>

    </div>

</body>
</html>
