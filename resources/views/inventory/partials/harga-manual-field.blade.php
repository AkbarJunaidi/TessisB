{{-- Harga beli per unit; hanya bagi yang boleh melihat Pembelian. Mengikuti Pembelian terakhir bila ada, selain itu diisi manual. --}}
@if(auth()->user()->hasPermission('purchase', 'view'))
    @php
        $pembelian = (($latestPrice ?? null)['source'] ?? null) === 'pembelian' ? $latestPrice : null;
    @endphp
    <div class="col-12 col-md-6">
        <label for="hargaManual" class="form-label fw-semibold small text-secondary">
            Harga Beli per Unit @unless($pembelian)<span class="fw-normal">(opsional)</span>@endunless
        </label>
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            @if($pembelian)
                {{-- Disabled agar tidak ikut terkirim; harga berubah lewat Pembelian. --}}
                <input type="text" id="hargaManual" class="form-control" value="{{ number_format($pembelian['price'], 0, ',', '.') }}" disabled>
            @else
                <input type="text"
                       inputmode="numeric"
                       name="harga_manual"
                       id="hargaManual"
                       class="form-control @error('harga_manual') is-invalid @enderror"
                       placeholder="0"
                       autocomplete="off"
                       value="{{ old('harga_manual', isset($inventory) && $inventory->harga_manual ? number_format((float) $inventory->harga_manual, 0, ',', '.') : '') }}">
            @endif
        </div>
        @error('harga_manual')
            <div class="invalid-feedback d-block">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
            </div>
        @enderror
        <div class="form-text">
            @if($pembelian)
                Mengikuti Pembelian terakhir (<a href="{{ route('purchases.show', $pembelian['purchase_id']) }}" class="text-decoration-none">{{ $pembelian['code'] }}</a>). Berubah otomatis saat ada pembelian baru.
            @else
                Hanya dipakai bila barang belum pernah dibeli lewat Pembelian.
            @endif
        </div>
    </div>
@endif
