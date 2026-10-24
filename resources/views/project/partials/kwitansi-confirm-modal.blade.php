{{-- Popup konfirmasi sebelum kwitansi disimpan: kelebihan bayar dan/atau pemasukan manual yang mungkin pembayaran yang sama.
     Dibuka otomatis dari flash kwitansi_confirm (KwitansiController::store); letakkan di luar tab-pane. --}}
@php
    $confirm = session('kwitansi_confirm');
    $rp = fn ($value) => \App\Support\Money::formatRupiah($value);
@endphp

@if($confirm)
    <div class="modal fade" id="kwitansiConfirmModal" tabindex="-1" data-bs-backdrop="static" aria-labelledby="kwitansiConfirmTitle">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ $confirm['action'] }}" method="POST">
                    @csrf
                    <input type="hidden" name="confirmed" value="1">
                    @foreach($confirm['input'] as $name => $value)
                        @continue($value === null || $value === '')
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach

                    <div class="modal-header">
                        <h6 class="modal-title" id="kwitansiConfirmTitle">Periksa dulu sebelum menyimpan</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        @if(! empty($confirm['candidates']))
                            <p class="small mb-2">
                                Di project ini sudah ada pemasukan manual senilai <strong>{{ $rp($confirm['amount']) }}</strong>.
                                Apakah itu pembayaran yang sama dengan kwitansi ini?
                            </p>
                            @foreach($confirm['candidates'] as $candidate)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="link_item_id" id="kcLink{{ $candidate['id'] }}" value="{{ $candidate['id'] }}" required>
                                    <label class="form-check-label small" for="kcLink{{ $candidate['id'] }}">
                                        Ya, pembayaran yang sama
                                        <span class="d-block text-muted">{{ $candidate['tanggal'] }} - {{ $candidate['description'] ?: 'tanpa keterangan' }}</span>
                                    </label>
                                </div>
                            @endforeach
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="link_item_id" id="kcLinkNew" value="" required>
                                <label class="form-check-label small" for="kcLinkNew">
                                    Bukan, ini pembayaran berbeda
                                    <span class="d-block text-muted">Dicatat sebagai pemasukan baru di buku kas.</span>
                                </label>
                            </div>
                            <div class="form-text mb-3">Bila ditautkan, pemasukan itu menjadi baris otomatis Kwitansi (terkunci) dan ikut terhapus jika kwitansi dibatalkan.</div>
                        @endif

                        @if(! empty($confirm['overpay']))
                            <div class="alert alert-warning small mb-0">
                                {{ ! empty($confirm['candidates']) ? 'Jika dicatat sebagai pembayaran berbeda, total' : 'Total' }}
                                diterima menjadi <strong>{{ $rp($confirm['overpay']['total']) }}</strong>, melebihi Estimasi Pendapatan
                                {{ $rp($confirm['overpay']['estimate']) }} (lebih {{ $rp($confirm['overpay']['excess']) }}).
                                Jika kontrak berubah, naikkan estimasi di Data Keuangan project.
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Lanjut Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.getElementById('kwitansiConfirmModal');
                if (el) new bootstrap.Modal(el).show();
            });
        </script>
    @endpush
@endif
