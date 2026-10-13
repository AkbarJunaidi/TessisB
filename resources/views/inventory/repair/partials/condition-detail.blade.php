{{-- Rincian Kondisi barang untuk satu barang; dimuat lewat fetch dan disisipkan di bawah barisnya. --}}
@php
    $statusClass = ['Diservis' => 'warning', 'Selesai' => 'success'];
@endphp

@forelse($repairs as $repair)
    @php $color = $statusClass[$repair->status] ?? 'secondary'; @endphp
    <div class="border rounded-3 bg-white p-3 mb-2">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 small">
            <div>
                <a href="{{ route('inventory.repairs.show', $repair) }}" class="fw-semibold text-decoration-none">{{ $repair->code }}</a>
                <span class="text-muted ms-1">{{ $repair->tanggal_masuk->format('d M Y') }} di {{ $repair->tempat_nama }}</span>
            </div>
            <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} border border-{{ $color }}-subtle px-2 py-1 fw-medium">{{ $repair->status }}</span>
        </div>
        @include('inventory.repair.partials.unit-groups', ['items' => $repair->items, 'repair' => $repair])
    </div>
@empty
    <div class="text-muted small py-2">Belum ada rincian servis untuk barang ini.</div>
@endforelse

@if($repairs->count() >= $limit)
    <div class="text-muted small mb-2">Menampilkan {{ $limit }} surat perbaikan terbaru.</div>
@endif

<div class="d-flex flex-wrap gap-3 small">
    <a href="{{ route('inventory.show', $inventory) }}" class="text-decoration-none">Detail barang</a>
    @if($canPrice)
        <a href="{{ route('inventory.prices.index', ['search' => $inventory->name]) }}" class="text-decoration-none">Harga dan biaya servis</a>
    @endif
</div>
