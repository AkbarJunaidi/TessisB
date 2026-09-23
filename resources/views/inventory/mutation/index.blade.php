@extends('layouts.app')

@section('title', 'Mutasi Aset')

{{-- Label & warna badge per event_type - dipakai di tabel bawah. Untuk
     status_berubah, labelnya diambil dari status_after ($m->status_after),
     bukan dari event_type-nya sendiri, supaya lebih gampang dibaca
     ("Rusak" bukan "Status Berubah"). --}}
@php
    $eventLabels = [
        'ditambahkan'   => ['Ditambahkan', 'success'],
        'dihapus'       => ['Dihapus', 'secondary'],
        'dipinjam'      => ['Dipinjam', 'primary'],
        'dikembalikan'  => ['Dikembalikan', 'info'],
    ];
    $statusLabels = [
        'Rusak'      => 'danger',
        'Perbaikan'  => 'warning',
        'Hilang'     => 'dark',
        'Tersedia'   => 'success',
    ];
@endphp

@section('content')
<div class="container-fluid px-4 py-3">

    <div class="mb-4">
        <h3 class="fw-bold text-dark m-0">Mutasi Aset</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}" class="text-decoration-none">Inventory</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mutasi Aset</li>
            </ol>
        </nav>
    </div>

    <div class="card shadow-sm mb-4 border-0 rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-funnel me-2"></i>Filter Mutasi</h6>
        </div>
        <div class="card-body bg-light bg-opacity-25">
            <form action="{{ route('inventory.mutasi') }}" method="GET">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label for="search" class="form-label small fw-semibold text-muted">Nama Barang</label>
                        <input type="text" class="form-control small" id="search" name="search"
                               value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama barang...">
                    </div>

                    <div class="col-md-4">
                        <label for="from" class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                        <input type="date" class="form-control small" id="from" name="from" value="{{ $filters['from'] ?? '' }}">
                    </div>

                    <div class="col-md-4">
                        <label for="to" class="form-label small fw-semibold text-muted">Sampai Tanggal</label>
                        <input type="date" class="form-control small" id="to" name="to" value="{{ $filters['to'] ?? '' }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-muted d-block">Jenis Kejadian</label>
                        @php $selectedTypes = $filters['event_types'] ?? []; @endphp
                        <div class="d-flex flex-wrap gap-3">
                            @foreach(['ditambahkan' => 'Ditambahkan', 'dihapus' => 'Dihapus', 'dipinjam' => 'Dipinjam', 'dikembalikan' => 'Dikembalikan', 'status_berubah' => 'Status Berubah (Rusak/Perbaikan/Hilang)'] as $value => $label)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="event_types[]" value="{{ $value }}"
                                           id="type_{{ $value }}" {{ in_array($value, $selectedTypes) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="type_{{ $value }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('inventory.mutasi') }}" class="btn btn-sm btn-outline-secondary px-3 fw-medium">Reset</a>
                    <button type="submit" class="btn btn-sm btn-primary px-3 fw-medium">
                        <i class="bi bi-search me-1"></i>Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-journal-text me-2"></i>Riwayat Mutasi</h6>
            <span class="badge bg-secondary text-white fw-medium rounded-pill px-3 py-1.5" style="font-size: 0.8rem;">
                {{ $mutations->total() }} Baris
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-stack align-middle mb-0 text-nowrap">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Tanggal</th>
                            <th>Barang</th>
                            <th>Jumlah</th>
                            <th>Kejadian</th>
                            <th>Referensi</th>
                            <th class="pe-4">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="small text-dark">
                        @forelse($mutations as $m)
                            <tr>
                                <td class="ps-4 py-3 text-secondary fw-medium" data-label="Tanggal">
                                    <i class="bi bi-calendar-event me-2"></i>{{ $m->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td data-label="Barang" class="fw-semibold">
                                    {{ $m->inventory->name ?? '(Barang dihapus)' }}
                                </td>
                                <td data-label="Jumlah">{{ $m->qty }} unit</td>
                                <td data-label="Kejadian">
                                    @if($m->event_type === 'status_berubah')
                                        @php $color = $statusLabels[$m->status_after] ?? 'secondary'; @endphp
                                        <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} border border-{{ $color }}-subtle px-2 py-2 fw-medium" style="font-size: 0.8rem;">
                                            {{ $m->status_after }}
                                        </span>
                                    @else
                                        @php [$label, $color] = $eventLabels[$m->event_type] ?? [$m->event_type, 'secondary']; @endphp
                                        <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} border border-{{ $color }}-subtle px-2 py-2 fw-medium" style="font-size: 0.8rem;">
                                            {{ $label }}
                                        </span>
                                    @endif
                                </td>
                                <td data-label="Referensi">
                                    {{-- Peminjaman langsung (tanpa Project) tidak punya halaman
                                         detail Surat Jalan yang valid - tampilkan teks saja. --}}
                                    @if($m->suratJalan && !$m->suratJalan->isPeminjamanLangsung())
                                        <a href="{{ route('surat-jalan.show', $m->suratJalan) }}" class="text-decoration-none">
                                            {{ $m->suratJalan->nomor }}
                                        </a>
                                    @elseif($m->suratJalan)
                                        {{ $m->suratJalan->nomor }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="pe-4" data-label="Keterangan">
                                    {{ $m->keterangan ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    Belum ada mutasi yang cocok dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($mutations->hasPages())
            <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
                {{ $mutations->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>
@endsection
