{{-- Card Jadwal Servis (opsional) untuk form inventory: isi interval hari dan/atau interval pemakaian (per peminjaman). --}}
@php
    $servisSource = $inventory ?? null;
    $servisHari   = old('servis_interval_hari', $servisSource?->servis_interval_hari);
    $servisPakai  = old('servis_interval_pemakaian', $servisSource?->servis_interval_pemakaian);
    $servisOn     = old('use_servis', ($servisHari || $servisPakai) ? '1' : '0');
@endphp
<div class="app-panel mb-4">
    <div class="app-panel-header">
        <div>
            <h5 class="fw-bold text-navy m-0 d-flex align-items-center gap-2">
                <i class="bi bi-tools text-primary"></i> Jadwal Servis
            </h5>
            <p class="text-muted small mb-0 mt-1">Opsional. Pengingat servis/perbaikan berkala per unit.</p>
        </div>
    </div>
    <div class="p-4">
        <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary d-block mb-2">Gunakan Jadwal Servis?</label>

            <input type="hidden" name="use_servis" id="use_servis_hidden" value="{{ $servisOn }}">

            <div class="form-check form-switch">
                <input class="form-check-input u-w-2p75em u-h-1p5em"
                       type="checkbox"
                       role="switch"
                       id="use_servis_toggle"
                      
                       {{ $servisOn == '1' ? 'checked' : '' }}>
                <label class="form-check-label fw-medium text-dark" for="use_servis_toggle">
                    <span id="use_servis_label">{{ $servisOn == '1' ? 'Ya' : 'Tidak' }}</span>
                </label>
            </div>
        </div>

        <div id="servisFields" class="{{ $servisOn == '1' ? '' : 'd-none' }}">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="servis_interval_hari" class="form-label fw-semibold small text-secondary">Berdasarkan waktu</label>
                    <div class="input-group">
                        <span class="input-group-text">Setiap</span>
                        <input type="number"
                               name="servis_interval_hari"
                               id="servis_interval_hari"
                               class="form-control @error('servis_interval_hari') is-invalid @enderror"
                               min="1" max="3650"
                               placeholder="Contoh: 90"
                               value="{{ $servisHari }}">
                        <span class="input-group-text">hari</span>
                        @error('servis_interval_hari')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-text">Dihitung mulai dari hari ini, lalu dari servis terakhir tiap unit.</div>
                </div>

                <div class="col-12 col-md-6">
                    <label for="servis_interval_pemakaian" class="form-label fw-semibold small text-secondary">Berdasarkan pemakaian</label>
                    <div class="input-group">
                        <span class="input-group-text">Setiap</span>
                        <input type="number"
                               name="servis_interval_pemakaian"
                               id="servis_interval_pemakaian"
                               class="form-control @error('servis_interval_pemakaian') is-invalid @enderror"
                               min="1" max="1000"
                               placeholder="Contoh: 10"
                               value="{{ $servisPakai }}">
                        <span class="input-group-text">kali pemakaian</span>
                        @error('servis_interval_pemakaian')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-text">Satu pemakaian = satu kali unit dipinjam (Surat Jalan atau Scan).</div>
                </div>
            </div>
            <p class="text-muted small mt-3 mb-0">
                Isi salah satu atau keduanya. Unit dianggap jatuh tempo begitu salah satu syarat terpenuhi.
                Servis ditandai selesai per unit di bagian Kelola Unit Fisik (halaman Edit).
            </p>
        </div>
    </div>
</div>

<script src="{{ \App\Support\AppAsset::url('js/inventory/partials/service-schedule-fields.js') }}"></script>
