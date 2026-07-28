<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan - {{ $suratJalan->nomor }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 1.6cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #222222;
            line-height: 1.45;
            margin: 0;
            padding: 0;
            font-size: 10pt;
        }

        /* HEADER */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .header-table td { vertical-align: middle; }
        .brand-name { font-size: 15pt; font-weight: bold; color: #0d3b66; margin: 0; letter-spacing: .3px; }
        .brand-sub { font-size: 9pt; font-weight: bold; color: #444444; margin: 1px 0 0 0; }
        .brand-address { font-size: 7.5pt; color: #777777; margin: 2px 0 0 0; }

        .divider { border-top: 2px solid #0d3b66; margin: 6px 0 10px 0; }

        .doc-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0 0 12px 0;
            color: #111111;
        }

        /* INFO KEPADA / TANGGAL / PIC */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9.5pt; }
        .info-table td { vertical-align: top; padding: 1px 0; }
        .info-label { width: 90px; color: #555555; }
        .info-colon { width: 12px; }

        /* TABEL ITEM */
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .item-table th, .item-table td { border: 1px solid #333333; padding: 6px 8px; font-size: 9.5pt; vertical-align: top; }
        .item-table th { background: #f0f4f8; text-align: center; font-weight: bold; }
        .item-no { width: 28px; text-align: center; }
        .item-category { font-weight: bold; margin: 2px 0 3px 0; }
        .item-line { margin: 0 0 2px 0; }

        .crew-box { border: 1px solid #333333; border-top: none; padding: 6px 8px; font-size: 9.5pt; margin-bottom: 10px; margin-top: -10px; }
        .crew-label { font-weight: bold; }

        /* LAPORAN KERJA */
        .laporan-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .laporan-title-row td { border: 1px solid #333333; font-weight: bold; padding: 5px 8px; font-size: 9.5pt; }
        .laporan-cell { border: 1px solid #333333; border-top: none; padding: 6px 8px; font-size: 9.5pt; width: 50%; }
        .laporan-cell.left { border-right: 1px solid #333333; }
        .laporan-venue-name { font-weight: bold; }

        .catatan-box { border: 1px solid #333333; padding: 6px 8px; font-size: 9.5pt; min-height: 40px; margin-bottom: 14px; }
        .catatan-label { font-weight: bold; margin-bottom: 4px; }

        .tagline { text-align: center; font-size: 8.5pt; font-weight: bold; color: #0d3b66; margin-bottom: 6px; letter-spacing: .5px; }
        .footer-divider { border-top: 2px solid #0d3b66; margin: 4px 0 6px 0; }
        .footer-contact { text-align: center; font-size: 7.5pt; color: #555555; }

        .status-badge { font-size: 8pt; font-weight: bold; padding: 2px 8px; border: 1px solid #333333; border-radius: 10px; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <p class="brand-name">CV. ARINDRA PRODUCTION</p>
                <p class="brand-sub">Creative House Production</p>
                <p class="brand-address">Alamat: Bendul Merisi Selatan 3/102 Surabaya, Telp: 031-8431462, Whatsapp: 081252200899</p>
            </td>
            <td style="width: 30%; text-align: right;">
                <span class="status-badge">{{ strtoupper($suratJalan->status) }}</span>
            </td>
        </tr>
    </table>
    <div class="divider"></div>

    <p class="doc-title">Surat Jalan</p>
    <p style="text-align:center; font-size:9pt; color:#666666; margin-top:-8px; margin-bottom:12px;">No. {{ $suratJalan->nomor }}</p>

    <table class="info-table">
        <tr>
            <td class="info-label">Kepada</td>
            <td class="info-colon">:</td>
            <td style="font-weight:bold;">{{ $suratJalan->kepada }}</td>
            <td class="info-label" style="width:100px;">Tanggal Terbit</td>
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
                <th>Item</th>
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
                            <p class="item-line">{{ $row->qty_dipakai }} Unit &ndash; {{ $row->inventory->name }}</p>
                        @endforeach
                    @endforeach
                </td>
            </tr>
        </tbody>
    </table>

    <div class="crew-box">
        <span class="crew-label">Crew</span> : {{ $suratJalan->project->crews->pluck('name')->implode(', ') ?: '-' }}
    </div>

    <table class="laporan-table">
        <tr class="laporan-title-row">
            <td colspan="2">Laporan Kerja</td>
        </tr>
        <tr>
            <td class="laporan-cell left">
                Tanggal Keberangkatan : {{ $suratJalan->tanggal_keberangkatan ? \Carbon\Carbon::parse($suratJalan->tanggal_keberangkatan)->translatedFormat('d F Y') : '-' }}<br>
                Jam Berangkat : {{ $suratJalan->jam_berangkat ? \Carbon\Carbon::parse($suratJalan->jam_berangkat)->format('H:i') . ' WIB' : '-' }}
            </td>
            <td class="laporan-cell">
                Tanggal Gladi Bersih : {{ $suratJalan->tanggal_gladi_bersih ? \Carbon\Carbon::parse($suratJalan->tanggal_gladi_bersih)->translatedFormat('d F Y') : '-' }}<br>
                Waktu Gladi Bersih : {{ $suratJalan->waktu_gladi_bersih ? \Carbon\Carbon::parse($suratJalan->waktu_gladi_bersih)->format('H:i') . ' WIB' : '-' }}
            </td>
        </tr>
        <tr>
            <td class="laporan-cell left" colspan="2">
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
            <td class="laporan-cell left" colspan="2">
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

    <p class="tagline">Videography | Photography | Live Streaming</p>
    <div class="footer-divider"></div>
    <p class="footer-contact">
        081217439568, 081252200899 &nbsp;|&nbsp; www.arindraproduction.co.id &nbsp;|&nbsp; @arindraproduction &nbsp;|&nbsp; @cvarindraproduction
    </p>

</body>
</html>
