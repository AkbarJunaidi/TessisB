@extends('layouts.app')

@section('title', 'Catat Perbaikan')

@section('content')
@php
    $selectedUnits = collect(old('unit_ids', $preselected))->map(fn ($id) => (int) $id)->all();

    // Barang dengan unit terlewat/segera servis tampil paling atas.
    $groups = $inventories->map(function ($inventory) use ($selectedUnits) {
        $states = $inventory->units->map(fn ($unit) => $inventory->servisStatusFor($unit)['state'] ?? 'ok');

        return [
            'inventory' => $inventory,
            'late'      => $states->filter(fn ($s) => $s === 'terlambat')->count(),
            'soon'      => $states->filter(fn ($s) => $s === 'segera')->count(),
            'selected'  => $inventory->units->whereIn('id', $selectedUnits)->count(),
        ];
    })->sortBy([
        fn ($a, $b) => ($b['late'] > 0) <=> ($a['late'] > 0),
        fn ($a, $b) => ($b['soon'] > 0) <=> ($a['soon'] > 0),
        fn ($a, $b) => strcasecmp($a['inventory']->name, $b['inventory']->name),
    ])->values();
@endphp

<div class="container-fluid px-4 py-3">

    <div class="mb-4">
        <h3 class="fw-bold text-dark m-0">Catat Perbaikan</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}" class="text-decoration-none">Inventaris</a></li>
                <li class="breadcrumb-item"><a href="{{ route('inventory.repairs.index') }}" class="text-decoration-none">Perbaikan Barang</a></li>
                <li class="breadcrumb-item active" aria-current="page">Catat</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('inventory.repairs.store') }}" method="POST" id="repairForm">
        @csrf

        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <ul class="mb-0 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 1. Tempat Servis --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Tempat Servis</h6>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="vendor_id" class="form-label">Vendor dari Kontak</label>
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
                        @if(auth()->user()->hasPermission('kontak', 'create'))
                            <div class="form-text">
                                <a href="{{ route('contacts.create', ['type' => 'vendor']) }}" target="_blank" rel="noopener">Tambah vendor baru</a>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label for="tempat_nama" class="form-label">Nama Tempat <span class="text-danger">*</span></label>
                        <input type="text" name="tempat_nama" id="tempat_nama" class="form-control" maxlength="150"
                               value="{{ old('tempat_nama') }}" placeholder="Contoh: Bengkel Jaya Elektronik" required>
                        <div class="form-text">Tampil sebagai "Perbaikan di ..." pada unit.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="tempat_alamat" class="form-label">Alamat</label>
                        <input type="text" name="tempat_alamat" id="tempat_alamat" class="form-control" maxlength="500"
                               value="{{ old('tempat_alamat') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Pengantaran --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Pengantaran</h6>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="tanggal_masuk" class="form-label">Tanggal Diantar <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_masuk" id="tanggal_masuk" class="form-control"
                               value="{{ old('tanggal_masuk', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label for="estimasi_selesai" class="form-label">Estimasi Selesai</label>
                        <input type="date" name="estimasi_selesai" id="estimasi_selesai" class="form-control"
                               value="{{ old('estimasi_selesai') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="diantar_oleh" class="form-label">Diantar Oleh</label>
                        <input type="text" name="diantar_oleh" id="diantar_oleh" class="form-control" maxlength="150"
                               list="peopleList" value="{{ old('diantar_oleh') }}" placeholder="Pilih akun atau ketik nama">
                    </div>
                    <div class="col-12">
                        <label for="keluhan" class="form-label">Keluhan / Kerusakan</label>
                        <textarea name="keluhan" id="keluhan" rows="2" class="form-control" maxlength="1000"
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

        {{-- 3. Unit yang diservis --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="fw-bold mb-0">Unit yang Bermasalah / Diservis <span class="text-danger">*</span></h6>
                    <span class="text-muted small"><span id="selectedCount">0</span> unit dipilih</span>
                </div>

                <input type="search" id="inventoryFilter" class="form-control mb-3" placeholder="Cari nama barang...">

                <div id="inventoryGroups">
                    @forelse($groups as $group)
                        @php
                            $inventory = $group['inventory'];
                            $headTone = $group['late'] > 0
                                ? 'border-danger-subtle bg-danger-subtle'
                                : ($group['soon'] > 0 ? 'border-warning-subtle bg-warning-subtle' : 'bg-white');
                            $open = $group['selected'] > 0;
                        @endphp
                        <div class="mb-2 inventory-group" data-name="{{ strtolower($inventory->name) }}" data-inventory="{{ $inventory->id }}">
                            <button type="button" class="btn w-100 text-start border rounded-3 px-3 py-2 d-flex align-items-center gap-2 {{ $headTone }} {{ $open ? '' : 'collapsed' }}"
                                    data-bs-toggle="collapse" data-bs-target="#unitGroup{{ $inventory->id }}"
                                    aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="unitGroup{{ $inventory->id }}">
                                <i class="bi bi-chevron-down small"></i>
                                <span class="fw-semibold small flex-grow-1">
                                    {{ $inventory->name }}
                                    <span class="text-muted fw-normal">SN {{ $inventory->serial_number }}</span>
                                </span>
                                @if($group['late'] > 0)
                                    <span class="badge text-bg-danger">{{ $group['late'] }} terlewat</span>
                                @endif
                                @if($group['soon'] > 0)
                                    <span class="badge text-bg-warning">{{ $group['soon'] }} segera</span>
                                @endif
                                <span class="badge text-bg-primary group-selected {{ $open ? '' : 'd-none' }}">{{ $group['selected'] }} dipilih</span>
                                <span class="badge text-bg-light border">{{ $inventory->units->count() }} unit</span>
                            </button>

                            <div class="collapse {{ $open ? 'show' : '' }}" id="unitGroup{{ $inventory->id }}">
                                <div class="d-flex flex-wrap gap-2 pt-2 px-1">
                                    @foreach($inventory->units as $unit)
                                        @php
                                            $servis = $inventory->servisStatusFor($unit);
                                            $state = $servis['state'] ?? 'ok';
                                            $tone = match(true) {
                                                $state === 'terlambat' => 'border-danger-subtle bg-danger-subtle',
                                                $state === 'segera'    => 'border-warning-subtle bg-warning-subtle',
                                                $unit->status !== 'Tersedia' => 'border-secondary-subtle bg-light',
                                                default => 'bg-white',
                                            };
                                        @endphp
                                        <label class="border rounded-3 px-3 py-2 d-flex align-items-center gap-2 {{ $tone }} u-cur-pointer">
                                            <input type="checkbox" class="form-check-input m-0 unit-check" name="unit_ids[]"
                                                   value="{{ $unit->id }}"
                                                   data-inventory="{{ $inventory->id }}"
                                                   data-inventory-name="{{ $inventory->name }}"
                                                   data-number="{{ $unit->unit_number }}"
                                                   data-state="{{ $state }}"
                                                   @checked(in_array($unit->id, $selectedUnits, true))>
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
                        </div>
                    @empty
                        <p class="text-muted small m-0">Tidak ada unit yang bisa dikirim servis. Unit yang sedang dipinjam, hilang, atau sudah diservis tidak ditampilkan.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 4. Ringkasan & perbandingan harga --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Ringkasan</h6>
                <div id="summaryEmpty" class="text-muted small">Pilih unit di atas untuk melihat ringkasan dan perbandingan harganya.</div>
                <div id="summaryList"></div>
                <div id="summaryTotal" class="d-none d-flex justify-content-end align-items-center gap-3 border-top pt-3 mt-3">
                    <span class="text-muted">Total dikirim servis</span>
                    <span class="fw-bold fs-5" id="summaryTotalText"></span>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('inventory.repairs.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" id="btnSubmitRepair">
                <i class="bi bi-tools me-1"></i> Simpan Perbaikan
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const repairCosts = @json($repairCosts ?? []);
    const warnRatio = {{ \App\Services\Inventory\RepairService::replaceWarnRatio() }};
    const rupiah = (v) => 'Rp ' + Math.round(v).toLocaleString('id-ID');
    const esc = (t) => { const d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; };

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

    const cell = (label, value) => '<div class="col-6 col-md-3"><div class="text-muted">' + label + '</div><div class="fw-semibold">' + value + '</div></div>';

    // Render satu barang beserta unit yang bisa dipilih untuk diservis, lengkap dengan harga terakhir.
    function renderGroup(inventoryId, name, units) {
        const info = repairCosts[inventoryId];
        const price = info && info.last_price !== null ? info.last_price : null;
        const numbers = units.map(function (u) { return '#' + u.number; }).join(', ');
        const late = units.filter(function (u) { return u.state === 'terlambat'; }).length;
        const soon = units.filter(function (u) { return u.state === 'segera'; }).length;

        let html = '<div class="border rounded-3 p-3 mb-2"><div class="fw-semibold mb-2">' + esc(name)
            + ' <span class="text-muted fw-normal small">Unit ' + esc(numbers) + ' (' + units.length + ' unit)</span></div>'
            + '<div class="rounded-3 border bg-light p-2 small"><div class="row g-2">'
            + cell('Harga beli terakhir', price !== null
                ? rupiah(price) + ' <span class="text-muted fw-normal">(' + esc(info.last_code) + ')</span>' : '-')
            + cell('Total biaya servis sebelumnya', info && info.servis_count > 0
                ? rupiah(info.servis_total) + ' <span class="text-muted fw-normal">(' + info.servis_count + 'x servis)</span>' : '-')
            + cell('Kondisi jadwal servis', (late || soon)
                ? (late ? late + ' terlewat' : '') + (late && soon ? ', ' : '') + (soon ? soon + ' segera' : '') : 'Belum jatuh tempo')
            + '</div>';

        // Biaya servis kumulatif tiap unit yang dikirim, dibanding harga beli terakhir.
        const lines = [];
        units.forEach(function (u) {
            const spent = info && info.units && info.units[u.id] ? info.units[u.id].total : 0;
            if (spent <= 0) return;
            const ratio = price ? spent / price : null;
            const pct = ratio === null ? '' : ' (' + Math.round(ratio * 100) + '% dari harga beli)';
            if (ratio !== null && ratio >= warnRatio) {
                lines.push('<div class="text-danger fw-semibold mt-2"><i class="bi bi-exclamation-triangle me-1"></i>Unit #' + u.number
                    + ' sudah menghabiskan ' + rupiah(spent) + ' untuk servis' + pct + '. Pertimbangkan beli baru daripada servis lagi.</div>');
            } else {
                lines.push('<div class="text-muted mt-2">Unit #' + u.number + ' sebelumnya sudah diservis senilai ' + rupiah(spent) + pct + '</div>');
            }
        });

        return html + lines.join('') + '</div></div>';
    }

    // Perbarui jumlah unit terpilih per grup dan total biaya.
    function updateSummary() {
        const checked = Array.from(document.querySelectorAll('.unit-check:checked'));
        document.getElementById('selectedCount').textContent = checked.length;

        document.querySelectorAll('.inventory-group').forEach(function (g) {
            const n = g.querySelectorAll('.unit-check:checked').length;
            const badge = g.querySelector('.group-selected');
            badge.textContent = n + ' dipilih';
            badge.classList.toggle('d-none', n === 0);
        });

        const groups = {};
        checked.forEach(function (c) {
            const id = c.dataset.inventory;
            (groups[id] = groups[id] || { name: c.dataset.inventoryName, units: [] }).units.push({
                id: c.value, number: parseInt(c.dataset.number, 10), state: c.dataset.state,
            });
        });

        const ids = Object.keys(groups);
        document.getElementById('summaryEmpty').classList.toggle('d-none', ids.length > 0);
        document.getElementById('summaryTotal').classList.toggle('d-none', ids.length === 0);
        document.getElementById('summaryList').innerHTML = ids.map(function (id) {
            return renderGroup(id, groups[id].name, groups[id].units);
        }).join('');
        document.getElementById('summaryTotalText').textContent = checked.length + ' unit dari ' + ids.length + ' barang';
    }

    document.querySelectorAll('.unit-check').forEach(function (c) { c.addEventListener('change', updateSummary); });
    updateSummary();

    document.getElementById('inventoryFilter').addEventListener('input', function () {
        const kw = this.value.trim().toLowerCase();
        document.querySelectorAll('.inventory-group').forEach(function (g) {
            g.style.display = g.dataset.name.indexOf(kw) === -1 ? 'none' : '';
        });
    });

    document.getElementById('repairForm').addEventListener('submit', function (e) {
        if (document.querySelectorAll('.unit-check:checked').length === 0) {
            e.preventDefault();
            AppUI.toast('Pilih minimal satu unit yang diservis.');
        }
    });
})();
</script>
@endsection
