<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inventory Report - {{ $inventory->serial_number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 1.8cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            font-size: 10.5pt;
        }

        /* HEADER */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .brand-name {
            font-size: 16pt;
            font-weight: bold;
            color: #1a3d8f;
            margin: 0;
        }
        .brand-sub {
            font-size: 8pt;
            color: #888888;
            margin: 0;
        }
        .header-title {
            text-align: center;
        }
        .report-title {
            font-size: 15pt;
            font-weight: bold;
            color: #1a3d8f;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .report-subtitle {
            font-size: 9pt;
            color: #1a3d8f;
            letter-spacing: 1px;
            margin: 2px 0 0 0;
        }
        .header-meta {
            text-align: right;
            font-size: 8.5pt;
            color: #444444;
        }
        .header-rule {
            border: none;
            border-top: 2px solid #1a3d8f;
            margin: 0 0 18px 0;
        }

        /* BAGIAN 1: FOTO + IDENTITAS */
        .section-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .photo-cell {
            width: 42%;
            vertical-align: top;
            padding-right: 18px;
        }
        .inventory-photo {
            width: 100%;
            max-height: 230px;
            object-fit: contain;
            border: 1px solid #e0e0e0;
            display: block;
        }
        .no-photo-placeholder {
            width: 100%;
            height: 180px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            text-align: center;
            line-height: 180px;
            color: #999999;
            font-size: 9pt;
        }
        .identity-cell {
            vertical-align: top;
        }
        .identity-table {
            width: 100%;
            border-collapse: collapse;
        }
        .identity-table td {
            padding: 9px 0;
            border-bottom: 1px solid #eeeeee;
            font-size: 10pt;
        }
        .identity-label {
            color: #666666;
            width: 40%;
        }
        .identity-value {
            font-weight: bold;
            color: #111111;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 9pt;
            font-weight: bold;
            border: 1px solid;
        }
        .status-tersedia  { background-color: #e8f7ee; color: #1e7e34; border-color: #b7e4c7; }
        .status-dipinjam  { background-color: #e7f0fe; color: #0854a0; border-color: #b6d4fe; }
        .status-perbaikan { background-color: #fff6e0; color: #a66a00; border-color: #ffe08a; }
        .status-rusak     { background-color: #fdeceb; color: #c0392b; border-color: #f3b8b3; }
        .status-hilang    { background-color: #ececed; color: #343a40; border-color: #c8cacc; }

        /* BAGIAN 2: DESKRIPSI */
        .box-section {
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 14px 16px;
            margin-bottom: 18px;
        }
        .box-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1a3d8f;
            margin: 0 0 8px 0;
            text-transform: uppercase;
        }
        .box-body {
            font-size: 9.5pt;
            color: #333333;
        }
        .box-body-empty {
            font-size: 9.5pt;
            color: #999999;
            font-style: italic;
        }

        /* BAGIAN 3: TABEL + QR CODE */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
        }
        .attr-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        .attr-table th {
            background-color: #eef2fb;
            text-align: left;
            padding: 6px 8px;
            font-size: 8.5pt;
            border: 1px solid #e0e0e0;
        }
        .attr-table td {
            padding: 6px 8px;
            border: 1px solid #e0e0e0;
        }
        .qr-cell {
            width: 34%;
            text-align: center;
            vertical-align: top;
            padding-left: 18px;
        }
        .qr-code-img {
            width: 120px;
            height: 120px;
            display: block;
            margin: 0 auto 8px auto;
            border: 1px solid #e0e0e0;
            padding: 6px;
        }
        .qr-caption {
            font-size: 8pt;
            color: #777777;
        }

        /* FOOTER */
        .footer-container {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            border-top: 2px solid #1a3d8f;
            padding-top: 10px;
            font-size: 8.5pt;
            color: #666666;
        }
        .footer-table {
            width: 100%;
        }
        .footer-right {
            text-align: right;
            font-size: 9pt;
            font-weight: bold;
            color: #1a3d8f;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 30%;">
                {{-- foto logo perusahaan --}}
                @php
                    $logoPath = public_path('image/logoAP.png');
                    $logoBase64 = '';
                    if (file_exists($logoPath)) {
                        $logoType = mime_content_type($logoPath);
                        $logoBase64 = 'data:' . $logoType . ';base64,' . base64_encode(file_get_contents($logoPath));
                    }
                @endphp

                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo" style="width: 180px; height: auto;">
                @else
                    <p class="brand-name">{{ config('app.name', 'TESSIS') }}</p>
                @endif


                {{-- <p class="brand-sub">CV. Arindra Production</p> --}}

            </td>
            <td class="header-title" style="width: 40%;">
                <p class="report-title">INVENTORY REPORT</p>
                {{-- <p class="report-subtitle">LAPORAN INFORMASI ASET</p> --}}
            </td>
            <td class="header-meta" style="width: 30%;">
                Generated :<br>
                {{ $exportDate }}
            </td>
        </tr>
    </table>
    <hr class="header-rule">

    <!-- Bagian 1: Foto Barang & Informasi Identitas -->
    <table class="section-table">
        <tr>
            <td class="photo-cell">
                @if($inventory->image && file_exists(storage_path('app/public/' . $inventory->image)))
                    <img src="{{ storage_path('app/public/' . $inventory->image) }}" class="inventory-photo">
                @else
                    <div class="no-photo-placeholder">Tidak ada foto aset barang</div>
                @endif
            </td>
            <td class="identity-cell">
                <table class="identity-table">
                    <tr>
                        <td class="identity-label">Nama Barang</td>
                        <td class="identity-value">{{ $inventory->name }}</td>
                    </tr>
                    <tr>
                        <td class="identity-label">Serial Number</td>
                        <td class="identity-value">{{ $inventory->serial_number }}</td>
                    </tr>
                    <tr>
                        <td class="identity-label">Status</td>
                        <td>
                            @php
                                $statusSlug = match($inventory->status) {
                                    'Dipinjam'  => 'dipinjam',
                                    'Perbaikan' => 'perbaikan',
                                    'Rusak'     => 'rusak',
                                    'Hilang'    => 'hilang',
                                    default     => 'tersedia',
                                };
                            @endphp
                            <span class="status-badge status-{{ $statusSlug }}">{{ $inventory->status ?? 'Tersedia' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="identity-label">Brand</td>
                        <td class="identity-value">{{ $inventory->brand ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="identity-label">Tanggal Input</td>
                        <td class="identity-value">{{ $inventory->created_at ? $inventory->created_at->translatedFormat('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="identity-label">Terakhir Update</td>
                        <td class="identity-value">{{ $inventory->updated_at ? $inventory->updated_at->translatedFormat('d F Y') : '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Bagian 2: Deskripsi Barang -->
    <div class="box-section">
        <p class="box-title">Deskripsi Barang</p>
        @if(!empty($inventory->description))
            <div class="box-body">{{ $inventory->description }}</div>
        @else
            <div class="box-body-empty">Belum ada deskripsi.</div>
        @endif
    </div>

    <!-- Bagian 3: Informasi Tambahan (jika ada) & QR Code -->
    <table class="bottom-table">
        <tr>
            <td style="vertical-align: top;">
                @if($inventory->attributes && $inventory->attributes->count() > 0)
                    <div class="box-section" style="margin-bottom: 0;">
                        <p class="box-title">Informasi Tambahan</p>
                        <table class="attr-table">
                            <tr>
                                <th>Nama Informasi</th>
                                <th>Nilai</th>
                            </tr>
                            @foreach($inventory->attributes as $attr)
                                <tr>
                                    <td>{{ $attr->attribute_name }}</td>
                                    <td>{{ $attr->attribute_value }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </td>
            <td class="qr-cell">
                <div class="box-section" style="margin-bottom: 0;">
                    <p class="box-title">QR Code</p>
                    @if($inventory->qr_code && file_exists(storage_path('app/public/' . $inventory->qr_code)))
                        <img src="{{ storage_path('app/public/' . $inventory->qr_code) }}" class="qr-code-img">
                        <div class="qr-caption" style="font-weight: bold; color: #333333;">{{ $inventory->serial_number }}</div>
                        <div class="qr-caption">Scan QR code di atas<br>untuk melihat detail aset<br>secara lengkap.</div>
                    @else
                        <div class="no-photo-placeholder" style="height: 120px; line-height: 120px;">QR belum tersedia</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-container">
        <table class="footer-table">
            <tr>
                <td>
                    Dicetak oleh<br>
                    {{ auth()->user()->name ?? 'Admin' }}<br>
                    {{ $exportDate }}
                </td>
                <td class="footer-right">
                    CV. Arindra Production<br>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
