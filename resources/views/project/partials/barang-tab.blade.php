{{-- Partial: Tab Barang
     Variabel yang dibutuhkan saat di-include: $project (dengan relasi suratJalans.items.inventory sudah di-load) --}}

@php
    $barangUsage = $project->suratJalans
        ->flatMap(fn ($sj) => $sj->items)
        ->groupBy('inventory_id')
        ->map(function ($rows) {
            $inventory = $rows->first()->inventory;
            return [
                'inventory'   => $inventory,
                'qty_dipakai' => $rows->sum('qty_dipakai'),
                'qty_kembali' => $rows->sum('qty_dikembalikan'),
            ];
        });
@endphp

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Barang yang Dipakai pada Project Ini</h6>

        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>Barang</th>
                        <th class="text-center">Qty Dipakai</th>
                        <th class="text-center">Sudah Dikembalikan</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ketersediaan Stok Saat Ini</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($barangUsage as $usage)
                        @php $sisa = $usage['qty_dipakai'] - $usage['qty_kembali']; @endphp
                        <tr>
                            <td>{{ $usage['inventory']->name ?? '-' }}</td>
                            <td class="text-center">{{ $usage['qty_dipakai'] }}</td>
                            <td class="text-center">{{ $usage['qty_kembali'] }}</td>
                            <td class="text-center">
                                <span class="badge {{ $sisa > 0 ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">
                                    {{ $sisa > 0 ? 'Masih Dipakai ('.$sisa.')' : 'Sudah Kembali' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($usage['inventory'])
                                    {{ $usage['inventory']->qty_available }} / {{ $usage['inventory']->quantity_total }} unit tersedia
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada barang yang dipakai pada project ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
