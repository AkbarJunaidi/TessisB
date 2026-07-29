<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan - {{ $suratJalan->nomor }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 1.2cm;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #222222;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            font-size: 9.5pt;
            position: relative; /* Penting untuk positioning footer absolute */
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

        /* LOGO STYLING */
        .header-logo {
            width: 120px;
            height: auto;
            display: block;
        }

        .brand-name {
            font-size: 14pt;
            font-weight: bold;
            color: #0d3b66;
            letter-spacing: 0.3px;
        }
        .brand-sub {
            font-size: 8.5pt;
            font-weight: bold;
            color: #444444;
            margin-top: 2px;
        }
        .brand-address {
            font-size: 7.5pt;
            color: #666666;
            margin-top: 2px;
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
            font-size: 8.5pt;
            color: #555555;
            margin-bottom: 12px;
        }

        /* INFO KEPADA / TANGGAL / PIC */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 9pt;
        }
        .info-table td {
            vertical-align: top;
            padding: 2px 0;
        }
        .info-label {
            width: 90px;
            color: #555555;
        }
        .info-colon {
            width: 12px;
        }

        /* TABEL ITEM */
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px;
        }
        .item-table th, .item-table td {
            border: 1px solid #333333;
            padding: 6px 8px;
            font-size: 9pt;
            vertical-align: top;
        }
        .item-table th {
            background-color: #f0f4f8;
            text-align: center;
            font-weight: bold;
        }
        .item-no {
            width: 28px;
            text-align: center;
        }
        .item-category {
            font-weight: bold;
            margin-top: 4px;
            margin-bottom: 3px;
            color: #00070d;
        }
        .item-category:first-child {
            margin-top: 0;
        }
        .item-line {
            margin-bottom: 2px;
            padding-left: 8px;
        }

        .crew-box {
            border: 1px solid #333333;
            border-top: none;
            padding: 6px 8px;
            font-size: 9pt;
            margin-bottom: 10px;
            background-color: #fafafa;
        }
        .crew-label {
            font-weight: bold;
        }

        /* LAPORAN KERJA */
        .laporan-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .laporan-title-row td {
            border: 1px solid #333333;
            background-color: #f0f4f8;
            font-weight: bold;
            padding: 5px 8px;
            font-size: 9pt;
        }
        .laporan-cell {
            border: 1px solid #333333;
            border-top: none;
            padding: 6px 8px;
            font-size: 9pt;
            width: 50%;
            vertical-align: top;
        }
        .laporan-venue-name {
            font-weight: bold;
        }

        .catatan-box {
            border: 1px solid #000000;
            padding: 6px 8px;
            font-size: 9pt;
            min-height: 45px;
            margin-bottom: 14px;
        }
        .catatan-label {
            font-weight: bold;
            margin-bottom: 4px;
        }

        /* FOOTER CONTAINER STYLING */
        .footer-container {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
        }

        .tagline {
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            color: #000000;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .footer-divider {
            border-top: 1.5px solid #000000;
            margin: 4px 0 6px 0;
        }
        .footer-contact {
            text-align: center;
            font-size: 7.5pt;
            color: #555555;
        }

        .status-badge {
            font-size: 8pt;
            font-weight: bold;
            padding: 3px 10px;
            border: 1px solid #333333;
            border-radius: 4px;
            display: inline-block;
            background-color: #ffffff;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <!-- Kolom Logo -->
            <td style="width: 15%; padding-right: 10px;">
                @php
                    // $imagePath = public_path('image/LogoAP.png');
                    $imagePath = public_path('image/Arindra.png');
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
            <td style="width: 55%;">
                <p class="brand-name">CV. ARINDRA PRODUCTION</p>
                <p class="brand-sub">Creative House Production</p>
                <p class="brand-address">Alamat: Bendul Merisi Selatan 3/102 Surabaya | Telp: 031-8431462 | WA: 081252200899</p>
            </td>
            <!-- Kolom Status Badge -->
            <td style="width: 30%; text-align: right;">
                <span class="status-badge">{{ strtoupper($suratJalan->status) }}</span>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <p class="doc-title">Surat Jalan</p>
    <p class="doc-number">No. {{ $suratJalan->nomor }}</p>

    <table class="info-table">
        <tr>
            <td class="info-label">Kepada</td>
            <td class="info-colon">:</td>
            <td style="font-weight: bold; width: 40%;">{{ $suratJalan->kepada }}</td>
            <td class="info-label" style="width: 100px;">Tanggal Terbit</td>
            <td class="info-colon">:</td>
            <td>{{ \Carbon\Carbon::parse($suratJalan->tanggal_terbit)->translatedFormat('d / m / Y') }}</td>
        </tr>
        <tr>
            <td class="info-label">&nbsp;</td>
            <td class="info-colon"></td>
            <td></td>
            <td class="info-label">PIC</td>
            <td class="info-colon">:</td>
            <td>{{ $suratJalan->pic }}</td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th class="item-no">No</th>
                <th>Item / Barang</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="item-no">1</td>
                <td>
                    @php $grouped = $suratJalan->items->groupBy(fn ($i) => $i->kategori_item ?: 'Barang'); @endphp
                    @foreach($grouped as $kategori => $rows)
                        <p class="item-category">{{ $kategori }}</p>
                        @foreach($rows as $row)
                            <p class="item-line">&bull; {{ $row->qty_dipakai }} Unit &ndash; {{ $row->inventory->name }}</p>
                        @endforeach
                    @endforeach
                </td>
            </tr>
        </tbody>
    </table>

    <div class="crew-box">
        <span class="crew-label">Crew :</span> {{ $suratJalan->project->crews->pluck('name')->implode(', ') ?: '-' }}
    </div>

    <table class="laporan-table">
        <tr class="laporan-title-row">
            <td colspan="2">Laporan Kerja</td>
        </tr>
        <tr>
            <td class="laporan-cell">
                Tanggal Keberangkatan : {{ $suratJalan->tanggal_keberangkatan ? \Carbon\Carbon::parse($suratJalan->tanggal_keberangkatan)->translatedFormat('d F Y') : '-' }}<br>
                Jam Berangkat : {{ $suratJalan->jam_berangkat ? \Carbon\Carbon::parse($suratJalan->jam_berangkat)->format('H:i') . ' WIB' : '-' }}
            </td>
            <td class="laporan-cell">
                Tanggal Gladi Bersih : {{ $suratJalan->tanggal_gladi_bersih ? \Carbon\Carbon::parse($suratJalan->tanggal_gladi_bersih)->translatedFormat('d F Y') : '-' }}<br>
                Waktu Gladi Bersih : {{ $suratJalan->waktu_gladi_bersih ? \Carbon\Carbon::parse($suratJalan->waktu_gladi_bersih)->format('H:i') . ' WIB' : '-' }}
            </td>
        </tr>
        <tr>
            <td class="laporan-cell" colspan="2">
                @php
                    $tglMulai = $suratJalan->tanggal_acara ? \Carbon\Carbon::parse($suratJalan->tanggal_acara) : null;
                    $tglSelesai = $suratJalan->tanggal_acara_selesai ? \Carbon\Carbon::parse($suratJalan->tanggal_acara_selesai) : null;
                    $tglAcaraText = '-';
                    if ($tglMulai && $tglSelesai && !$tglMulai->isSameDay($tglSelesai)) {
                        $tglAcaraText = $tglMulai->translatedFormat('d F') . ' - ' . $tglSelesai->translatedFormat('d F Y');
                    } elseif ($tglMulai) {
                        $tglAcaraText = $tglMulai->translatedFormat('d F Y');
                    }
                @endphp
                Tanggal Acara : {{ $tglAcaraText }}<br>
                Waktu Acara : {{ $suratJalan->waktu_acara ?: '-' }}
            </td>
        </tr>
        <tr>
            <td class="laporan-cell" colspan="2">
                Lokasi Acara :<br>
                <span class="laporan-venue-name">{{ $suratJalan->project->location ?? '-' }}</span><br>
                {{ $suratJalan->lokasi_acara }}
            </td>
        </tr>
    </table>

    <div class="catatan-box">
        <p class="catatan-label">Catatan :</p>
        {{ $suratJalan->catatan ?: '-' }}
    </div>

    <!-- WRAPPER FOOTER -->
    <div class="footer-container">
        <p class="tagline">Videography | Photography | Live Streaming</p>
        <div class="footer-divider"></div>
        <p class="footer-contact">
            081217439568, 081252200899 &nbsp;|&nbsp; www.arindraproduction.co.id &nbsp;|&nbsp; @arindraproduction &nbsp;|&nbsp; @cvarindraproduction
        </p>
    </div>

</body>
</html>
