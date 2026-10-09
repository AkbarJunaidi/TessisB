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
                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}" class="text-decoration-none">Inventory</a></li>
                <li class="breadcrumb-item active" aria-current="page">Daftar Harga Barang</li>
            </ol>
        </nav>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Barang</div>
                    <div class="fs-4 fw-bold">{{ $summary['total_barang'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Sudah Ada Harga</div>
                    <div class="fs-4 fw-bold">{{ $summary['ada_harga'] }}</div>
                    <div class="text-muted small">dari Pembelian yang diterima</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Biaya Servis</div>
                    <div class="fs-4 fw-bold">{{ \App\Support\Money::formatRupiah($summary['total_servis']) }}</div>
                    <div class="text-muted small">perbaikan yang sudah selesai</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
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
    <div class="card shadow-sm mb-4 border-0 rounded-3 bg-white">
        <div class="card-body">
            <form action="{{ route('inventory.prices.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label for="filterSearch" class="form-label small fw-semibold text-muted">Cari Barang</label>
                    <input type="text" class="form-control" id="filterSearch" name="search"
                           value="{{ $filters['search'] ?? '' }}" placeholder="Nama, brand, atau serial number...">
                </div>
                <div class="col-md-3">
                    <label for="filterTampil" class="form-label small fw-semibold text-muted">Tampilkan</label>
                    <select class="form-select" id="filterTampil" name="filter">
                        <option value="">Semua barang</option>
                        <option value="servis" {{ ($filters['filter'] ?? '') === 'servis' ? 'selected' : '' }}>Pernah diservis</option>
                        <option value="ganti" {{ ($filters['filter'] ?? '') === 'ganti' ? 'selected' : '' }}>Layak dipertimbangkan ganti</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <a href="{{ route('inventory.prices.index') }}" class="btn btn-outline-secondary px-3">Reset</a>
                    <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search me-1"></i>Cari</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar --}}
    <div class="app-panel overflow-hidden">
        <div class="px-3 px-md-4 pt-3 pt-md-4">
            <h6 class="fw-bold mb-3">Semua Harga Barang</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-modern table-stack align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4 py-3">Barang</th>
                        <th class="text-center">Unit</th>
                        <th class="text-end">Harga Beli Terakhir</th>
                        <th class="text-end">Total Biaya Servis</th>
                        <th class="text-center">Servis</th>
                        <th>Unit Termahal Diservis</th>
                        <th class="pe-4">Perbandingan</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($items as $inventory)
                        @php
                            $cost  = $costs[$inventory->id] ?? null;
                            $ratio = $cost ? \App\Services\Inventory\RepairService::replaceRatio($cost) : null;
                            $pct   = $ratio === null ? null : (int) round($ratio * 100);
                        @endphp
                        <tr>
                            <td class="ps-4 py-3" data-label="Barang" style="min-width: 200px;">
                                <a href="{{ route('inventory.show', $inventory) }}" class="text-decoration-none fw-semibold">{{ $inventory->name }}</a>
                                <div class="text-muted small">{{ $inventory->brand ?: '' }} SN {{ $inventory->serial_number }}</div>
                            </td>
                            <td class="text-center" data-label="Unit">{{ $inventory->units_count }}</td>
                            <td class="text-end" data-label="Harga Beli Terakhir">
                                @if($cost && $cost['last_price'] !== null)
                                    <div class="fw-semibold">{{ \App\Support\Money::formatRupiah($cost['last_price']) }}</div>
                                    @if($canPurchase && $cost['last_purchase_id'])
                                        <a href="{{ route('purchases.show', $cost['last_purchase_id']) }}" class="text-decoration-none small">{{ $cost['last_code'] }}</a>
                                    @endif
                                @else
                                    <span class="text-muted">Belum ada</span>
                                @endif
                            </td>
                            <td class="text-end" data-label="Total Biaya Servis">
                                {{ $cost && $cost['servis_count'] > 0 ? \App\Support\Money::formatRupiah($cost['servis_total']) : '-' }}
                            </td>
                            <td class="text-center" data-label="Servis">{{ $cost && $cost['servis_count'] > 0 ? $cost['servis_count'] . 'x' : '-' }}</td>
                            <td data-label="Unit Termahal Diservis">
                                @if($cost && $cost['worst_unit'])
                                    Unit #{{ $cost['worst_unit']['number'] }}
                                    <span class="text-muted">{{ \App\Support\Money::formatRupiah($cost['worst_unit']['total']) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="pe-4" data-label="Perbandingan">
                                @if($pct === null)
                                    <span class="text-muted">{{ $cost && $cost['worst_unit'] ? 'Harga beli belum ada' : '-' }}</span>
                                @elseif($ratio >= \App\Services\Inventory\RepairService::replaceWarnRatio())
                                    <span class="badge-soft-danger px-2 py-1 rounded-pill">{{ $pct }}% - pertimbangkan ganti</span>
                                @elseif($ratio >= \App\Services\Inventory\RepairService::replaceWatchRatio())
                                    <span class="badge-soft-warning px-2 py-1 rounded-pill">{{ $pct }}% - pantau</span>
                                @else
                                    <span class="badge-soft-success px-2 py-1 rounded-pill">{{ $pct }}% - aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
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
        Perbandingan = biaya servis unit paling mahal dibagi harga beli terakhir. Biaya catatan perbaikan yang berisi beberapa unit
        dibagi rata per unit, jadi angkanya perkiraan. Barang yang belum pernah dibeli lewat Pembelian belum punya harga.
    </p>

    <div class="mt-3">
        {{ $items->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
