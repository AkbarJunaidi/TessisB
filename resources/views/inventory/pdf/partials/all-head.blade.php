<style>
    @page {
        size: a4 portrait;
        margin: 0; /* full-bleed: kop atas & bawah 100% lebar halaman, sama seperti single report */
    }
    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #333333;
        line-height: 1.5;
        margin: 0;
        padding: 0;
        font-size: 10.5pt;
    }

    /* KOP ATAS & BAWAH (letterhead) - tampil berulang di tiap halaman DOMPDF,
       sama persis dengan resources/views/inventory/pdf/single.blade.php */
    .kop-atas, .kop-bawah {
        position: fixed;
        left: 0;
        right: 0;
        width: 100%;
    }
    .kop-atas {
        top: 0;
    }
    .kop-bawah {
        bottom: 0;
    }
    .kop-atas img, .kop-bawah img {
        width: 100%;
        display: block;
    }

    .page-bundle {
        /* Padding atas/bawah pas tinggi kop (sama seperti single report),
           kiri/kanan 1.8cm - supaya konten tidak ketiban/kepotong gambar
           kop-atas & kop-bawah yang position: fixed */
        padding: 4.8cm 1.8cm;
        page-break-after: always;
    }
    .page-bundle:last-child {
        page-break-after: avoid;
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
</style>
