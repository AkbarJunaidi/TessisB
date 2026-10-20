@php
    $user = auth()->user();
@endphp

@if(($purchase->canOrder() || $purchase->canEditOrder()) && $user->hasPermission('purchase', 'edit'))
    @php
        $editingOrder = $purchase->canEditOrder();
    @endphp
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('purchases.order', $purchase) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_modal" value="orderModal">
                    <div class="modal-header">
                        <h6 class="modal-title">{{ $editingOrder ? 'Info Pesanan' : 'Tandai Dipesan' }} - {{ $purchase->code }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-2">
                        <div class="col-12 small text-muted">
                            {{ $editingOrder ? 'Perbarui estimasi, ekspedisi, atau resi bila ada perubahan.' : 'Pastikan vendor sudah dihubungi dan pesanan sudah dikonfirmasi.' }}
                        </div>

                        @unless($editingOrder)
                            <div class="col-md-6">
                                <label class="form-label small">Tanggal Pesan</label>
                                <input type="date" name="ordered_at" class="form-control" max="{{ now()->format('Y-m-d') }}" value="{{ old('ordered_at', now()->format('Y-m-d')) }}">
                            </div>
                        @endunless
                        <div class="col-md-6">
                            <label class="form-label small">Estimasi Tiba</label>
                            <input type="date" name="expected_at" class="form-control" value="{{ old('expected_at', optional($purchase->expected_at)->format('Y-m-d')) }}">
                        </div>

                        @if($purchase->isOnline())
                            <div class="col-md-6">
                                <label class="form-label small">Nama Toko</label>
                                <input type="text" name="store_name" class="form-control" maxlength="100" value="{{ old('store_name', $purchase->store_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Nomor Pesanan <span class="text-danger">*</span></label>
                                <input type="text" name="order_number" class="form-control" maxlength="100" value="{{ old('order_number', $purchase->order_number) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Tautan Pesanan</label>
                                <input type="url" name="order_url" class="form-control" maxlength="500" value="{{ old('order_url', $purchase->order_url) }}" placeholder="https://">
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label small">Ekspedisi</label>
                            <input type="text" name="courier" class="form-control" maxlength="50" value="{{ old('courier', $purchase->courier) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Nomor Resi</label>
                            <input type="text" name="tracking_number" class="form-control" maxlength="100" value="{{ old('tracking_number', $purchase->tracking_number) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Catatan</label>
                            <input type="text" name="note" class="form-control" maxlength="255" value="{{ old('note') }}">
                        </div>
                        <div class="{{ $purchase->isOnline() ? 'col-md-6' : 'col-12' }}">
                            <label class="form-label small">Bukti Pesanan</label>
                            <input type="file" name="order_proof" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        </div>
                        @if($purchase->isOnline())
                            <div class="col-md-6">
                                <label class="form-label small">Resi</label>
                                <input type="file" name="receipt_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                            </div>
                        @endif
                        <div class="col-12 form-text">Tangkapan layar pesanan atau chat vendor. JPG, PNG, WEBP, atau PDF, maksimal 5MB.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($purchase->canOrder() && $user->hasPermission('purchase', 'edit') && !$purchase->isOnline())
    @php
        $vendor = $purchase->vendor;
        $waNumber = $purchase->vendorWhatsapp();
    @endphp
    <div class="modal fade" id="contactVendorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Hubungi Vendor - {{ $purchase->vendor_name }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label for="vendorMessage" class="form-label small">Pesan (bisa diubah sebelum dikirim)</label>
                    <textarea id="vendorMessage" class="form-control" rows="9">{{ $purchase->vendorMessage() }}</textarea>

                    <div class="small text-muted mt-3">
                        @if($vendor && $vendor->phone)
                            <div><i class="bi bi-telephone me-1"></i><a href="tel:{{ $vendor->phone }}" class="text-decoration-none">{{ $vendor->phone }}</a>@if(!$waNumber) <span>(tidak memakai WhatsApp)</span>@endif</div>
                        @endif
                        @if($vendor && $vendor->email)
                            <div><i class="bi bi-envelope me-1"></i>{{ $vendor->email }}</div>
                        @endif
                        @if(!$waNumber && !($vendor && $vendor->email))
                            <div class="text-danger">
                                Vendor belum punya WhatsApp atau email.
                                @if($vendor && $user->hasPermission('kontak', 'view'))
                                    <a href="{{ route('contacts.show', $vendor) }}" class="text-decoration-none">Lengkapi kontak</a>
                                @endif
                                Anda tetap bisa menyalin pesan dan mengirimnya lewat jalur lain.
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-outline-secondary" id="copyVendorMessage"><i class="bi bi-clipboard"></i> <span>Salin Pesan</span></button>
                    @if($vendor && $vendor->email)
                        <button type="button" class="btn btn-outline-primary" id="sendVendorEmail" data-email="{{ $vendor->email }}" data-subject="Pesanan {{ $purchase->code }}"><i class="bi bi-envelope"></i> Kirim Email</button>
                    @endif
                    @if($waNumber)
                        <button type="button" class="btn btn-success" id="sendVendorWhatsapp" data-number="{{ $waNumber }}"><i class="bi bi-whatsapp"></i> Kirim WhatsApp</button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const box = document.getElementById('vendorMessage');
                const open = (url) => window.open(url, '_blank', 'noopener');

                document.getElementById('sendVendorWhatsapp')?.addEventListener('click', function () {
                    open('https://wa.me/' + this.dataset.number + '?text=' + encodeURIComponent(box.value));
                });

                document.getElementById('sendVendorEmail')?.addEventListener('click', function () {
                    open('mailto:' + this.dataset.email + '?subject=' + encodeURIComponent(this.dataset.subject) + '&body=' + encodeURIComponent(box.value));
                });

                document.getElementById('copyVendorMessage').addEventListener('click', async function () {
                    const label = this.querySelector('span');
                    try {
                        await navigator.clipboard.writeText(box.value);
                    } catch (e) {
                        box.select();
                        document.execCommand('copy');
                    }
                    label.textContent = 'Tersalin';
                    setTimeout(() => label.textContent = 'Salin Pesan', 1500);
                });
            });
        </script>
    @endpush
