<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PDF Label - {{ $inventory->serial_number }}</title>
    <style>
        /* SETTING PENGATURAN KERTAS DOMPDF: Menghilangkan margin bawaan driver printer */
        @page {
            margin: 0px !important;
        }

        /* CORE RESOLUSI DIMENSI: Mengunci ukuran fisik stiker 100mm x 50mm dalam format Point (pt) */
        html, body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0px;
            padding: 0px;
            width: 283.46pt;
            height: 141.73pt;
            background-color: #ffffff;
        }

        /* CONTAINER INDUK LUAR: Tabel pembungkus tinggi 100% untuk meniru area layout stiker */
        .wrapper-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            margin: 0px;
            padding: 0px;
        }

        /* ENGINE VERTICAL CENTER: Memaksa tabel konten dalam otomatis turun tepat di tengah stiker */
        .main-container-cell {
            width: 100%;
            height: 100%;
            vertical-align: middle;
            text-align: center;
            padding: 0px;
        }

        /* TABEL KONTEN DALAM: Membatasi lebar 92% agar layout aman dari batas potong pisau printer */
        .inner-table {
            width: 92%;
            margin-left: auto;
            margin-right: auto;
            border-collapse: collapse;
        }

        /* SISI KIRI (KOLOM QR CODE): Mengatur porsi area 42% dan memberi batas garis putus-putus */
        .td-qr {
            width: 42%;
            text-align: center;
            vertical-align: middle;
            padding-right: 15px;
            border-right: 2px dashed #dddddd;
        }

        /* SISI KANAN (KOLOM INFORMASI TEKS): Mengatur porsi area 58% dengan format teks rata kiri */
        .td-text {
            width: 58%;
            vertical-align: middle;
            padding-left: 20px;
            text-align: left;
            position: relative; /* Menjadi acuan patokan posisi logo */
        }

        /* WATERMARK LOGO AP3 (Center Presisi & Opacity 54%) */
        .bg-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 180px;
            height: auto;
            margin-top: -120px;  /* Setengah dari perkiraan tinggi logo untuk center vertikal */
            margin-left: -85px;  /* Menggeser setengah lebar logo agar pas tengah di kolom teks */
            opacity: 0.44;
            z-index: 1;
        }

        /* KONTEN TEKS (Di atas logo) */
        .content-container {
            position: relative;
            z-index: 2;
        }

        /* DRAF ELEMEN QR: Dimensi gambar matriks QR code agar konsisten dan tajam */
        .qr-image {
            width: 105px;
            height: 105px;
            display: block;
            margin: 0 auto;
        }

        /* DRAF ELEMEN NAMA BARANG: Font tebal kapital dengan pembatasan tinggi spasi teks */
        .item-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0px 0px 5px 0px;
            color: #111111;
            line-height: 1.2;
        }

        /* DRAF ELEMEN BADGE SERIAL: Font monospace abu-abu untuk kemudahan verifikasi visual */
        .serial-badge {
            display: inline-block;
            font-family: monospace;
            font-size: 13px;
            background-color: #f5f5f5;
            padding: 4px 8px;
            border: 1px solid #cccccc;
            color: #555555;
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <table class="wrapper-table" width="100%" height="100%">
        <tr height="100%">
            <td class="main-container-cell" align="center" valign="middle">

                <table class="inner-table" align="center">
                    <tr>
                        <td class="td-qr">
                            @if($inventory->qr_code && file_exists(storage_path('app/public/' . $inventory->qr_code)))
                                <img src="{{ storage_path('app/public/' . $inventory->qr_code) }}" class="qr-image">
                            @else
                                <div style="color: red; font-size: 10px; font-weight: bold;">QR NOT FOUND</div>
                            @endif
                        </td>

                        <td class="td-text">
                            @php
                                $logoPath = public_path('image/LogoAP3.png');
                                $logoSrc = file_exists($logoPath)
                                    ? 'data:' . mime_content_type($logoPath) . ';base64,' . base64_encode(file_get_contents($logoPath))
                                    : null;
                            @endphp

                            @if($logoSrc)
                                <img src="{{ $logoSrc }}" class="bg-watermark">
                            @endif

                            <div class="content-container">
                                <div class="item-name">{{ $inventory->name }}</div>
                                <div class="serial-badge">{{ $inventory->serial_number }}</div>
                            </div>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>
