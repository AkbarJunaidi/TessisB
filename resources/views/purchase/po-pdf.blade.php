<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - {{ $purchase->code }}</title>
    <style>
        @page { size: a4 portrait; margin: 0.8cm 1.8cm 1.8cm 1.8cm; }
        * { box-sizing: border-box; }
        body { font-family: 'Times-Roman', 'Times New Roman', serif; color: #222222; line-height: 1.4; margin: 0; font-size: 9.5pt; }
        p { margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; }

        .header-table td { vertical-align: middle; }
        .logo-cell { width: 22%; padding-right: 12px; }
        .header-logo { width: 115px; height: auto; display: block; }
        .brand-name { font-family: 'Helvetica-Bold', 'Helvetica', sans-serif; font-size: 16pt; font-weight: bold; color: #000000; }
        .brand-sub, .brand-address { font-size: 8.5pt; color: #444444; }
        .divider { border-top: 2px solid #000000; margin: 8px 0 12px; }

        .doc-title { font-family: 'Helvetica-Bold', 'Helvetica', sans-serif; font-size: 14pt; font-weight: bold; text-align: center; letter-spacing: 1px; }
        .doc-number { text-align: center; margin-bottom: 12px; }

        .info-table td { vertical-align: top; padding: 2px 0; }
        .info-label { width: 26%; color: #555555; }

        .item-table { margin-top: 14px; }
        .item-table th, .item-table td { border: 1px solid #333333; padding: 5px 7px; }
        .item-table th { background: #eeeeee; font-family: 'Helvetica-Bold', 'Helvetica', sans-serif; font-size: 8.5pt; text-align: left; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .total-row td { font-weight: bold; }

        .notes { margin-top: 12px; }
        .sign-table { margin-top: 36px; }
        .sign-table td { width: 50%; text-align: center; vertical-align: top; }
        .sign-space { height: 56px; }
    </style>
</head>
<body>
    @php
        $company = \App\Models\AppSetting::company();
        $imagePath = \App\Models\AppSetting::imagePath('logo_pdf');
        $src = file_exists($imagePath) ? 'data:' . mime_content_type($imagePath) . ';base64,' . base64_encode(file_get_contents($imagePath)) : '';
        $vendor = $purchase->vendor;
    @endphp

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if($src)
                    <img src="{{ $src }}" class="header-logo" alt="Logo">
                @endif
            </td>
            <td>
                <p class="brand-name">{{ mb_strtoupper($company['name']) }}</p>
                @if($company['tagline'] !== '')
                    <p class="brand-sub">{{ $company['tagline'] }}</p>
                @endif
                <p class="brand-address">{{ $company['address_line'] }}</p>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <p class="doc-title">PURCHASE ORDER</p>
    <p class="doc-number">No. {{ $purchase->code }}</p>

    <table class="info-table">
        <tr>
            <td class="info-label">Kepada</td>
            <td>: {{ $purchase->vendor_name }}@if($vendor && $vendor->company) ({{ $vendor->company }})@endif</td>
        </tr>
        @if($vendor && $vendor->address)
            <tr><td class="info-label">Alamat</td><td>: {{ $vendor->address }}</td></tr>
        @endif
        @if($vendor && $vendor->phone)
            <tr><td class="info-label">Telepon</td><td>: {{ $vendor->phone }}</td></tr>
        @endif
        <tr>
            <td class="info-label">Tanggal</td>
            <td>: {{ $purchase->purchase_date->translatedFormat('d F Y') }}</td>
        </tr>
        @if($purchase->expected_at)
            <tr><td class="info-label">Estimasi tiba</td><td>: {{ $purchase->expected_at->translatedFormat('d F Y') }}</td></tr>
        @endif
        <tr>
            <td class="info-label">Pembayaran</td>
            <td>: {{ \App\Models\Purchase::PAYMENT_TIMINGS[$purchase->payment_timing] ?? '-' }}@if($purchase->due_date), jatuh tempo {{ $purchase->due_date->translatedFormat('d F Y') }}@endif</td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 6%;">No</th>
                <th>Nama Barang</th>
                <th class="text-end" style="width: 10%;">Jumlah</th>
                <th class="text-end" style="width: 20%;">Harga Satuan</th>
                <th class="text-end" style="width: 20%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $item->name }}@if($item->brand) ({{ $item->brand }})@endif</td>
                    <td class="text-end">{{ number_format($item->qty) }}</td>
                    <td class="text-end">{{ \App\Support\Money::formatRupiah($item->unit_price) }}</td>
                    <td class="text-end">{{ \App\Support\Money::formatRupiah($item->subtotal) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="text-end">Total</td>
                <td class="text-end">{{ \App\Support\Money::formatRupiah($purchase->total) }}</td>
            </tr>
        </tbody>
    </table>

    @if($purchase->notes)
        <p class="notes"><strong>Catatan:</strong> {{ $purchase->notes }}</p>
    @endif

    <table class="sign-table">
        <tr>
            <td>Dibuat oleh,<div class="sign-space"></div>{{ $purchase->creator->name ?? '-' }}</td>
            <td>Hormat kami,<div class="sign-space"></div>{{ $company['name'] }}</td>
        </tr>
    </table>
</body>
</html>
