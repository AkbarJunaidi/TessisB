{{-- Kartu daftar Ringkasan Keuangan. Variabel: $title, $rows [url, label, sub, value, tone], $total (opsional) --}}
<div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm rounded-3 h-100">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
                <h6 class="fw-bold mb-0">{{ $title }}</h6>
                @isset($total)
                    <span class="small text-muted text-nowrap">{{ $total }}</span>
                @endisset
            </div>
            @forelse($rows as $row)
                <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-top">
                    @if(!empty($row['url']))
                        <a href="{{ $row['url'] }}" class="text-decoration-none text-dark min-w-0">
                    @else
                        <span class="min-w-0">
                    @endif
                        <span class="d-block text-truncate">{{ $row['label'] }}</span>
                        @if(!empty($row['sub']))
                            <span class="d-block text-muted small text-truncate">{{ $row['sub'] }}</span>
                        @endif
                    @if(!empty($row['url']))
                        </a>
                    @else
                        </span>
                    @endif
                    <span class="fw-semibold text-nowrap {{ $row['tone'] ?? '' }}">{{ $row['value'] }}</span>
                </div>
            @empty
                <div class="text-muted small border-top pt-2">Belum ada data.</div>
            @endforelse
        </div>
    </div>
</div>