@endif

@if($purchase->canReturn() && $user->hasPermission('purchase', 'receive'))
    <div class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('purchases.return', $purchase) }}" method="POST">
                    @csrf
                    <input type="hidden" name="_modal" value="returnModal">
                    <div class="modal-header">
                        <h6 class="modal-title">Retur Barang - {{ $purchase->code }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="small text-muted">
                                        <th>Barang</th>
                                        <th class="text-end">Diretur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchase->items->filter(fn ($i) => $i->returnable_qty > 0) as $item)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $item->name }}</div>
                                                <div class="small text-muted">Dapat diretur {{ number_format($item->returnable_qty) }}@if($item->stock_mode !== \App\Models\PurchaseItem::STOCK_NONE), stok Inventory dikurangi @endif</div>
                                            </td>
                                            <td class="text-end">
                                                <input type="number" name="lines[{{ $item->id }}][qty]" class="form-control form-control-sm text-end"
                                                       min="0" max="{{ $item->returnable_qty }}" value="{{ old("lines.{$item->id}.qty", 0) }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <label class="form-label small">Alasan retur dan catatan refund <span class="text-danger">*</span></label>
                        <textarea name="note" class="form-control" rows="3" maxlength="500" required>{{ old('note') }}</textarea>
                        @if($purchase->payment_status === \App\Models\Purchase::PAYMENT_PAID)
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label small">Uang Kembali dari Vendor</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" inputmode="numeric" name="refund" class="form-control js-money" value="{{ old('refund') }}" placeholder="Opsional">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Tanggal Uang Kembali</label>
                                    <input type="date" name="refunded_on" class="form-control" value="{{ old('refunded_on', now()->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="form-text">Bila nominal diisi, tercatat otomatis sebagai pemasukan Pengembalian Pembelian di buku kas.</div>
                        @else
                            <div class="form-text">Pembelian belum dibayar, nominal bayar otomatis mengikuti barang yang tidak diretur.</div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Simpan Retur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($purchase->canFund() && $user->hasPermission('approval', 'decide'))
    <div class="modal fade" id="fundModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('purchases.fund', $purchase) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_modal" value="fundModal">
                    <div class="modal-header">
                        <h6 class="modal-title">Transfer Dana ke Finance - {{ $purchase->code }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Tanggal Transfer <span class="text-danger">*</span></label>
                            <input type="date" name="fund_at" class="form-control" value="{{ old('fund_at', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Nominal <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" inputmode="numeric" name="amount" class="form-control js-money" value="{{ old('amount', number_format($purchase->total, 0, ',', '.')) }}" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Bukti Transfer <span class="text-danger">*</span></label>
                            <input type="file" name="proof" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Catatan</label>
                            <input type="text" name="note" class="form-control" maxlength="255" value="{{ old('note') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($purchase->canCancel() && $user->hasPermission('purchase', 'edit'))
    @php
        $reasonRequired = in_array($purchase->status, [\App\Models\Purchase::STATUS_APPROVED, \App\Models\Purchase::STATUS_ORDERED], true);
    @endphp
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('purchases.cancel', $purchase) }}" method="POST">
                    @csrf
                    <input type="hidden" name="_modal" value="cancelModal">
                    <div class="modal-header">
                        <h6 class="modal-title">Batalkan Pembelian - {{ $purchase->code }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small">Alasan pembatalan @if($reasonRequired)<span class="text-danger">*</span>@endif</label>
                        <textarea name="reason" class="form-control" rows="3" maxlength="500" @required($reasonRequired)>{{ old('reason') }}</textarea>
                        @if($purchase->fund_status === \App\Models\Purchase::FUND_AT_FINANCE)
                            <div class="form-text text-danger">Dana {{ \App\Support\Money::formatRupiah($purchase->fund_amount) }} sudah di finance dan perlu dikembalikan.</div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-danger">Batalkan Pembelian</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if((int) session('purchase_approved_id') === $purchase->id)
    <div class="modal fade" id="nextStepModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-check-circle text-success me-2"></i>{{ $purchase->code }} disetujui</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="fw-semibold small mb-2">Langkah berikutnya</div>
                    @foreach($nextSteps as $step)
                        <div class="d-flex gap-2 small mb-2">
                            <i class="bi {{ $step['track'] === 'Uang' ? 'bi-cash-coin' : 'bi-box-seam' }} text-secondary"></i>
                            <div><span class="fw-semibold">{{ $step['owner'] }}</span>: {{ $step['text'] }}</div>
                        </div>
                    @endforeach
                    <div class="small text-muted mt-3">Notifikasi sudah dikirim ke pihak terkait.</div>
                </div>
                <div class="modal-footer">
                    @if($canOrderNow && !$purchase->isOnline())
                        <button type="button" class="btn btn-outline-success" id="nextStepContact"><i class="bi bi-chat-dots me-1"></i> Hubungi vendor</button>
                    @endif
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const popup = document.getElementById('nextStepModal');
                const contact = document.getElementById('contactVendorModal');
                const popupModal = new bootstrap.Modal(popup);
                popupModal.show();

                // Buka popup pesan vendor setelah popup ini tertutup sempurna.
                document.getElementById('nextStepContact')?.addEventListener('click', function () {
                    popup.addEventListener('hidden.bs.modal', function () {
                        if (contact) new bootstrap.Modal(contact).show();
                    }, { once: true });
                    popupModal.hide();
                });
            });
        </script>
    @endpush
