<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi - {{ $kwitansi->nomor }}</title>
    <style>
        {{-- Ukuran halaman SENGAJA lebih kecil dari A4 penuh (mirip ukuran
             blok kwitansi fisik) supaya tidak banyak ruang kosong di bawah -
             konten kwitansi memang pendek, tidak butuh 1 halaman A4 penuh. --}}
        @page { size: 21.5cm 15cm; margin: 1.4cm; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1f2d3d;
            margin: 0;
            padding: 0;
            font-size: 10pt;
        }
        p { margin: 0; padding: 0; }

        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .logo-cell { width: 60px; padding-right: 12px; }
        .logo-cell img { max-width: 70px; max-height: 70px; }
        .brand-cell { }
        .brand-name {
            font-family: 'Helvetica-Bold', 'Helvetica', sans-serif;
            font-size: 15pt; font-weight: bold; color: #2f6fa8; margin-bottom: 2px;
        }
        .brand-contact { font-size: 7.5pt; color: #5b7a99; }
        .doc-title-cell { text-align: right; vertical-align: top; }
        .doc-title {
            font-family: 'Helvetica-Bold', 'Helvetica', sans-serif;
            font-size: 15pt; font-weight: bold; color: #2f6fa8; letter-spacing: 3px;
        }
        .doc-nomor { font-size: 9pt; color: #5b7a99; margin-top: 3px; }

        .header-divider { border-top: 1.5px solid #2f6fa8; margin: 10px 0 18px 0; }

        .field-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .field-table td { padding: 5px 0; vertical-align: bottom; font-size: 10pt; }
        .field-label { width: 26%; color: #5b7a99; }
        .field-sep { width: 2%; color: #5b7a99; }
        .field-value { border-bottom: 1px dotted #9fb7cc; padding-left: 4px; }

        .amount-bar {
            background-color: #dceaf7;
            border-radius: 4px;
            padding: 7px 12px;
            font-family: 'Helvetica-Bold', 'Helvetica', sans-serif;
            font-weight: bold;
            color: #1f4a72;
            font-size: 11pt;
        }
        .terbilang-row td { font-size: 8.5pt; font-style: italic; color: #5b7a99; padding-top: 2px; }

        .checkbox-row { margin: 12px 0 8px 0; font-size: 9.5pt; }
        .checkbox-item { display: inline-block; margin-right: 26px; color: #1f2d3d; }
        .checkbox-item .box { font-family: 'Helvetica-Bold', 'Helvetica', sans-serif; color: #2f6fa8; }

        .signature-block { width: 42%; float: right; text-align: center; margin-top: 4px; font-size: 9.5pt; }
        .signature-space { height: 50px; }
        .signature-space img { max-height: 50px; max-width: 100%; }
        .signature-name { border-top: 1px solid #333333; display: inline-block; padding-top: 3px; min-width: 75%; }

        .footer-note {
            clear: both;
            margin-top: 30px;
            font-size: 7.5pt;
            font-style: italic;
            color: #7a8fa3;
            border-top: 1px solid #dbe6f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @php
                    $company = \App\Models\AppSetting::company();
                    $logoPath = \App\Models\AppSetting::imagePath('logo_pdf');
                    $logoSrc = file_exists($logoPath)
                        ? 'data:' . mime_content_type($logoPath) . ';base64,' . base64_encode(file_get_contents($logoPath))
                        : '';
                @endphp
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo">
                @endif
            </td>
            <td class="brand-cell">
                <p class="brand-name">{{ $company['name'] }}</p>
                <p class="brand-contact">
                    {!! implode(' &nbsp;|&nbsp; ', array_map('e', $company['contact_line'])) !!}
                </p>
            </td>
            <td class="doc-title-cell">
                <p class="doc-title">KWITANSI</p>
                <p class="doc-nomor">No. {{ $kwitansi->nomor }}</p>
            </td>
        </tr>
    </table>
    <div class="header-divider"></div>

    <table class="field-table">
        <tr>
            <td class="field-label">Telah terima dari</td>
            <td class="field-sep">:</td>
            <td class="field-value">{{ $kwitansi->project->company ?: $kwitansi->project->client ?: '-' }}</td>
        </tr>
        <tr>
            <td class="field-label" style="padding-top: 10px;">Uang Sejumlah</td>
            <td class="field-sep"></td>
            <td style="padding-top: 10px;">
                <div class="amount-bar">{{ \App\Support\Money::formatRupiah($kwitansi->jumlah) }}</div>
            </td>
        </tr>
        <tr class="terbilang-row">
            <td></td><td></td>
            <td>({{ $terbilang }})</td>
        </tr>
    </table>

    {{-- kategori_pembayaran SEKARANG field pilihan eksplisit di form
         Kwitansi (bukan tebak-tebak dari teks Keterangan lagi). --}}
    <div class="checkbox-row">
        <span class="checkbox-item"><span class="box">{{ $kwitansi->kategori_pembayaran === 'booking_fee' ? '[X]' : '[ ]' }}</span> Booking Fee</span>
        <span class="checkbox-item"><span class="box">{{ $kwitansi->kategori_pembayaran === 'dp' ? '[X]' : '[ ]' }}</span> Down Payment (DP)</span>
        <span class="checkbox-item"><span class="box">{{ $kwitansi->kategori_pembayaran === 'pelunasan' ? '[X]' : '[ ]' }}</span> Pelunasan</span>
    </div>

    <table class="field-table">
        <tr>
            <td class="field-label">Keterangan</td>
            <td class="field-sep">:</td>
            <td class="field-value">{{ $kwitansi->keterangan ?: ('Pembayaran project "' . $kwitansi->project->name . '"') }}</td>
        </tr>
        <tr>
            <td class="field-label">Metode Pembayaran</td>
            <td class="field-sep">:</td>
            <td class="field-value">{{ $kwitansi->metode_pembayaran ?: '-' }}</td>
        </tr>
    </table>

    <div class="signature-block">
        <p>Surabaya, {{ $kwitansi->tanggal->translatedFormat('d F Y') }}</p>
        <p>Hormat kami,</p>
        <div class="signature-space">
            @if($kwitansi->signature)
                @php
                    $sigPath = storage_path('app/public/' . $kwitansi->signature->file_path);
                    $sigSrc = file_exists($sigPath)
                        ? 'data:' . mime_content_type($sigPath) . ';base64,' . base64_encode(file_get_contents($sigPath))
                        : '';
                @endphp
                @if($sigSrc)
                    <img src="{{ $sigSrc }}" alt="Tanda Tangan">
                @endif
            @endif
        </div>
        <p class="signature-name">{{ $kwitansi->creator->name ?? $company['name'] }}</p>
    </div>

    <p class="footer-note">
        Pembayaran melalui transfer dianggap sah setelah dana diterima dan dikonfirmasi oleh {{ $company['name'] }}.
    </p>

</body>
</html>
