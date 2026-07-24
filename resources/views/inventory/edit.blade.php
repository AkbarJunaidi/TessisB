@extends('layouts.app')

@section('title', 'Edit Aset - ' . $inventory->name)

@section('content')
<div class="container-fluid p-0">

    <!-- Header Page & Back Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Edit Inventory Data</h3>
            <p class="text-muted small m-0">Perbarui informasi utama, foto fisik, dan informasi tambahan aset.</p>
        </div>
        <a href="{{ route('inventory.show', $inventory->id) }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 fw-medium">
            <i class="bi bi-arrow-left"></i> Batal / Kembali
        </a>
    </div>

    <!-- Error Validation Alert -->
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center mb-1 fw-bold">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Terdapat kesalahan pada input form:
            </div>
            <ul class="mb-0 ps-4 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('inventory.update', $inventory->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- CARD 1: Informasi Utama -->
        <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title fw-bold text-dark m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle text-primary"></i> Informasi Utama
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Nama Barang -->
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label fw-semibold small text-secondary">Nama Barang <span class="text-danger">*</span></label>
                        <input type="text"
                               name="name"
                               id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $inventory->name) }}"
                               required>
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
                               value="{{ old('serial_number', $inventory->serial_number) }}"
                               required>
                        @error('serial_number')
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Status Barang (Dropdown) & Brand -->
                    <div class="col-12 col-md-6">
                        <label for="status" class="form-label fw-semibold small text-secondary">Status Barang <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            @php $currentStatus = old('status', $inventory->status ?? 'Tersedia'); @endphp
                            <option value="Tersedia" {{ $currentStatus == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                            <option value="Dipinjam" {{ $currentStatus == 'Dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                            <option value="Perbaikan" {{ $currentStatus == 'Perbaikan' ? 'selected' : '' }}>Perbaikan</option>
                            <option value="Rusak" {{ $currentStatus == 'Rusak' ? 'selected' : '' }}>Rusak</option>
                            <option value="Hilang" {{ $currentStatus == 'Hilang' ? 'selected' : '' }}>Hilang</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="brand" class="form-label fw-semibold small text-secondary">Brand</label>
                        <input type="text"
                               name="brand"
                               id="brand"
                               class="form-control @error('brand') is-invalid @enderror"
                               placeholder="Contoh: ASUS / Logitech / Generic"
                               maxlength="100"
                               value="{{ old('brand', $inventory->brand) }}">
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
                            <span class="text-muted fw-normal" id="descriptionCounter">0/628</span>
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="3"
                                  maxlength="628"
                                  placeholder="Tambahkan deskripsi atau catatan kondisi fisik barang... (maksimal 628 karakter)">{{ old('description', $inventory->description) }}</textarea>
                        <div class="form-text text-muted small mt-1">
                            <i class="bi bi-info-circle me-1"></i> Maksimal 628 karakter.
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

        <!-- CARD 2: Foto Barang -->
        <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title fw-bold text-dark m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-image text-primary"></i> Foto Barang
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <!-- Preview Gambar Fisik Saat Ini -->
                    <div class="col-12 col-md-4 mb-3 mb-md-0 text-center">
                        <label class="form-label fw-semibold small text-secondary d-block text-start mb-2">Foto Saat Ini</label>
                        @if($inventory->image)
                            <div class="p-2 bg-light rounded border border-dashed d-inline-block">
                                <img src="{{ asset('storage/' . $inventory->image) }}" alt="Foto {{ $inventory->name }}" class="img-thumbnail img-fluid" style="max-height: 160px;">
                                <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i>Gambar Terpasang</small>
                            </div>
                        @else
                            <div class="p-4 bg-light rounded border border-dashed text-muted">
                                <i class="bi bi-image opacity-25 d-block mb-1" style="font-size: 2.5rem;"></i>
                                <span class="small d-block">Belum ada foto fisik</span>
                            </div>
                        @endif
                    </div>

                    <!-- Input Unggah / Ganti Foto -->
                    <div class="col-12 col-md-8">
                        <label for="image" class="form-label fw-semibold small text-secondary">Ganti / Unggah Gambar Baru</label>
                        <input type="file"
                               name="image"
                               id="image"
                               class="form-control @error('image') is-invalid @enderror"
                               accept="image/jpeg,image/png,image/jpg,image/webp">
                        <div class="form-text text-muted small mt-1">
                            <i class="bi bi-info-circle me-1"></i> *Kosongkan jika tidak ingin mengubah foto. Format: <strong>jpeg, png, jpg, webp</strong>. Maksimal: <strong>5 MB</strong>.
                        </div>
                        @error('image')
                            <div class="invalid-feedback d-block">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
                            </div>
                        @enderror

                        @if($inventory->image)
                            <div class="form-check text-danger mt-3">
                                <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image" value="1">
                                <label class="form-check-label fw-medium small" for="remove_image">
                                    Hapus foto fisik saat ini
                                </label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: Informasi Tambahan -->
        @php
            $hasExistingAttrs = ($inventory->attributes && $inventory->attributes->count() > 0);
            $useAttributesDefault = old('use_attributes', $hasExistingAttrs ? '1' : '0');
        @endphp
        <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title fw-bold text-dark m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-sliders text-primary"></i> Informasi Tambahan
                </h5>
                <p class="text-muted small mb-0 mt-1">Maksimal 7 baris informasi.</p>
            </div>
            <div class="card-body p-4">
                <!-- Toggle Gunakan Informasi Tambahan -->
                <div class="mb-4">
                    <label class="form-label fw-semibold small text-secondary d-block mb-2">Gunakan Informasi Tambahan?</label>

                    <input type="hidden" name="use_attributes" id="use_attributes_hidden" value="{{ $useAttributesDefault }}">

                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               role="switch"
                               id="use_attr_toggle"
                               style="width: 2.75em; height: 1.5em;"
                               {{ $useAttributesDefault == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-medium text-dark ms-2" for="use_attr_toggle">
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
                                    <th style="width: 45%;">Nama Informasi</th>
                                    <th style="width: 45%;">Nilai</th>
                                    <th class="text-center" style="width: 10%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="attributesBody">
                                @if($hasExistingAttrs)
                                    @foreach($inventory->attributes as $index => $attr)
                                        <tr>
                                            <td>
                                                <input type="text"
                                                       name="attributes[{{ $index }}][name]"
                                                       class="form-control form-control-sm"
                                                       maxlength="40"
                                                       value="{{ old("attributes.{$index}.name", $attr->attribute_name) }}"
                                                       placeholder="Contoh: Processor / RAM / Panjang">
                                            </td>
                                            <td>
                                                <input type="text"
                                                       name="attributes[{{ $index }}][value]"
                                                       class="form-control form-control-sm"
                                                       maxlength="100"
                                                       value="{{ old("attributes.{$index}.value", $attr->attribute_value) }}"
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
                    <button type="button" class="btn btn-primary w-100 fw-medium py-2" id="btnAddAttribute">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Informasi
                    </button>
                    <small class="text-danger d-none ms-2" id="attrLimitWarning">Maksimal 7 baris informasi tambahan.</small>
                </div>
            </div>
        </div>

        <!-- Form Submit & Cancel Actions -->
        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="{{ route('inventory.show', $inventory->id) }}" class="btn btn-light px-4 fw-medium">Batal</a>
            <button type="submit" class="btn btn-primary px-4 fw-medium shadow-sm">
                <i class="bi bi-check-circle-fill me-1"></i> Simpan Perubahan
            </button>
        </div>

    </form>

</div>

<!-- JavaScript Vanilla Dynamic Attribute Key-Value Builder -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const useToggle = document.getElementById('use_attr_toggle');
        const useHidden = document.getElementById('use_attributes_hidden');
        const useLabel = document.getElementById('use_attr_label');
        const container = document.getElementById('attributesContainer');
        const attributesBody = document.getElementById('attributesBody');
        const btnAdd = document.getElementById('btnAddAttribute');
        const limitWarning = document.getElementById('attrLimitWarning');
        const MAX_ATTR_ROWS = 7;

        // Toggle visibility container + sinkronkan hidden input & label berdasarkan switch
        function toggleContainer() {
            const isOn = useToggle.checked;
            useHidden.value = isOn ? '1' : '0';
            useLabel.textContent = isOn ? 'Ya' : 'Tidak';

            if (isOn) {
                container.classList.remove('d-none');
            } else {
                container.classList.add('d-none');
            }
        }

        useToggle.addEventListener('change', toggleContainer);

        // Menambah Baris Atribut Baru
        let rowIndex = attributesBody.querySelectorAll('tr').length;

        // Batasi jumlah baris agar tabel Informasi Tambahan tetap muat 1 halaman saat dicetak ke PDF
        function updateAddButtonState() {
            const currentRows = attributesBody.querySelectorAll('tr').length;
            const limitReached = currentRows >= MAX_ATTR_ROWS;
            btnAdd.disabled = limitReached;
            limitWarning.classList.toggle('d-none', !limitReached);
        }

        btnAdd.addEventListener('click', function() {
            if (attributesBody.querySelectorAll('tr').length >= MAX_ATTR_ROWS) {
                updateAddButtonState();
                return;
            }

            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <input type="text" name="attributes[${rowIndex}][name]" class="form-control form-control-sm" maxlength="40" placeholder="Contoh: Processor / RAM / Panjang">
                </td>
                <td>
                    <input type="text" name="attributes[${rowIndex}][value]" class="form-control form-control-sm" maxlength="100" placeholder="Contoh: Ryzen 7 / 16 GB / 5 Meter">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Hapus Baris">
                        <i class="bi bi-trash"></i> Hapus
                    </button>
                </td>
            `;
            attributesBody.appendChild(newRow);
            rowIndex++;
            updateAddButtonState();
        });

        // Hapus Baris Atribut (Event Delegation)
        attributesBody.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.btn-remove-row');
            if (removeBtn) {
                const tr = removeBtn.closest('tr');
                if (tr) {
                    tr.remove();
                }
                updateAddButtonState();
            }
        });

        updateAddButtonState();

        // Counter karakter Deskripsi Barang
        const descriptionField = document.getElementById('description');
        const descriptionCounter = document.getElementById('descriptionCounter');
        if (descriptionField && descriptionCounter) {
            function updateDescriptionCounter() {
                descriptionCounter.textContent = descriptionField.value.length + '/628';
            }
            descriptionField.addEventListener('input', updateDescriptionCounter);
            updateDescriptionCounter();
        }
    });
</script>
@endsection
