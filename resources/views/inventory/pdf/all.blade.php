<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>All Inventory Asset Report Bundle</title>
    @include('inventory.pdf.partials.all-head')
</head>
<body>

    @foreach($inventories as $inventory)
        @include('inventory.pdf.partials.all-item', ['inventory' => $inventory, 'exportDate' => $exportDate])
    @endforeach

</body>
</html>
