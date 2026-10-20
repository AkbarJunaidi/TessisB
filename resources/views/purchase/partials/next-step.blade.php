@php
    use App\Models\Purchase;

    $events = $purchase->histories->pluck('event')->all();
    $isPaid = $purchase->payment_status === Purchase::PAYMENT_PAID;
    $terminal = match ($purchase->status) {
        Purchase::STATUS_REJECTED  => 'Ditolak',
        Purchase::STATUS_CANCELLED => 'Dibatalkan',
        default                    => null,
    };

    // Posisi terakhir yang dicapai di jalur barang; pembelian dibatalkan dibaca dari riwayat.
    $goodsAt = match ($purchase->status) {
        Purchase::STATUS_RECEIVED, Purchase::STATUS_PARTIAL => 4,
        Purchase::STATUS_ORDERED  => 3,
        Purchase::STATUS_APPROVED => 2,
        Purchase::STATUS_SUBMITTED, Purchase::STATUS_REJECTED => 1,
        Purchase::STATUS_DRAFT    => 0,
        default => in_array('ordered', $events, true) ? 3 : (in_array('approved', $events, true) ? 2 : (in_array('submitted', $events, true) ? 1 : 0)),
    };

    $moneyLabels = $purchase->isPayFirst() ? ['Dana diminta', 'Dana di finance', 'Dibayar'] : ['Belum dibayar', 'Dibayar'];
    $moneyAt = $isPaid
        ? count($moneyLabels) - 1
        : ($purchase->fund_status === Purchase::FUND_AT_FINANCE ? 1 : 0);

    $tracks = [
        'Barang' => ['labels' => ['Draft', 'Diajukan', 'Disetujui', 'Dipesan', $purchase->status === Purchase::STATUS_PARTIAL ? 'Diterima sebagian' : 'Diterima'], 'at' => $goodsAt, 'finished' => $purchase->status === Purchase::STATUS_RECEIVED, 'badge' => $terminal],
        'Uang'   => ['labels' => $moneyLabels, 'at' => $moneyAt, 'finished' => $isPaid, 'badge' => null],
    ];
@endphp

<div class="card border-0 shadow-sm rounded-3 mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-signpost-split me-2"></i>Tahapan Pembelian</h6>

        @foreach($nextSteps as $step)
            @php
                $track = $tracks[$step['track']];
            @endphp
            <div class="{{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                <div class="d-flex align-items-start gap-3 mb-2">
                    <span class="text-muted small text-nowrap"><i class="bi {{ $step['track'] === 'Uang' ? 'bi-cash-coin' : 'bi-box-seam' }} me-1"></i>{{ $step['track'] }}</span>
                    <div class="d-flex align-items-center flex-wrap gap-2 small">
                        @foreach($track['labels'] as $i => $label)
                            @php
                                $state = $track['finished'] || $i < $track['at'] ? 'done' : ($i === $track['at'] ? 'current' : 'todo');
                            @endphp
                            @if($i > 0)
                                <i class="bi bi-chevron-right text-muted"></i>
                            @endif
                            <span class="d-inline-flex align-items-center gap-1 {{ $state === 'todo' ? 'text-muted' : '' }} {{ $state === 'current' ? 'fw-semibold' : '' }}">
                                @if($state === 'done')
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                @elseif($state === 'current')
                                    <i class="bi {{ $track['badge'] ? 'bi-x-circle-fill text-danger' : 'bi-record-circle-fill text-primary' }}"></i>
                                @else
                                    <i class="bi bi-circle"></i>
                                @endif
                                {{ $label }}
                            </span>
                        @endforeach
                        @if($track['badge'])
                            <span class="badge bg-danger">{{ $track['badge'] }}</span>
                        @endif
                    </div>
                </div>

                <div class="small">
                    <span class="fw-semibold">{{ $step['owner'] }}</span>: {{ $step['text'] }}
                    @if($step['mine'])
                        <span class="badge bg-primary ms-1">Giliran Anda</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
