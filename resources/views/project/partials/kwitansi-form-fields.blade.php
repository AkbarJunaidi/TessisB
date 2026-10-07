{{-- Field kategori pembayaran (radio) dan pilih tanda tangan; dipakai 2x di kwitansi-content. Variabel: $userSignatures. --}}

<div class="col-12">
    <label class="form-label small d-block">Untuk Pembayaran</label>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="kategori_pembayaran" id="kp_booking_{{ $uniqueId }}" value="booking_fee">
        <label class="form-check-label small" for="kp_booking_{{ $uniqueId }}">Booking Fee</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="kategori_pembayaran" id="kp_dp_{{ $uniqueId }}" value="dp">
        <label class="form-check-label small" for="kp_dp_{{ $uniqueId }}">Down Payment (DP)</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="kategori_pembayaran" id="kp_lunas_{{ $uniqueId }}" value="pelunasan">
        <label class="form-check-label small" for="kp_lunas_{{ $uniqueId }}">Pelunasan</label>
    </div>
</div>

<div class="col-md-6">
    <label class="form-label small">Tanda Tangan</label>
    <select name="signature_id" class="form-select form-select-sm">
        <option value="">Tanpa tanda tangan</option>
        @foreach($userSignatures as $sig)
            <option value="{{ $sig->id }}" @selected($sig->is_default)>{{ $sig->label }}{{ $sig->is_default ? ' (Default)' : '' }}</option>
        @endforeach
    </select>
    @if($userSignatures->isEmpty())
        <div class="form-text">Belum ada tanda tangan tersimpan - <a href="{{ route('signature.index') }}" target="_blank">buat di sini</a>.</div>
    @endif
</div>
