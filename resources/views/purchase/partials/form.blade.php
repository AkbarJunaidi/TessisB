@csrf

@php
    $items = old('items') ?? (isset($purchase)
        ? $purchase->items->map(fn ($row) => [
            'name'         => $row->name,
            'qty'          => $row->qty,
            'unit_price'   => $row->unit_price,
            'stock_mode'   => $row->stock_mode,
            'inventory_id' => $row->inventory_id,
            'brand'        => $row->brand,
        ])->all()
        : [[]]);
    $nextIndex = empty($items) ? 0 : max(array_keys($items)) + 1;
@endphp

@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        <ul class="mb-0 small">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm rounded-3 mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Informasi Pembelian</h6>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                <select name="vendor_id" id="vendor_id" class="form-select" required>
                    <option value="">- Pilih vendor -</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $purchase->vendor_id ?? '') === (string) $vendor->id)>
                            {{ $vendor->name }}{{ $vendor->company ? ' - ' . $vendor->company : '' }}
                        </option>
                    @endforeach
                </select>
                @if($vendors->isEmpty())
                    <div class="form-text text-danger">Belum ada kontak bertag Vendor.</div>
                @endif
                @if(auth()->user()->hasPermission('kontak', 'create'))
                    <div class="form-text">
                        <a href="{{ route('contacts.create', ['type' => 'vendor']) }}">Tambah vendor baru</a>
                    </div>
                @endif
            </div>

            <div class="col-md-6">
                <label for="project_id" class="form-label">Project</label>
                <select name="project_id" id="project_id" class="form-select">
                    <option value="">- Stok umum (tanpa project) -</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) old('project_id', $purchase->project_id ?? '') === (string) $project->id)>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label for="purchase_date" class="form-label">Tanggal Pembelian <span class="text-danger">*</span></label>
                <input type="date" name="purchase_date" id="purchase_date" class="form-control"
                       value="{{ old('purchase_date', isset($purchase) ? $purchase->purchase_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
            </div>

            <div class="col-md-8">
                <label for="attachment" class="form-label">Lampiran Nota / Bukti</label>
                <input type="file" name="attachment" id="attachment" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                <div class="form-text">JPG, PNG, WEBP, atau PDF. Maksimal 5MB.</div>
                @if(isset($purchase) && $purchase->attachment)
                    <div class="form-check mt-1">
                        <input type="checkbox" name="remove_attachment" id="remove_attachment" value="1" class="form-check-input">
                        <label class="form-check-label small" for="remove_attachment">
                            Hapus lampiran saat ini
                            (<a href="{{ route('purchases.attachment', $purchase) }}" target="_blank" rel="noopener">lihat</a>)
                        </label>
                    </div>
                @endif
            </div>

            <div class="col-12">
                <label for="notes" class="form-label">Catatan</label>
                <textarea name="notes" id="notes" rows="2" class="form-control" maxlength="2000">{{ old('notes', $purchase->notes ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Item Pembelian</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addItem">
                <i class="bi bi-plus-lg"></i> Tambah Item
            </button>
        </div>

        <div id="itemList">
            @foreach($items as $index => $item)
                @include('purchase.partials.item-row', ['i' => $index, 'item' => $item])
            @endforeach
        </div>

        <div class="d-flex justify-content-end align-items-center gap-3 border-top pt-3">
            <span class="text-muted">Total</span>
            <span class="fw-bold fs-5" id="purchaseTotal">Rp 0</span>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ isset($purchase) ? route('purchases.show', $purchase) : route('purchases.index') }}" class="btn btn-outline-secondary">Batal</a>
    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
</div>

<template id="itemTemplate">
    @include('purchase.partials.item-row', ['i' => '__INDEX__', 'item' => null])
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('itemList');
    const template = document.getElementById('itemTemplate');
    const totalEl = document.getElementById('purchaseTotal');
    let nextIndex = {{ $nextIndex }};

    const toNumber = (value) => {
        const digits = String(value).replace(/\D/g, '');
        return digits === '' ? 0 : parseInt(digits, 10);
    };
    const rupiah = (value) => 'Rp ' + Math.round(value).toLocaleString('id-ID');
    const repairCosts = @json($repairCosts ?? []);
    const warnRatio = {{ \App\Services\Inventory\RepairService::REPLACE_WARN_RATIO }};
    const esc = (text) => { const d = document.createElement('div'); d.textContent = text == null ? '' : String(text); return d.innerHTML; };

    // Perbandingan beli baru vs riwayat servis, hanya untuk mode "Tambah stok barang yang ada".
    function renderCompare(row) {
        const box = row.querySelector('.item-compare');
        const inventoryId = row.querySelector('.item-inventory').value;

        if (row.querySelector('.item-mode').value !== 'existing' || !inventoryId) {
            box.classList.add('d-none');
            box.innerHTML = '';
            return;
        }

        const qty = parseInt(row.querySelector('.item-qty').value, 10) || 0;
        const price = toNumber(row.querySelector('.item-price').value);
        const info = repairCosts[inventoryId];
        const cell = (label, value) => `<div class="col-6 col-md-3"><div class="text-muted">${label}</div><div class="fw-semibold">${value}</div></div>`;

        let html = '<div class="row g-2">'
            + cell('Harga beli baru / unit', price > 0 ? rupiah(price) : '-')
            + cell('Total beli baru (' + qty + ' unit)', price > 0 ? rupiah(price * qty) : '-');

        if (info) {
            html += cell('Harga beli terakhir', info.last_price !== null
                ? rupiah(info.last_price) + ' <span class="text-muted fw-normal">(' + esc(info.last_code) + ')</span>' : '-');
            html += cell('Total biaya servis', info.servis_count > 0
                ? rupiah(info.servis_total) + ' <span class="text-muted fw-normal">(' + info.servis_count + 'x servis)</span>' : '-');
        }
        html += '</div>';

        if (info && info.worst_unit) {
            const w = info.worst_unit;
            const ratio = price > 0 ? w.total / price : null;
            const pct = ratio === null ? '' : ' (' + Math.round(ratio * 100) + '% dari harga beli baru)';

            if (ratio !== null && ratio >= warnRatio) {
                html += '<div class="text-danger fw-semibold mt-2"><i class="bi bi-exclamation-triangle me-1"></i>Servis Unit #'
                    + w.number + ' sudah ' + rupiah(w.total) + pct + '. Pertimbangkan mengganti dengan yang baru.</div>';
            } else {
                html += '<div class="text-muted mt-2">Unit paling mahal diservis: Unit #' + w.number + ' ' + rupiah(w.total) + pct + '</div>';
            }
        } else if (!info) {
            html += '<div class="text-muted mt-2">Belum ada riwayat servis atau harga beli untuk barang ini.</div>';
        }

        box.innerHTML = '<div class="rounded-3 border bg-light p-2 small">' + html + '</div>';
        box.classList.remove('d-none');
    }

    function recalculate() {
        let total = 0;
        list.querySelectorAll('.purchase-item').forEach(function (row) {
            const qty = parseInt(row.querySelector('.item-qty').value, 10) || 0;
            const subtotal = qty * toNumber(row.querySelector('.item-price').value);
            row.querySelector('.item-subtotal').textContent = rupiah(subtotal);
            renderCompare(row);
            total += subtotal;
        });
        totalEl.textContent = rupiah(total);
    }

    function syncMode(row) {
        const mode = row.querySelector('.item-mode').value;
        row.querySelector('.item-existing').classList.toggle('d-none', mode !== 'existing');
        row.querySelector('.item-new').classList.toggle('d-none', mode !== 'new');
    }

    document.getElementById('addItem').addEventListener('click', function () {
        list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++));
        recalculate();
    });

    list.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-item');
        if (btn && list.querySelectorAll('.purchase-item').length > 1) {
            btn.closest('.purchase-item').remove();
            recalculate();
        }
    });

    list.addEventListener('change', function (e) {
        const row = e.target.closest('.purchase-item');
        if (e.target.classList.contains('item-mode')) {
            syncMode(row);
            renderCompare(row);
        }
        if (e.target.classList.contains('item-inventory')) {
            const nameInput = row.querySelector('.item-name');
            const selected = e.target.selectedOptions[0];
            if (selected && selected.dataset.name && nameInput.value.trim() === '') {
                nameInput.value = selected.dataset.name;
            }
            renderCompare(row);
        }
    });

    list.addEventListener('input', function (e) {
        if (e.target.classList.contains('item-price')) {
            const fromEnd = e.target.value.length - e.target.selectionStart;
            const value = toNumber(e.target.value);
            e.target.value = value === 0 ? '' : value.toLocaleString('id-ID');
            const pos = Math.max(e.target.value.length - fromEnd, 0);
            e.target.setSelectionRange(pos, pos);
        }
        recalculate();
    });

    recalculate();
});
</script>
@endpush
