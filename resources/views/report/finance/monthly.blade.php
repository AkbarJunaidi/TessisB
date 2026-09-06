<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan Bulanan - {{ $periodDate->translatedFormat('F Y') }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 4cm 1.8cm 2cm 1.8cm;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            /* Font default untuk isi dokumen */
            font-family: 'Times-Roman', 'Times New Roman', serif;
            color: #222222;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            font-size: 9.5pt;
            position: relative;
        }

        p {
            margin: 0;
            padding: 0;
        }

        /* HEADER (fixed - berulang di setiap halaman) */
        .page-header-fixed {
            position: fixed;
            top: -136px;
            left: 0;
            right: 0;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1px;
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
            text-shadow: 0.5px 0px 0px #000000;
        }

        /* BRAND SUB */
        .brand-sub {
            font-family: 'Times-BoldItalic', 'Times-Roman', serif;
            font-size: 10.5pt;
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
            margin: 0px 0 20px 0;
        }

        .doc-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 10px 0;
            color: #111111;
            text-transform: uppercase;
        }

        .doc-title span {
            border-bottom: 1.5px solid #111111;
            padding-bottom: 4px;
        }

        .doc-period {
            text-align: center;
            font-size: 9pt;
            color: #555555;
            margin-bottom: 12px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            color: #444444;
            margin-bottom: 16px;
        }

        /* RINGKASAN */
        .summary-title {
            font-size: 10.5pt;
            font-weight: bold;
            margin-bottom: 6px;
            border-bottom: 1px solid #999999;
            padding-bottom: 3px;
            page-break-after: avoid; /* judul tidak boleh jadi baris terakhir sendirian di halaman */
        }

        /* Blok yang harus utuh - kalau tidak muat di sisa halaman, pindah semua ke halaman baru */
        .section-block {
            page-break-inside: avoid;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 9.5pt;
        }
        .summary-table td {
            padding: 3px 0;
        }
        .summary-label {
            color: #444444;
            width: 60%;
        }
        .summary-value {
            text-align: right;
            font-weight: bold;
        }

        /* TABEL PROJECT */
        .project-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 8.5pt;
        }
        .project-table th, .project-table td {
            border: 1px solid #666666;
            padding: 5px 6px;
        }
        .project-table th {
            background-color: #f0f4f8;
            text-align: center;
            font-weight: bold;
        }
        .project-table thead {
            display: table-header-group;
        }
        .project-table tr {
            page-break-inside: avoid;
        }
        .col-no {
            width: 24px;
            text-align: center;
        }
        .col-money {
            text-align: right;
            white-space: nowrap;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f5f5f5;
        }

        /* TABEL REKAP PENDAPATAN/PENGELUARAN */
        .recap-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 8.5pt;
        }
        .recap-table th, .recap-table td {
            border: 1px solid #666666;
            padding: 5px 6px;
        }
        .recap-table th {
            background-color: #f0f4f8;
            text-align: center;
            font-weight: bold;
        }
        .recap-table thead {
            display: table-header-group; /* thead berulang otomatis kalau tabel lanjut ke halaman baru */
        }
        .recap-table tr {
            page-break-inside: avoid;
        }

        /* FOOTER CONTAINER STYLING (fixed - berulang di setiap halaman) */
        .footer-container {
            position: fixed;
            bottom: -30px;
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
            font-size: 9pt;
            color: #555555;
        }
    </style>
