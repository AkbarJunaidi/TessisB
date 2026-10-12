@extends('layouts.app')

@section('title', 'Daftar Harga Barang')

@section('content')
@php
    $canPurchase = auth()->user()->hasPermission('purchase', 'view');
@endphp

<div class="container-fluid px-4 py-3">

    <div class="mb-4">
        <h3 class="fw-bold text-dark m-0">Daftar Harga Barang</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}" class="text-decoration-none">Inventaris</a></li>
                <li class="breadcrumb-item active" aria-current="page">Daftar Harga Barang</li>
            </ol>
        </nav>
    </div>

    @error('harga_manual')
        <div class="alert alert-danger small">{{ $message }}</div>
    @enderror

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Barang</div>
                    <div class="fs-4 fw-bold">{{ $summary['total_barang'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Sudah Ada Harga</div>
                    <div class="fs-4 fw-bold">{{ $summary['ada_harga'] }}</div>
                    <div class="text-muted small">dari Pembelian atau harga manual</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Layak Dipertimbangkan Ganti</div>
                    <div class="fs-4 fw-bold {{ $summary['layak_ganti'] > 0 ? 'text-danger' : '' }}">{{ $summary['layak_ganti'] }}</div>
                    <div class="text-muted small">servis unit &ge; {{ \App\Models\AppSetting::int('repair_warn_percent') }}% harga beli</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card filter-card mb-4">
        <div class="card-body">
            <form action="{{ route('inventory.prices.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md">
                    <label for="filterSearch" class="form-label">Cari</label>
                    <div class="search-field">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" id="filterSearch" name="search"
                               value="{{ $filters['search'] ?? '' }}" placeholder="Nama, brand, atau serial number...">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label for="filterTampil" class="form-label">Tampilkan</label>
                    <select class="form-select" id="filterTampil" name="filter">
                        <option value="">Semua barang</option>
                        <option value="servis" {{ ($filters['filter'] ?? '') === 'servis' ? 'selected' : '' }}>Pernah diservis</option>
                        <option value="kosong" {{ ($filters['filter'] ?? '') === 'kosong' ? 'selected' : '' }}>Belum ada harga</option>
                        <option value="ganti" {{ ($filters['filter'] ?? '') === 'ganti' ? 'selected' : '' }}>Layak dipertimbangkan ganti</option>
                    </select>
                </div>
                <div class="col-6 col-md-auto filter-actions">
                    <a href="{{ route('inventory.prices.index') }}" class="btn btn-outline-secondary px-3">Reset</a>
                    <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search me-1"></i>Cari</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar --}}
    <div class="app-panel overflow-hidden">
        <div class="px-3 px-md-4 pt-3 pt-md-4 mb-3">
            <h6 class="fw-bold m-0">Semua Harga Barang</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-modern table-stack align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4 py-3">Foto</th>
                        <th>Barang</th>
                        <th class="text-center">Unit</th>
                        <th class="text-end">Harga Beli Terakhir</th>
                        <th class="text-end">Rata-rata Servis per Unit</th>
                        <th class="text-center">Servis</th>
                        <th>Penanda</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($items as $inventory)
                        @php
                            $cost  = $costs[$inventory->id] ?? ['last_price' => null, 'last_date' => null, 'last_code' => null, 'last_purchase_id' => null, 'price_source' => null, 'servis_total' => 0.0, 'servis_count' => 0, 'servis_estimasi' => false, 'worst_unit' => null, 'units' => []];
                            $ratio = $cost ? \App\Services\Inventory\RepairService::replaceRatio($cost) : null;
                            $pct   = $ratio === null ? null : (int) round($ratio * 100);
                        @endphp
                        <tr>
                            <td class="ps-4 py-3" data-label="Foto">
                                @if($inventory->image)
                                    <img src="{{ asset('storage/' . $inventory->image) }}"
                                         alt="Foto {{ $inventory->name }}"
                                         class="rounded-3 border u-w-56px u-h-46px u-of-cover">
                                @else
                                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center text-muted border u-w-56px u-h-46px u-fs-0p7rem">
                                        <i class="bi bi-image opacity-50"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 u-minw-200px" data-label="Barang">
                                <a href="{{ route('inventory.show', $inventory) }}" class="text-decoration-none fw-semibold">{{ $inventory->name }}</a>
                                <div class="text-muted small">{{ $inventory->brand ?: '' }} SN {{ $inventory->serial_number }}</div>
                            </td>
                            <td class="text-center" data-label="Unit">{{ $inventory->units_count }}</td>
                            <td class="text-end" data-label="Harga Beli Terakhir">
                                @if($cost['last_price'] !== null)
                                    <div class="fw-semibold">{{ \App\Support\Money::formatRupiah($cost['last_price']) }}</div>
                                    @if($cost['last_date'])
                                        <div class="text-muted small">{{ $cost['last_date']->translatedFormat('d M Y') }}</div>
                                    @endif
                                    @if(($cost['price_source'] ?? null) === 'manual')
                                        <span class="badge-soft-secondary px-2 py-1 rounded-pill small">Manual</span>
                                    @elseif($canPurchase && $cost['last_purchase_id'])
                                        <a href="{{ route('purchases.show', $cost['last_purchase_id']) }}" class="text-decoration-none small">{{ $cost['last_code'] }}</a>
                                    @endif
                                @else
                                    <span class="text-muted">Belum ada</span>
                                @endif
                            </td>
                            <td class="text-end" data-label="Rata-rata Servis per Unit">
                                @if($cost['units'])
                                    {{ \App\Support\Money::formatRupiah(\App\Services\Inventory\RepairService::averagePerUnit($cost)) }}
                                    <div class="text-muted small">dari {{ count($cost['units']) }} unit{{ $cost['servis_estimasi'] ? ', perkiraan' : '' }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center" data-label="Servis">
                                @if($cost['servis_count'] > 0)
                                    <a href="{{ route('inventory.repairs.index', ['tampil' => 'riwayat', 'inventory_id' => $inventory->id]) }}"
                                       class="text-decoration-none" title="Lihat riwayat servis">{{ $cost['servis_count'] }}x</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td data-label="Penanda">
                                @if($pct === null)
                                    <span class="text-muted">{{ $cost['worst_unit'] ? 'Harga beli belum ada' : '-' }}</span>
                                @else
                                    @php
                                        [$tone, $label] = $ratio >= \App\Services\Inventory\RepairService::replaceWarnRatio()
                                            ? ['danger', 'Pertimbangkan ganti']
                                            : ($ratio >= \App\Services\Inventory\RepairService::replaceWatchRatio() ? ['warning', 'Pantau'] : ['success', 'Aman']);
                                    @endphp
                                    <span class="badge-soft-{{ $tone }} px-2 py-1 rounded-pill text-nowrap"
                                          title="Unit #{{ $cost['worst_unit']['number'] }} diservis {{ \App\Support\Money::formatRupiah($cost['worst_unit']['total']) }} ({{ $pct }}% dari harga beli)">{{ $label }}</span>
                                @endif
                            </td>
                            <td class="text-center pe-4" data-label="Aksi">
                                @if($canEdit)
                                    <button type="button" class="btn btn-sm btn-light border"
                                            data-bs-toggle="modal" data-bs-target="#priceModal"
                                            data-action="{{ route('inventory.prices.update', $inventory) }}"
                                            data-name="{{ $inventory->name }}"
                                            data-price="{{ $cost['last_price'] !== null ? (int) round($cost['last_price']) : '' }}"
                                            aria-label="Tambah atau perbarui harga {{ $inventory->name }}"
                                            title="Tambah atau perbarui harga">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-currency-dollar fs-3 d-block mb-2 opacity-50"></i>
                                Tidak ada barang yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0">
        Semua angka per unit. Harga yang berlaku adalah yang paling baru: Pembelian diterima atau harga manual dari tombol Aksi,
        sesuai tanggalnya. Rata-rata servis dihitung dari biaya servis tiap unit yang pernah diservis. Penanda membandingkan biaya
        servis unit yang paling mahal dengan harga beli per unit; arahkan kursor ke penanda untuk melihat angkanya. Biaya perbaikan lama
        yang dicatat per catatan dibagi rata ke tiap unit dan ditandai perkiraan. Angka pada Servis membuka riwayat servis barang itu.
    </p>

    <div class="mt-3">
        {{ $items->links('pagination.app') }}
    </div>
</div>

@if($canEdit)
    {{-- Modal isi / ubah harga manual --}}
    <div class="modal fade" id="priceModal" tabindex="-1" aria-labelledby="priceModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="" id="priceForm" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title" id="priceModalTitle">Tambah Harga Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="fw-semibold mb-3" id="priceModalName"></div>
                    <label for="hargaManual" class="form-label fw-semibold small text-secondary">Harga Beli per Unit</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="numeric" name="harga_manual" id="hargaManual" class="form-control" placeholder="Contoh: 150.000" autocomplete="off">
                    </div>
                    <div class="form-text">
                        Disimpan sebagai harga manual dengan tanggal hari ini. Kosongkan untuk menghapus harga manual.
                        Harga manual berlaku sampai ada Pembelian yang lebih baru.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="priceSubmit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ \App\Support\AppAsset::url('js/inventory/harga-manual.js') }}"></script>
    <script src="{{ \App\Support\AppAsset::url('js/inventory/harga-price.js') }}"></script>
@endif
@endsection
