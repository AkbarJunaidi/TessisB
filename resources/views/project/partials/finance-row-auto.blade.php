{{-- Partial: baris keuangan otomatis (Pembelian, Servis), hanya baca. Variabel: $item (ProjectFinanceItem) --}}
<div class="row g-2 mb-2">
    <div class="col-4">
        <input type="text" class="form-control form-control-sm" value="{{ number_format((float) $item->amount, 0, ',', '.') }}" disabled>
    </div>
    <div class="col-7">
        <input type="text" class="form-control form-control-sm" value="{{ $item->description }}" disabled>
    </div>
    <div class="col-1">
        <span class="btn btn-sm btn-outline-secondary disabled w-100" title="Otomatis, ubah lewat modul asalnya"><i class="bi bi-lock"></i></span>
    </div>
</div>
