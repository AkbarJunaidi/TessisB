<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Seluruh Aset Inventaris</title>
    @include('inventory.pdf.partials.all-head')
</head>
<body>

    @php
        $kopAtasPath = \App\Models\AppSetting::imagePath('kop_atas');
        $kopAtasBase64 = file_exists($kopAtasPath)
            ? 'data:' . mime_content_type($kopAtasPath) . ';base64,' . base64_encode(file_get_contents($kopAtasPath))
            : '';

        $kopBawahPath = \App\Models\AppSetting::imagePath('kop_bawah');
        $kopBawahBase64 = file_exists($kopBawahPath)
            ? 'data:' . mime_content_type($kopBawahPath) . ';base64,' . base64_encode(file_get_contents($kopBawahPath))
            : '';
    @endphp

    @if($kopAtasBase64)
        <div class="kop-atas"><img src="{{ $kopAtasBase64 }}" alt="Kop Atas"></div>
    @endif
    @if($kopBawahBase64)
        <div class="kop-bawah"><img src="{{ $kopBawahBase64 }}" alt="Kop Bawah"></div>
    @endif

    {{-- $bodyHtml adalah gabungan HTML per-item yang sudah dirender bertahap
    lewat beberapa request kecil (lihat InventoryService::processAllReportBatch) -
    sengaja dicetak mentah (bukan di-escape), karena isinya sudah HTML valid
    hasil render Blade partial yang sama seperti all.blade.php. --}}
    {!! $bodyHtml !!}

</body>
</html>
