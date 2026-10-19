<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - {{ $purchase->code }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 0.5cm 1.8cm 1.8cm 1.8cm;
        }

        body {
            font-family: 'Times-Roman', 'Times New Roman', serif;
            color: #222222;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            font-size: 10pt;
        }

        p {
            margin: 0;
            padding: 0;
        }

        /* HEADER */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .header-table td {
            vertical-align: middle;
        }

        /* LOGO CELL (Tanpa Garis Vertikal) */
        .logo-cell {
            width: 22%;
            padding-right: 12px;
            padding-top: 6px;
        }

        .header-logo {
            width: 115px;
            height: auto;
            display: block;
        }

        /* DETAIL PERUSAHAAN */
        .brand-cell {
            width: 78%;
            padding-left: 0px;
        }

        /* BRAND NAME (Fake Bold untuk DomPDF) */
        .brand-name {
            font-family: 'Helvetica-Bold', 'Helvetica', sans-serif;
            font-size: 16pt;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            margin-bottom: 2px;
            text-shadow: 0.5px 0px 0px #000000; /* Menambah ketebalan ekstra di DomPDF */
        }

        /* BRAND SUB */
        .brand-sub {
            font-family: 'Times-BoldItalic', 'Times-Roman', serif;
            font-size: 10.5pt;
            /* font-style: italic; */
            font-weight: bold;
            color: #111111;
            letter-spacing: 1.5px;
            margin-bottom: 3px;
        }

        /* BRAND ADDRESS */
        .brand-address {
            font-family: 'Times-Bold', 'Times-Roman', serif;
            font-size: 7.5pt;
            font-weight: bold;
            color: #222222;
            letter-spacing: 0.3px;
        }

        .divider {
            border-top: 2px solid #0d3b66;
            margin: 6px 0 12px 0;
        }

        .doc-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
            color: #111111;
            text-transform: uppercase;
        }

        .doc-number {
            text-align: center;
            font-size: 9pt;
            color: #555555;
            margin-bottom: 12px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-table td {
            vertical-align: top;
            padding: 1px 0;
        }
        .info-label {
            color: #555555;
            width: 90px;
        }

        .item-table {
            width: 100%;
            border-collapse: collapse;
        }
        .item-table th, .item-table td {
            border: 1px solid #333333;
            padding: 5px 7px;
            vertical-align: top;
        }
        .item-table th {
            background-color: #f0f4f8;
            text-align: center;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .note-box {
            margin-top: 12px;
            font-size: 9.5pt;
        }

        .sign-table {
            width: 100%;
            margin-top: 28px;
            border-collapse: collapse;
        }
        .sign-table td {
            width: 33%;
            text-align: center;
            vertical-align: top;
            font-size: 9.5pt;
        }
        .sign-space { height: 55px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <!-- Kolom Logo (Tanpa Garis Vertikal) -->
            <td class="logo-cell">
                @php
                    $company = \App\Models\AppSetting::company();
                    $imagePath = \App\Models\AppSetting::imagePath('logo_pdf');
                    if (file_exists($imagePath)) {
                        $imageData = base64_encode(file_get_contents($imagePath));
                        $mimeType = mime_content_type($imagePath);
                        $src = 'data:' . $mimeType . ';base64,' . $imageData;
                    } else {
                        $src = '';
                    }
                @endphp

                @if($src)
                    <img src="{{ $src }}" class="header-logo" alt="Logo">
                @else
                    <span style="font-size: 8pt; color: red;">Logo Tidak Ditemukan</span>
                @endif
            </td>

            <!-- Kolom Nama & Detail Perusahaan -->
            <td class="brand-cell">
                <p class="brand-name">{{ mb_strtoupper($company['name']) }}</p>
                @if($company['tagline'] !== '')
                <p class="brand-sub">{{ $company['tagline'] }}</p>
                @endif
                <p class="brand-address">{{ $company['address_line'] }}</p>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    @php
        $approval = $purchase->approvalRequests->where('status', 'approved')->sortByDesc('id')->first();
        $timing = \App\Models\Purchase::PAYMENT_TIMINGS[$purchase->payment_timing] ?? '-';
        $poTotal = 0;
    @endphp

    <p class="doc-title">Purchase Order</p>
    <p class="doc-number">No. {{ $purchase->code }}</p>

    <table class="info-table">
        <tr>
            <td class="info-label">Kepada</td>
            <td>: <strong>{{ $purchase->vendor_name }}</strong>@if($purchase->vendor?->company) ({{ $purchase->vendor->company }})@endif</td>
            <td class="info-label">Tanggal</td>
            <td>: {{ $purchase->purchase_date->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="info-label">Alamat</td>
            <td>: {{ $purchase->vendor?->address ?: '-' }}</td>
            <td class="info-label">Waktu Bayar</td>
            <td>: {{ $timing }}@if($purchase->payment_timing === \App\Models\Purchase::TIMING_TERM && $purchase->due_date), jatuh tempo {{ $purchase->due_date->translatedFormat('d F Y') }}@endif</td>
        </tr>
        <tr>
            <td class="info-label">Telepon</td>
            <td>: {{ $purchase->vendor?->phone ?: '-' }}</td>
            <td class="info-label">Project</td>
            <td>: {{ $purchase->project?->name ?: '-' }}</td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>Nama Barang</th>
                <th style="width: 55px;">Jumlah</th>
                <th style="width: 100px;">Harga Satuan</th>
                <th style="width: 110px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $item)
                @php
                    $price = (float) ($item->po_unit_price ?? $item->unit_price);
                    $line = round($item->qty * $price, 2);
                    $poTotal += $line;
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $item->name }}@if($item->brand) <span style="color:#555555;">({{ $item->brand }})</span>@endif</td>
                    <td class="text-center">{{ number_format($item->qty) }}</td>
                    <td class="text-right">{{ \App\Support\Money::formatRupiah($price) }}</td>
                    <td class="text-right">{{ \App\Support\Money::formatRupiah($line) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4" class="text-right"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ \App\Support\Money::formatRupiah($poTotal) }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if($purchase->notes)
        <div class="note-box">
            <strong>Catatan:</strong> {{ $purchase->notes }}
        </div>
    @endif

    <div class="note-box">
        Mohon konfirmasi ketersediaan barang dan estimasi pengiriman. Terima kasih atas kerja samanya.
    </div>

    <table class="sign-table">
        <tr>
            <td>Dibuat oleh,</td>
            <td>Disetujui oleh,</td>
            <td>Vendor,</td>
        </tr>
        <tr><td class="sign-space" colspan="3"></td></tr>
        <tr>
            <td>({{ $purchase->creator->name ?? '................' }})</td>
            <td>({{ $approval?->decidedBy?->name ?? '................' }})</td>
            <td>(................)</td>
        </tr>
    </table>

</body>
</html>
