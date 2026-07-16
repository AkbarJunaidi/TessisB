@extends('layouts.app')

@section('title', 'Detail Aset - ' . $inventory->name)

@section('content')
<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">View Inventory Detail</h3>
            <p class="text-muted small m-0">Menampilkan rincian berkas, foto fisik, beserta informasi identitas unik barang.</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 fw-medium">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0 rounded-3 bg-white h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold text-dark m-0">Spesifikasi Identitas Aset</h5>
                </div>
                <div class="card-body p-4">

                    <div class="mb-4 text-center bg-light rounded-3 p-3 border border-dashed">
                        <label class="form-label d-block text-start fw-semibold small text-secondary mb-2">Foto Fisik Barang</label>
                        @if($inventory->image)
                            <img src="{{ asset('storage/' . $inventory->image) }}"
                                 alt="Foto {{ $inventory->name }}"
                                 class="img-fluid rounded shadow-sm border"
                                 style="max-height: 280px; object-fit: contain;">
                        @else
                            <div class="py-5 text-muted">
                                <i class="bi bi-image opacity-25 d-block mb-2" style="font-size: 3.5rem;"></i>
                                <span class="small d-block">Tidak ada foto fisik yang dilampirkan untuk barang ini.</span>
                            </div>
                        @endif
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.75rem;">Nama Barang</label>
                            <div class="fs-5 fw-bold text-dark p-2 bg-light rounded border-start border-primary border-3">
                                {{ $inventory->name }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.75rem;">Serial Number</label>
                            <div class="p-2 bg-light rounded border font-monospace fw-semibold text-secondary">
                                <i class="bi bi-hash me-1 text-primary"></i>{{ $inventory->serial_number }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.75rem;">Terdaftar Pada</label>
                            <div class="p-2 bg-light rounded border text-secondary">
                                <i class="bi bi-clock me-1 text-primary"></i>{{ $inventory->created_at->format('d M Y, H:i') }} WIB
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card shadow-sm border-0 rounded-3 bg-white h-100 d-flex flex-column">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold text-dark m-0">Label Kode QR Otorisasi</h5>
                </div>

                <div class="card-body p-4 text-center d-flex flex-column justify-content-center align-items-center flex-grow-1">
                    <div class="bg-white p-3 rounded-3 shadow-sm border mb-4 text-center d-flex align-items-center justify-content-center" style="width: 220px; height: 220px;">
                        @if($inventory->qr_code && file_exists(public_path('storage/' . $inventory->qr_code)))
                            <img src="{{ asset('storage/' . $inventory->qr_code) }}"
                                 alt="QR Code {{ $inventory->serial_number }}"
                                 class="img-fluid"
                                 style="max-width: 100%; height: auto; object-fit: contain;">
                        @else
                            <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted border border-dashed rounded p-2">
                                <i class="bi bi-exclamation-triangle-fill text-warning mb-2" style="font-size: 2.5rem;"></i>
                                <span class="fw-semibold text-secondary" style="font-size: 0.75rem;">QR NOT FOUND</span>
                                <small class="text-muted text-center" style="font-size: 0.65rem;">Berkas QR code fisik belum tersedia.</small>
                            </div>
                        @endif
                    </div>

                    <div class="w-100 px-3">
                        <span class="badge bg-light text-dark border font-monospace fs-6 px-3 py-2 w-100 rounded-3 mb-2">
                            SN: {{ $inventory->serial_number }}
                        </span>
                    </div>
                </div>

                <div class="card-footer bg-light border-top p-3">
                    <div class="d-flex gap-2 mb-2">
                        <!-- Tombol Edit Aset Bawaan -->
                        <a href="{{ route('inventory.edit', $inventory->id) }}" class="btn btn-sm btn-outline-secondary w-50 fw-medium py-2">
                            <i class="bi bi-pencil-square me-1"></i> Edit Aset
                        </a>
                        <!-- Tombol Preview QR Label (Stiker Kecil) Bawaan -->
                        <a href="{{ route('inventory.preview-qr', $inventory->id) }}" target="_blank" class="btn btn-sm btn-outline-primary w-50 fw-medium py-2 d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-qr-code"></i> Preview QR Label
                        </a>
                    </div>

                    <div class="d-flex gap-2 w-100">
                        <!-- FITUR BARU: Preview Report PDF A4 (Inline Stream) -->
                        <a href="{{ route('inventory.preview', $inventory->id) }}" target="_blank" class="btn btn-sm btn-primary w-50 fw-medium py-2 d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-file-earmark-pdf"></i> Preview Report
                        </a>

                        <!-- FITUR BARU: Download Report PDF A4 (Forced Download) -->
                        <a href="{{ route('inventory.download', $inventory->id) }}" class="btn btn-sm btn-dark w-50 fw-medium py-2 d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-download text-success"></i> Download Report
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