</head>
<body>

    <div class="page-header-fixed">
        <table class="header-table">
            <tr>
                <!-- Kolom Logo (Tanpa Garis Vertikal) -->
                <td class="logo-cell">
                    @php
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
                <td class="brand-cell">
                    <p class="brand-name">CV. ARINDRA PRODUCTION</p>
                    <p class="brand-sub">Creative House Production</p>
                    <p class="brand-address">Alamat : Bendul Merisi Selatan 3/102 Surabaya, Telp : 031- 8431462, Whatsapp : 081252200899</p>
                </td>
            </tr>
        </table>

        <div class="divider"></div>
    </div>

    <p class="doc-title"><span>Laporan Keuangan Bulanan</span></p>
    <p class="doc-period">Periode: {{ $periodDate->translatedFormat('F Y') }}</p>

    <div class="section-block">
        <p class="summary-title">Ringkasan</p>
        <table class="summary-table">
            <tr>
                <td class="summary-label">Total Project</td>
                <td class="summary-value">{{ $summary['total_project'] }} Project</td>
            </tr>
            <tr>
                <td class="summary-label">Total Pendapatan</td>
                <td class="summary-value">{{ \App\Support\Money::formatRupiah($summary['total_revenue']) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Total Pengeluaran</td>
                <td class="summary-value">{{ \App\Support\Money::formatRupiah($summary['total_expense']) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Laba Bersih</td>
                <td class="summary-value">{{ \App\Support\Money::formatRupiah($summary['total_profit']) }}</td>
            </tr>
        </table>
    </div>

    <p class="summary-title">Daftar Project</p>
    <table class="project-table">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th>Nama Project</th>
                <th>Client</th>
                <th>Tanggal</th>
                <th>Pendapatan</th>
                <th>Pengeluaran</th>
                <th>Laba</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['projects'] as $index => $project)
                <tr>
                    <td class="col-no">{{ $index + 1 }}</td>
                    <td>{{ $project->name }}</td>
                    <td>{{ $project->client ?: '-' }}</td>
                    <td>{{ optional($project->event_date)->translatedFormat('d M Y') }}</td>
                    <td class="col-money">{{ \App\Support\Money::formatRupiah($project->total_income) }}</td>
                    <td class="col-money">{{ \App\Support\Money::formatRupiah($project->total_expense) }}</td>
                    <td class="col-money">{{ \App\Support\Money::formatRupiah($project->profit) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Total</td>
                <td class="col-money">{{ \App\Support\Money::formatRupiah($summary['total_revenue']) }}</td>
                <td class="col-money">{{ \App\Support\Money::formatRupiah($summary['total_expense']) }}</td>
                <td class="col-money">{{ \App\Support\Money::formatRupiah($summary['total_profit']) }}</td>
            </tr>
        </tbody>
    </table>

    <p class="summary-title">Rekap Pendapatan</p>
    <table class="recap-table">
        <thead>
            <tr>
                <th style="width:80px;">Tanggal Diinput</th>
                <th>Nama Project</th>
                <th>Keterangan</th>
                <th style="width:110px;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summary['income_items'] as $item)
                <tr>
                    <td>{{ $item->created_at->translatedFormat('d M Y') }}</td>
                    <td>{{ $item->project_name }}</td>
                    <td>{{ $item->description ?: '-' }}</td>
                    <td class="col-money">{{ \App\Support\Money::formatRupiah($item->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; color:#777777;">Tidak ada data pendapatan.</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3" style="text-align:right;">Total Pendapatan</td>
                <td class="col-money">{{ \App\Support\Money::formatRupiah($summary['total_revenue']) }}</td>
            </tr>
        </tbody>
    </table>

    <p class="summary-title">Rekap Pengeluaran</p>
    <table class="recap-table">
        <thead>
            <tr>
                <th style="width:80px;">Tanggal Diinput</th>
                <th>Nama Project</th>
                <th>Keterangan</th>
                <th style="width:110px;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summary['expense_items'] as $item)
                <tr>
                    <td>{{ $item->created_at->translatedFormat('d M Y') }}</td>
                    <td>{{ $item->project_name }}</td>
                    <td>{{ $item->description ?: '-' }}</td>
                    <td class="col-money">{{ \App\Support\Money::formatRupiah($item->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; color:#777777;">Tidak ada data pengeluaran.</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3" style="text-align:right;">Total Pengeluaran</td>
                <td class="col-money">{{ \App\Support\Money::formatRupiah($summary['total_expense']) }}</td>
            </tr>
        </tbody>
    </table>

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
