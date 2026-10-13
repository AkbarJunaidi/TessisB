{{--
    Unit satu barang dalam satu surat perbaikan, dikelompokkan per hasil dan catatan.
    Dipakai di Detail Perbaikan dan di rincian Kondisi barang.
    Variabel: $items (item perbaikan satu barang, relasi unit dimuat), $repair.
--}}
@php
    $order  = ['Tersedia' => 0, 'Rusak' => 1];
    $groups = $items
        ->groupBy(fn ($item) => ($item->hasil ?? 'aktif') . '|' . ($item->catatan ?? ''))
        ->sortBy(fn ($group) => $order[$group->first()->hasil] ?? 2);
@endphp

@foreach($groups as $group)
    @php
        $first   = $group->first();
        $count   = $group->count();
        $numbers = $group->map(fn ($item) => $item->unit?->unit_number)->filter()->sort()->map(fn ($n) => '#' . $n)->implode(', ');
        $costed  = $group->filter(fn ($item) => $item->biaya !== null);
        $before  = $group->pluck('status_sebelum')->filter()->unique()->implode(', ');

        if ($first->hasil === 'Tersedia') {
            [$tone, $label] = ['success', "{$count} unit berhasil"];
        } elseif ($first->hasil === 'Rusak') {
            [$tone, $label] = ['danger', "{$count} unit gagal"];
        } elseif ($repair->status === 'Dibatalkan') {
            [$tone, $label] = ['secondary', "{$count} unit dikembalikan ke status sebelumnya"];
        } else {
            [$tone, $label] = ['warning', "{$count} unit sedang diservis"];
        }
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 small {{ $loop->last ? '' : 'mb-2' }}">
        <div>
            <span class="badge-soft-{{ $tone }} px-2 py-1 rounded-pill">{{ $label }}</span>
            <span class="ms-1">{{ $numbers }}</span>
            @if($before)
                <span class="text-muted ms-1">sebelumnya {{ $before }}</span>
            @endif
            @if($first->catatan)
                <div class="text-muted mt-1 u-ws-pre-line">{{ $first->catatan }}</div>
            @endif
        </div>
        <div class="text-end">
            @if($costed->isNotEmpty())
                <span class="fw-semibold">{{ \App\Support\Money::formatRupiah($costed->sum('biaya')) }}</span>
                @if($costed->contains(fn ($item) => $item->biaya_estimasi))
                    <div class="text-muted small">perkiraan</div>
                @endif
            @else
                <span class="text-muted">-</span>
            @endif
        </div>
    </div>
@endforeach
