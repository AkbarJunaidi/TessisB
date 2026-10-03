@extends('layouts.app')

@section('title', 'Catat Perbaikan')

@section('content')
@php
    $selectedUnits = collect(old('unit_ids', $preselected))->map(fn ($id) => (int) $id)->all();
@endphp

<div class="container-fluid px-4 py-3">

    <div class="mb-4">
        <h3 class="fw-bold text-dark m-0">Catat Perbaikan</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}" class="text-decoration-none">Inventory</a></li>
                <li class="breadcrumb-item"><a href="{{ route('inventory.repairs.index') }}" class="text-decoration-none">Perbaikan Barang</a></li>
                <li class="breadcrumb-item active" aria-current="page">Catat</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('inventory.repairs.store') }}" method="POST" id="repairForm">
        @csrf

        @if($errors->any())
            <div class="alert alert-danger small">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3">
            {{-- Tempat servis & pengantaran --}}
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm border-0 rounded-3 mb-3">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">Tempat Servis</h6>

                        <div class="mb-3">
                            <label for="vendor_id" class="form-label fw-medium text-secondary">Vendor dari Kontak</label>
                            <select name="vendor_id" id="vendor_id" class="form-select">
                                <option value="">- Tanpa vendor / tulis manual -</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}"
                                            data-name="{{ $vendor->company ?: $vendor->name }}"
                                            data-address="{{ $vendor->address }}"
                                            @selected((string) old('vendor_id') === (string) $vendor->id)>
                                        {{ $vendor->name }}{{ $vendor->company ? ' - ' . $vendor->company : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Hanya kontak bertipe vendor.
                                <a href="{{ route('contacts.create', ['type' => 'vendor']) }}" target="_blank" rel="noopener">Tambah vendor baru</a>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tempat_nama" class="form-label fw-medium text-secondary">Nama Tempat <span class="text-danger">*</span></label>
                            <input type="text" name="tempat_nama" id="tempat_nama" class="form-control" maxlength="150"
                                   value="{{ old('tempat_nama') }}" placeholder="Contoh: Bengkel Jaya Elektronik" required>
                            <div class="form-text">Nama toko, bengkel, atau vendor. Ini yang tampil sebagai "Perbaikan di ..." pada unit.</div>
                        </div>

                        <div class="mb-0">
                            <label for="tempat_alamat" class="form-label fw-medium text-secondary">Alamat</label>
                            <textarea name="tempat_alamat" id="tempat_alamat" rows="2" class="form-control" maxlength="500">{{ old('tempat_alamat') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">Pengantaran</h6>

                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label for="tanggal_masuk" class="form-label fw-medium text-secondary">Tanggal Diantar <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_masuk" id="tanggal_masuk" class="form-control"
                                       value="{{ old('tanggal_masuk', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="estimasi_selesai" class="form-label fw-medium text-secondary">Estimasi Selesai</label>
                                <input type="date" name="estimasi_selesai" id="estimasi_selesai" class="form-control"
                                       value="{{ old('estimasi_selesai') }}">
                            </div>
                            <div class="col-12">
                                <label for="diantar_oleh" class="form-label fw-medium text-secondary">Diantar Oleh</label>
                                <input type="text" name="diantar_oleh" id="diantar_oleh" class="form-control" maxlength="150"
                                       list="peopleList" value="{{ old('diantar_oleh') }}" placeholder="Pilih akun atau ketik nama">
                            </div>
                            <div class="col-12">
                                <label for="keluhan" class="form-label fw-medium text-secondary">Keluhan / Kerusakan</label>
                                <textarea name="keluhan" id="keluhan" rows="3" class="form-control" maxlength="1000"
                                          placeholder="Apa yang perlu diperbaiki atau diservis">{{ old('keluhan') }}</textarea>
                            </div>
                        </div>
                        <datalist id="peopleList">
                            @foreach($people as $person)
                                <option value="{{ $person }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>
            </div>

            {{-- Pilih unit --}}
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h6 class="fw-bold m-0">Unit yang Diservis <span class="text-danger">*</span></h6>
                            <span class="text-muted small"><span id="selectedCount">0</span> unit dipilih</span>
                        </div>

                        <input type="search" id="inventoryFilter" class="form-control mb-3" placeholder="Cari nama barang...">

                        <div id="inventoryGroups">
                            @forelse($inventories as $inventory)
                                <div class="border rounded-3 p-3 mb-2 inventory-group" data-name="{{ strtolower($inventory->name) }}">
                                    <div class="fw-semibold mb-2">
                                        {{ $inventory->name }}
                                        <span class="text-muted small fw-normal">SN {{ $inventory->serial_number }}</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($inventory->units as $unit)
                                            @php
                                                $servis = $inventory->servisStatusFor($unit);
                                                $state = $servis['state'] ?? null;
                                                $tone = match(true) {
                                                    $state === 'terlambat' => 'border-danger-subtle bg-danger-subtle',
                                                    $state === 'segera'    => 'border-warning-subtle bg-warning-subtle',
                                                    $unit->status !== 'Tersedia' => 'border-secondary-subtle bg-light',
                                                    default => 'bg-white',
                                                };
                                            @endphp
                                            <label class="border rounded-3 px-3 py-2 d-flex align-items-center gap-2 {{ $tone }}" style="cursor:pointer;">
                                                <input type="checkbox" class="form-check-input m-0 unit-check" name="unit_ids[]"
                                                       value="{{ $unit->id }}" @checked(in_array($unit->id, $selectedUnits, true))>
                                                <span>
                                                    <span class="fw-semibold">#{{ $unit->unit_number }}</span>
                                                    @if($state === 'terlambat')
                                                        <span class="d-block small text-danger">Servis terlewat</span>
                                                    @elseif($state === 'segera')
                                                        <span class="d-block small text-warning">Segera servis</span>
                                                    @elseif($unit->status !== 'Tersedia')
                                                        <span class="d-block small text-muted">{{ $unit->status }}</span>
                                                    @endif
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small m-0">Tidak ada unit yang bisa dikirim servis. Unit yang sedang dipinjam, hilang, atau sudah diservis tidak ditampilkan.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('inventory.repairs.index') }}" class="btn btn-light px-4 fw-medium">Batal</a>
            <button type="submit" class="btn btn-primary px-4 fw-medium shadow-sm" id="btnSubmitRepair">
                <i class="bi bi-tools me-1"></i> Simpan Perbaikan
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const vendorSelect = document.getElementById('vendor_id');
    const nameInput    = document.getElementById('tempat_nama');
    const addrInput    = document.getElementById('tempat_alamat');
    let autoName = '';

    // Pilih vendor mengisi nama & alamat, tapi tidak menimpa ketikan manual user.
    vendorSelect.addEventListener('change', function () {
        const opt = vendorSelect.options[vendorSelect.selectedIndex];
        if (!opt || !opt.value) return;

        if (nameInput.value.trim() === '' || nameInput.value === autoName) {
            nameInput.value = opt.dataset.name || '';
            autoName = nameInput.value;
        }
        if (addrInput.value.trim() === '' && opt.dataset.address) {
            addrInput.value = opt.dataset.address;
        }
    });

    const counter = document.getElementById('selectedCount');
    const checks  = document.querySelectorAll('.unit-check');
    function updateCount() {
        counter.textContent = document.querySelectorAll('.unit-check:checked').length;
    }
    checks.forEach(function (c) { c.addEventListener('change', updateCount); });
    updateCount();

    document.getElementById('inventoryFilter').addEventListener('input', function () {
        const kw = this.value.trim().toLowerCase();
        document.querySelectorAll('.inventory-group').forEach(function (g) {
            g.style.display = g.dataset.name.indexOf(kw) === -1 ? 'none' : '';
        });
    });

    document.getElementById('repairForm').addEventListener('submit', function (e) {
        if (document.querySelectorAll('.unit-check:checked').length === 0) {
            e.preventDefault();
            alert('Pilih minimal satu unit yang diservis.');
        }
    });
})();
</script>
@endsection
