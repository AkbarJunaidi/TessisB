@php
    use App\Models\Purchase;
    use App\Support\Stepper;

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

    // Tanggal kejadian terakhir per langkah, tampil sebagai keterangan kecil.
    $when = fn (string $event) => optional($purchase->histories->where('event', $event)->last())->created_at?->translatedFormat('d M Y');
    $goodsHints = [$when('created'), $when('submitted'), $when('approved'), $when('ordered'), $when('received') ?? $when('partial')];

    $moneyLabels = $purchase->isPayFirst() ? ['Dana diminta', 'Dana di finance', 'Dibayar'] : ['Belum dibayar', 'Dibayar'];
    $moneyAt = $isPaid
        ? count($moneyLabels) - 1
        : ($purchase->fund_status === Purchase::FUND_AT_FINANCE ? 1 : 0);
    $moneyHints = $purchase->isPayFirst()
        ? [$when('approved'), $purchase->fund_at?->translatedFormat('d M Y'), $when('paid')]
        : [null, $when('paid')];

    $stepsByTrack = [
        'Barang' => Stepper::steps(
            ['Draft', 'Diajukan', 'Disetujui', 'Dipesan', $purchase->status === Purchase::STATUS_PARTIAL ? 'Diterima sebagian' : 'Diterima'],
            $goodsAt,
            $purchase->status === Purchase::STATUS_RECEIVED,
            $terminal !== null,
            $goodsHints
        ),
        'Uang' => Stepper::steps($moneyLabels, $moneyAt, $isPaid, false, $moneyHints),
    ];

    $tracks = [];

    foreach ($nextSteps as $step) {
        $tracks[] = [
            'label' => $step['track'],
            'icon'  => $step['track'] === 'Uang' ? 'bi-cash-coin' : 'bi-box-seam',
            'badge' => $step['track'] === 'Barang' ? $terminal : null,
            'steps' => $stepsByTrack[$step['track']],
            'owner' => $step['owner'],
            'text'  => $step['text'],
            'mine'  => $step['mine'],
        ];
    }
@endphp

@include('partials.flow-card', ['flowTitle' => 'Tahapan Pembelian', 'tracks' => $tracks])
