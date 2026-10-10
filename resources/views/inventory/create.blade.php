@extends('layouts.app')

@section('title', 'Tambah Barang')

@section('content')
<div class="page-heading">
        <div>
            <h3>Tambah Barang</h3>
            <p>Daftarkan aset barang fisik baru ke dalam sistem digital manajemen.</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    <!-- Error Validation Alert -->
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 rounded-3" role="alert">
            <div class="d-flex align-items-center mb-1 fw-bold">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Terdapat kesalahan pada input form:
            </div>
            <ul class="mb-0 ps-4 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <form action="{{ route('inventory.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- CARD 1: Informasi Utama -->
        <div class="app-panel mb-4">
            <div class="app-panel-header">
                <h5 class="fw-bold text-navy m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle text-primary"></i> Informasi Utama
                </h5>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <!-- Nama Barang -->
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label fw-semibold small text-secondary">Nama Barang <span class="text-danger">*</span></label>
                        <input type="text"
                               name="name"
                               id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="Contoh: Laptop ASUS ROG Strix"
                               value="{{ old('name') }}"
                               required
                               maxlength="100"
                               autofocus>
                        @error('name')
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Serial Number -->
                    <div class="col-12 col-md-6">
                        <label for="serial_number" class="form-label fw-semibold small text-secondary">Serial Number (Nomor Seri Unik) <span class="text-danger">*</span></label>
                        <input type="text"
                               name="serial_number"
                               id="serial_number"
                               class="form-control font-monospace @error('serial_number') is-invalid @enderror"
                               placeholder="Contoh: SN-ROG-2026XYZ"
                               value="{{ old('serial_number') }}"
                               required>
                        @error('serial_number')
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Jumlah Barang (Quantity Total) -->
                    <div class="col-12 col-md-6">
                        <label for="quantity_total" class="form-label fw-semibold small text-secondary">Jumlah Barang (Unit) <span class="text-danger">*</span></label>
                        <input type="number"
                               min="1"
                               name="quantity_total"
                               id="quantity_total"
                               class="form-control @error('quantity_total') is-invalid @enderror"
                               placeholder="Contoh: 3"
                               value="{{ old('quantity_total', 1) }}"
                               required>
                        @error('quantity_total')
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Status Barang (Dropdown) & Brand -->
                    <div class="col-12 col-md-6">
                        <label for="status" class="form-label fw-semibold small text-secondary">Status Barang <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            @php $currentStatus = old('status', 'Tersedia'); @endphp
                            @foreach(\App\Support\InventoryStatus::all() as $statusName)
                                <option value="{{ $statusName }}" {{ $currentStatus == $statusName ? 'selected' : '' }}>{{ $statusName }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="brand" class="form-label fw-semibold small text-secondary">Merek</label>
                        <input type="text"
                               name="brand"
                               id="brand"
                               class="form-control @error('brand') is-invalid @enderror"
                               placeholder="Contoh: ASUS / Logitech / Generic"
                               maxlength="100"
                               value="{{ old('brand') }}">
                        @error('brand')
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Deskripsi Barang -->
                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold small text-secondary d-flex justify-content-between">
                            <span>Deskripsi Barang</span>
                            <span class="text-muted fw-normal" id="descriptionCounter">0/500</span>
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Tambahkan deskripsi atau catatan kondisi fisik barang... (maksimal 500 karakter)">{{ old('description') }}</textarea>
                        <div class="form-text text-muted small mt-1">
                            <i class="bi bi-info-circle me-1"></i> Maksimal 500 karakter agar tetap rapi saat dicetak ke laporan PDF.
                        </div>
                        @error('description')
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Jadwal Servis diletakkan di atas (setelah Informasi Utama), bukan di bawah seperti Informasi Tambahan. --}}
        @include('inventory.partials.service-schedule-fields')

        <!-- CARD 2: Foto Barang -->
        <div class="app-panel mb-4">
            <div class="app-panel-header">
                <h5 class="fw-bold text-navy m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-image text-primary"></i> Foto Barang
                </h5>
            </div>
            <div class="p-4">
                <div class="mb-3">
                    <label for="image" class="form-label fw-semibold small text-secondary">Unggah Gambar Barang</label>
                    <input type="file"
                           name="image"
                           id="image"
                           class="form-control @error('image') is-invalid @enderror"
                           accept="image/jpeg,image/png,image/jpg,image/webp">
                    <div class="form-text text-muted small mt-1">
                        <i class="bi bi-info-circle me-1"></i> *Opsional (Boleh dikosongkan). Format berkas: <strong>jpeg, png, jpg, webp</strong>. Maksimal ukuran: <strong>5 MB</strong>.
                    </div>
                    @error('image')
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- CARD 3: Informasi Tambahan -->
        @php
            $useAttributesDefault = old('use_attributes', '0');
        @endphp
        <div class="app-panel mb-4">
            <div class="app-panel-header">
                <div>
                    <h5 class="fw-bold text-navy m-0 d-flex align-items-center gap-2">
                        <i class="bi bi-sliders text-primary"></i> Informasi Tambahan
                    </h5>
                    <p class="text-muted small mb-0 mt-1">Maksimal 7 baris informasi.</p>
                </div>
            </div>
            <div class="p-4">
                <!-- Toggle Gunakan Informasi Tambahan -->
                <div class="mb-4">
                    <label class="form-label fw-semibold small text-secondary d-block mb-2">Gunakan Informasi Tambahan?</label>

                    <input type="hidden" name="use_attributes" id="use_attributes_hidden" value="{{ $useAttributesDefault }}">

                    <div class="form-check form-switch">
                        <input class="form-check-input u-w-2p75em u-h-1p5em"
                               type="checkbox"
                               role="switch"
                               id="use_attr_toggle"
                              
                               {{ $useAttributesDefault == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-medium text-dark" for="use_attr_toggle">
                            <span id="use_attr_label">{{ $useAttributesDefault == '1' ? 'Ya' : 'Tidak' }}</span>
                        </label>
                    </div>
                </div>

                <!-- Container Dynamic Key-Value Table -->
                <div id="attributesContainer" class="{{ $useAttributesDefault == '1' ? '' : 'd-none' }}">
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle" id="attributesTable">
                            <thead class="table-light small text-uppercase text-secondary">
                                <tr>
                                    <th class="u-w-45pct">Nama Informasi</th>
                                    <th class="u-w-45pct">Nilai</th>
                                    <th class="text-center u-w-10pct">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="attributesBody">
                                @if(old('attributes'))
                                    @foreach(old('attributes') as $index => $attr)
                                        <tr>
                                            <td>
                                                <input type="text"
                                                       name="attributes[{{ $index }}][name]"
                                                       class="form-control form-control-sm"
                                                       maxlength="40"
                                                       value="{{ $attr['name'] ?? '' }}"
                                                       placeholder="Contoh: Processor / RAM / Panjang">
                                            </td>
                                            <td>
                                                <input type="text"
                                                       name="attributes[{{ $index }}][value]"
                                                       class="form-control form-control-sm"
                                                       maxlength="100"
                                                       value="{{ $attr['value'] ?? '' }}"
                                                       placeholder="Contoh: Ryzen 7 / 16 GB / 5 Meter">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Hapus Baris">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <!-- Default 1 Baris Kosong jika memilih Ya -->
                                    <tr>
                                        <td>
                                            <input type="text" name="attributes[0][name]" class="form-control form-control-sm" maxlength="40" placeholder="Contoh: Processor / RAM / Panjang">
                                        </td>
                                        <td>
                                            <input type="text" name="attributes[0][value]" class="form-control form-control-sm" maxlength="100" placeholder="Contoh: Ryzen 7 / 16 GB / 5 Meter">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Hapus Baris">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Tombol Tambah Informasi -->
                    <button type="button" class="btn btn-sm btn-outline-primary fw-medium" id="btnAddAttribute">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Informasi
                    </button>
                    <small class="text-danger d-none ms-2" id="attrLimitWarning">Maksimal 7 baris informasi tambahan.</small>
                </div>
            </div>
        </div>

        <!-- Form Submit & Cancel Actions -->
        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="{{ route('inventory.index') }}" class="btn btn-light px-4">Batal</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Simpan Barang
            </button>
        </div>

    </form>

<!-- JavaScript Vanilla Dynamic Attribute Key-Value Builder -->
<script src="{{ \App\Support\AppAsset::url('js/inventory/create.js') }}"></script>
@endsection
