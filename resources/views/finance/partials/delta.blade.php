{{-- Perubahan dibanding periode sebelumnya. Variabel: $value (persen atau null), $upIsGood (bool) --}}
@if($value === null)
    <span class="text-muted small">Tanpa pembanding</span>
@else
    @php
        $up   = $value >= 0;
        $good = $up === $upIsGood;
    @endphp
    <span class="small fw-semibold {{ $good ? 'text-success' : 'text-danger' }}">
        <i class="bi {{ $up ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
        {{ number_format(abs($value), 1, ',', '.') }}%
    </span>
    <span class="text-muted small">vs periode sebelumnya</span>
@endif
