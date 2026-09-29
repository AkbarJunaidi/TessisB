@php
    $mode = $item['stock_mode'] ?? 'none';
    $price = $item['unit_price'] ?? '';
    $priceDisplay = $price === '' ? '' : number_format((float) $price, 0, ',', '.');
@endphp
<div class="border rounded-3 p-2 mb-2 purchase-item">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1">Nama Item</label>
            <input type="text" name="items[{{ $i }}][name]" class="form-control form-control-sm item-name" value="{{ $item['name'] ?? '' }}" maxlength="150" required>
        </div>
        <div class="col-4 col-md-2">
            <label class="form-label small mb-1">Jumlah</label>
            <input type="number" name="items[{{ $i }}][qty]" class="form-control form-control-sm item-qty" min="1" max="10000" value="{{ $item['qty'] ?? 1 }}" required>
        </div>
        <div class="col-8 col-md-3">
            <label class="form-label small mb-1">Harga Satuan (Rp)</label>
            <input type="text" inputmode="numeric" name="items[{{ $i }}][unit_price]" class="form-control form-control-sm item-price" value="{{ $priceDisplay }}" placeholder="0" required>
        </div>
        <div class="col-8 col-md-2">
            <label class="form-label small mb-1">Subtotal</label>
            <div class="form-control form-control-sm bg-light item-subtotal">Rp 0</div>
        </div>
        <div class="col-4 col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Hapus item"><i class="bi bi-trash"></i></button>
        </div>
    </div>

    <div class="row g-2 mt-1">
        <div class="col-md-5">
            <select name="items[{{ $i }}][stock_mode]" class="form-select form-select-sm item-mode">
                @foreach(\App\Models\PurchaseItem::STOCK_MODES as $value => $label)
                    <option value="{{ $value }}" @selected($mode === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-7 item-existing {{ $mode === 'existing' ? '' : 'd-none' }}">
            <select name="items[{{ $i }}][inventory_id]" class="form-select form-select-sm item-inventory">
                <option value="">- Pilih barang Inventory -</option>
                @foreach($inventories as $inventory)
                    <option value="{{ $inventory->id }}" data-name="{{ $inventory->name }}" @selected((string) ($item['inventory_id'] ?? '') === (string) $inventory->id)>
                        {{ $inventory->name }} ({{ $inventory->serial_number }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-7 item-new {{ $mode === 'new' ? '' : 'd-none' }}">
            <input type="text" name="items[{{ $i }}][brand]" class="form-control form-control-sm" value="{{ $item['brand'] ?? '' }}" maxlength="100" placeholder="Brand (opsional). Serial number diisi saat barang diterima.">
        </div>
    </div>
</div>
