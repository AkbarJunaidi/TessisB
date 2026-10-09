@extends('layouts.app')

@section('title', 'Perbaikan Barang')

@section('content')
@php
    $canManage = auth()->user()->hasPermission('inventory', 'manage_repairs');
    $canContact = auth()->user()->hasPermission('kontak', 'view');
    $statusClass = [
        'Diservis'   => 'warning',
        'Selesai'    => 'success',
        'Dibatalkan' => 'secondary',
    ];
@endphp

<div class="container-fluid px-4 py-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Perbaikan Barang</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}" class="text-decoration-none">Inventory</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Perbaikan Barang</li>
                </ol>
            </nav>
        </div>

        @if($canManage)
            <a href="{{ route('inventory.repairs.create') }}" class="btn btn-primary px-3">
                <i class="bi bi-plus-lg me-1"></i> Catat Perbaikan
            </a>
        @endif
    </div>

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Sedang Diservis</div>
                    <div class="fs-4 fw-bold">{{ $stats['aktif'] }}</div>
                    <div class="text-muted small">catatan perbaikan berjalan</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Unit di Tempat Servis</div>
                    <div class="fs-4 fw-bold">{{ $stats['unit_aktif'] }}</div>
                    <div class="text-muted small">berstatus Perbaikan</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Lewat Estimasi</div>
                    <div class="fs-4 fw-bold {{ $stats['terlambat'] > 0 ? 'text-danger' : '' }}">{{ $stats['terlambat'] }}</div>
                    <div class="text-muted small">belum diambil dari tempat servis</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Biaya Bulan Ini</div>
                    <div class="fs-4 fw-bold">{{ \App\Support\Money::formatRupiah($stats['biaya_bulan_ini']) }}</div>
                    <div class="text-muted small">perbaikan yang selesai bulan ini</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm mb-4 border-0 rounded-3 bg-white">
        <div class="card-body">
            <form action="{{ route('inventory.repairs.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label for="filterSearch" class="form-label small fw-semibold text-muted">Cari</label>
                    <input type="text" class="form-control" id="filterSearch" name="search"
                           value="{{ $filters['search'] ?? '' }}" placeholder="Kode, nama barang, atau tempat servis...">
                </div>
                <div class="col-md-3">
                    <label for="filterStatus" class="form-label small fw-semibold text-muted">Status</label>
                    <select class="form-select" id="filterStatus" name="status">
                        <option value="">Semua status</option>
                        @foreach(['Diservis', 'Selesai', 'Dibatalkan'] as $status)
                            <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <a href="{{ route('inventory.repairs.index') }}" class="btn btn-outline-secondary px-3">Reset</a>
                    <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search me-1"></i>Cari</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar --}}
    <div class="app-panel overflow-hidden">
        <div class="px-3 px-md-4 pt-3 pt-md-4">
            <h6 class="fw-bold mb-3">Semua Perbaikan</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-modern table-stack align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4 py-3">Kode</th>
                        <th class="py-3">Barang</th>
                        <th class="py-3">Tempat Servis</th>
                        <th class="py-3">Diantar</th>
                        <th class="py-3">Estimasi / Selesai</th>
                        <th class="py-3 text-end">Biaya</th>
                        <th class="py-3">Status</th>
                        <th class="py-3 text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($repairs as $repair)
                        @php
                            $color = $statusClass[$repair->status] ?? 'secondary';
                            $summary = $repair->items->groupBy('inventory_id')->map(function ($group) {
                                $numbers = $group->map(fn ($i) => '#' . ($i->unit->unit_number ?? '?'))->implode(', ');
                                return ($group->first()->inventory->name ?? 'Barang') . ' (' . $numbers . ')';
                            });
                        @endphp
                        <tr>
                            <td class="ps-4 py-3 fw-semibold" data-label="Kode">{{ $repair->code }}</td>
                            <td data-label="Barang" style="min-width: 200px;">
                                @foreach($summary as $line)
                                    <div>{{ $line }}</div>
                                @endforeach
                            </td>
                            <td data-label="Tempat Servis" style="min-width: 160px;">
                                <div class="fw-semibold">{{ $repair->tempat_nama }}</div>
                                @if($repair->vendor && $canContact)
                                    <a href="{{ route('contacts.show', $repair->vendor) }}" class="text-decoration-none small">
                                        <i class="bi bi-person-lines-fill me-1"></i>{{ $repair->vendor->name }}
                                    </a>
                                @endif
                            </td>
                            <td data-label="Diantar">
                                {{ $repair->tanggal_masuk->format('d M Y') }}
                                @if($repair->diantar_oleh)
                                    <div class="text-muted small">oleh {{ $repair->diantar_oleh }}</div>
                                @endif
                            </td>
                            <td data-label="Estimasi / Selesai">
                                @if($repair->status === 'Selesai')
                                    {{ $repair->tanggal_selesai?->format('d M Y') }}
                                    @if($repair->diambil_oleh)
                                        <div class="text-muted small">diambil {{ $repair->diambil_oleh }}</div>
                                    @endif
                                @elseif($repair->estimasi_selesai)
                                    <span class="{{ $repair->isOverdue() ? 'text-danger fw-semibold' : '' }}">{{ $repair->estimasi_selesai->format('d M Y') }}</span>
                                    @if($repair->isOverdue())
                                        <div class="text-danger small">Lewat estimasi</div>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end" data-label="Biaya">
                                {{ $repair->biaya !== null ? \App\Support\Money::formatRupiah($repair->biaya) : '-' }}
                            </td>
                            <td data-label="Status">
                                <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} border border-{{ $color }}-subtle px-2 py-2 fw-medium">{{ $repair->status }}</span>
                            </td>
                            <td class="pe-4 text-center cell-block" data-label="Aksi">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('inventory.repairs.show', $repair) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Lihat Detail" aria-label="Lihat detail perbaikan {{ $repair->code }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-tools fs-3 d-block mb-2 opacity-50"></i>
                                Belum ada catatan perbaikan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $repairs->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
