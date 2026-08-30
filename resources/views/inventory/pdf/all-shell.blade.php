<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>All Inventory Asset Report Bundle</title>
    @include('inventory.pdf.partials.all-head')
</head>
<body>

    {{-- $bodyHtml adalah gabungan HTML per-item yang sudah dirender bertahap
    lewat beberapa request kecil (lihat InventoryService::processAllReportBatch) -
    sengaja dicetak mentah (bukan di-escape), karena isinya sudah HTML valid
    hasil render Blade partial yang sama seperti all.blade.php. --}}
    {!! $bodyHtml !!}

</body>
</html>