@endif

@php
    $serialPending = session('purchase_serial_pending');
@endphp
@if(is_array($serialPending) && $serialPending)
    @php
        $canFillSerial = ($canOpenInventory ?? false) && $user->hasPermission('inventory', 'edit');
    @endphp
    <div class="modal fade" id="serialPendingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-upc-scan text-warning me-2"></i>Serial number perlu dilengkapi</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-3">
                        Barang berikut sudah diterima dan stok sudah masuk, tetapi serial number-nya belum diisi.
                        Lengkapi sesegera mungkin di detail Inventory, sebelum label QR dicetak.
                        @unless($canFillSerial)
                            Minta admin Inventory untuk melengkapinya.
                        @endunless
                    </p>
                    <div class="list-group">
                        @foreach($serialPending as $row)
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-2 small">
                                <span class="fw-semibold">{{ $row['name'] }}</span>
                                @if($canFillSerial)
                                    <a href="{{ route('inventory.show', $row['id']) }}" class="btn btn-sm btn-outline-primary">Isi serial</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('serialPendingModal')).show();
            });
        </script>
    @endpush
@endif

@push('scripts')
    <script>
        // Input rupiah di modal: tampil bertitik ribuan, server membersihkan titiknya.
        document.addEventListener('input', function (e) {
            if (!e.target.classList.contains('js-money')) return;
            const digits = e.target.value.replace(/\D/g, '');
            e.target.value = digits ? Number(digits).toLocaleString('id-ID') : '';
        });
    </script>
@endpush

@if($errors->any() && old('_modal'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.getElementById(@json(old('_modal')));
                if (el) new bootstrap.Modal(el).show();
            });
        </script>
    @endpush
@endif
