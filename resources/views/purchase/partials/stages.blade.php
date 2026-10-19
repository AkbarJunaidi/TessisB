{{-- Penanda tahap satu jalur (barang atau uang): selesai, sedang di sini, belum. $steps dari PurchaseNextStep::stages(). --}}
<ol class="list-unstyled d-flex flex-wrap align-items-center gap-2 mb-0 small">
    @foreach($steps as $step)
        @php
            [$icon, $class] = match ($step['state']) {
                'done'    => ['bi-check-circle-fill', 'text-success'],
                'current' => ['bi-arrow-right-circle-fill', 'text-primary fw-semibold'],
                'stop'    => ['bi-x-circle-fill', 'text-danger fw-semibold'],
                default   => ['bi-circle', 'text-muted'],
            };
        @endphp
        <li class="d-inline-flex align-items-center gap-1 {{ $class }}" @if($step['state'] === 'current') aria-current="step" @endif>
            <i class="bi {{ $icon }}"></i>
            <span>{{ $step['label'] }}</span>
            @if($step['hint'])
                <span class="text-muted fw-normal">({{ $step['hint'] }})</span>
            @endif
        </li>
        @unless($loop->last)
            <li class="text-muted" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
        @endunless
    @endforeach
</ol>
