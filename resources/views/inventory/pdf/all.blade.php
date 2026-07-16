<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>All Inventory Report Bundle</title>
    <style>
        /* RESET & DEFINE BASE KERTAS A4 PORTRAIT */
        @page {
            size: a4 portrait;
            margin: 2cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            font-size: 12pt;
        }

        /* CORE ARCHITECTURE ENGINE: PAGE BREAK SETTING UNTUK MASAL REPORT */
        .page-bundle {
            page-break-after: always;
        }
        .page-bundle:last-child {
            page-break-after: avoid;
        }

        /* DOKUMEN HEADER */
        .header-container {
            border-bottom: 3px double #111111;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .report-title {
            font-size: 26pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
            color: #111111;
        }
        .meta-date {
            font-size: 10pt;
            color: #666666;
            margin-top: 5px;
        }

        /* INFORMASI UTAMA BARANG */
        .item-title-badge {
            font-size: 20pt;
            font-weight: bold;
            color: #111111;
            margin: 0 0 10px 0;
            text-transform: uppercase;
        }
        .serial-number-badge {
            display: inline-block;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12pt;
            font-weight: bold;
            background-color: #f5f5f5;
            padding: 5px 12px;
            border: 1px solid #cccccc;
            color: #444444;
            margin-bottom: 30px;
        }

        /* VISUAL ASSETS CONTAINER (GAMBAR & BARCODE) */
        .media-section {
            margin-bottom: 40px;
            width: 100%;
        }
        .box-image {
            float: left;
            width: 55%;
        }
        .box-qrcode {
            float: right;
            width: 35%;
            text-align: center;
            background-color: #fafafa;
            border: 1px dashed #dddddd;
            padding: 20px;
        }
        .clear {
            clear: both;
        }

        /* SPESIFIKASI DIMENSI GAMBAR ASLI BARANG */
        .inventory-photo {
            width: 100%;
            max-height: 250px;
            object-fit: contain;
            border: 1px solid #e0e0e0;
            display: block;
        }
        .no-photo-placeholder {
            width: 100%;
            height: 200px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            text-align: center;
            line-height: 200px;
            color: #999999;
            font-size: 10pt;
        }

        /* SPESIFIKASI DIMENSI QR CODE FISIK */
        .qr-code-img {
            width: 140px;
            height: 140px;
            display: block;
            margin: 0 auto 10px auto;
        }
        .qr-caption {
            font-family: monospace;
            font-size: 10pt;
            font-weight: bold;
            color: #222222;
        }

        /* DOKUMEN FOOTER ABSOLUTE */
        .footer-container {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            text-align: center;
            border-top: 1px solid #dddddd;
            padding-top: 10px;
            font-size: 9pt;
            color: #888888;
        }
    </style>
</head>
<body>

    @foreach($inventories as $inventory)
        <div class="page-bundle">
            <div class="header-container">
                <h1 class="report-title">Inventory Report</h1>
                <div class="meta-date">Tanggal Cetak: {{ $exportDate }}</div>
            </div>

            <h2 class="item-title-badge">{{ $inventory->name }}</h2>
            <div class="serial-number-badge">S/N: {{ $inventory->serial_number }}</div>

            <div class="media-section">
                <div class="box-image">
                    @if($inventory->image && file_exists(storage_path('app/public/' . $inventory->image)))
                        <img src="{{ storage_path('app/public/' . $inventory->image) }}" class="inventory-photo">
                    @else
                        <div class="no-photo-placeholder">TIDAK ADA FOTO ASSET BARANG</div>
                    @endif
                </div>

                <div class="box-qrcode">
                    @if($inventory->qr_code && file_exists(storage_path('app/public/' . $inventory->qr_code)))
                        <img src="{{ storage_path('app/public/' . $inventory->qr_code) }}" class="qr-code-img">
                        <div class="qr-caption">{{ $inventory->serial_number }}</div>
                    @else
                        <div style="color: red; font-size: 11pt; font-weight: bold; padding: 50px 0;">QR NOT FOUND</div>
                    @endif
                </div>

                <div class="clear"></div>
            </div>
        </div>
    @endforeach

    <div class="footer-container">
        CV. Arindra Production
    </div>

</body>
</html>
