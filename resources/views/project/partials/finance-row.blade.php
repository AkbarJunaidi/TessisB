{{-- Partial: 1 baris item keuangan (Pendapatan/Pengeluaran)
     Variabel: $group ('incomes'|'expenses'), $index, $item (ProjectFinanceItem) --}}
<div class="row g-2 mb-2 finance-row">
    <div class="col-4">
        <input type="text" inputmode="numeric"
               name="{{ $group }}[{{ $index }}][amount]"
               class="form-control form-control-sm finance-amount-input"
               value="{{ number_format((float) $item->amount, 0, ',', '.') }}" placeholder="0" required>
    </div>
    <div class="col-7">
        <input type="text"
               name="{{ $group }}[{{ $index }}][description]"
               class="form-control form-control-sm"
               value="{{ $item->description }}" placeholder="Keterangan">
    </div>
    <div class="col-1">
        <button type="button" class="btn btn-sm btn-outline-danger remove-finance-row">
            <i class="bi bi-trash"></i>
        </button>
    </div>
</div>
