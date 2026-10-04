<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>All Inventory Asset Report Bundle</title>
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

    @foreach($inventories as $inventory)
        @include('inventory.pdf.partials.all-item', ['inventory' => $inventory, 'exportDate' => $exportDate])
    @endforeach

</body>
</html>
