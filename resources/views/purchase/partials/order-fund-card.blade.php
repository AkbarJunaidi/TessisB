@php
    $row = fn (string $label, $value) => filled($value)
        ? '<div class="mb-2"><div class="text-muted small">' . e($label) . '</div><div>' . $value . '</div></div>'
        : '';
@endphp

@if($purchase->ordered_at)
    <div class="card border-0 shadow-sm rounded-3 mt-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Pesanan</h6>
            <div class="row">
                <div class="col-md-6">
                    {!! $row('Dipesan', e($purchase->ordered_at->translatedFormat('d M Y H:i')) . ' - ' . e($purchase->orderer->name ?? '-')) !!}
                    {!! $row('Estimasi tiba', $purchase->expected_at ? e($purchase->expected_at->translatedFormat('d M Y')) : null) !!}
                    {!! $row('Nama toko', e($purchase->store_name)) !!}
                </div>
                <div class="col-md-6">
                    @php
                        $orderValue = e($purchase->order_number);
                        if ($purchase->order_url) {
                            $orderValue = '<a href="' . e($purchase->order_url) . '" target="_blank" rel="noopener noreferrer" class="text-decoration-none">' . ($orderValue ?: 'Buka pesanan') . '</a>';
                        }
                    @endphp
                    {!! $row('Nomor pesanan', $orderValue) !!}
                    {!! $row('Ekspedisi', e($purchase->courier)) !!}
                    {!! $row('Nomor resi', e($purchase->tracking_number)) !!}
                </div>
            </div>
        </div>
    </div>
@endif

@if($purchase->isPayFirst() && $purchase->fund_status === \App\Models\Purchase::FUND_AT_FINANCE)
    <div class="card border-0 shadow-sm rounded-3 mt-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Dana di Finance</h6>
            <div class="row">
                <div class="col-md-6">
                    {!! $row('Nominal', e(\App\Support\Money::formatRupiah($purchase->fund_amount))) !!}
                    {!! $row('Tanggal transfer', $purchase->fund_at ? e($purchase->fund_at->translatedFormat('d M Y')) : null) !!}
                </div>
                <div class="col-md-6">
                    {!! $row('Dicatat oleh', e($purchase->funder->name ?? null)) !!}
                    {!! $row('Catatan', e($purchase->fund_note)) !!}
                </div>
            </div>
        </div>
    </div>
@endif
