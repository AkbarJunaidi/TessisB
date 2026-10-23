@extends('layouts.app')

@section('title', 'Detail Pembelian')

@section('content')
@php
    $user = auth()->user();
    $canEdit = $user->hasPermission('purchase', 'edit');
    $latestApproval = $purchase->approvalRequests->first();
    $pendingApproval = $purchase->approvalRequests->firstWhere('status', 'pending');
    $canDecide = $purchase->status === \App\Models\Purchase::STATUS_SUBMITTED
        && $pendingApproval
        && $user->hasPermission('approval', 'decide');
    $canOpenInventory = $user->hasRole('super_admin', 'admin') && $user->hasPermission('inventory', 'view');

    $canSubmitNow    = $purchase->isEditable() && $canEdit;
    $canOrderNow     = $purchase->canOrder() && $canEdit;
    $canEditOrderNow = $purchase->canEditOrder() && $canEdit;
    $canFundNow      = $purchase->canFund() && $user->hasPermission('approval', 'decide');
    $canReceiveNow   = $purchase->canReceive() && $user->hasPermission('purchase', 'receive');
    $canPayNow       = $purchase->canPayBy($user);
    // Jalur bayar biasa; membayar langsung sebelum dana di finance masuk menu Lainnya.
    $payRegular      = !$purchase->isPayFirst() || $purchase->fund_status === \App\Models\Purchase::FUND_AT_FINANCE;
    $canReturnNow    = $purchase->canReturn() && $user->hasPermission('purchase', 'receive');
    $canDeleteNow    = $purchase->canDelete() && $user->hasPermission('purchase', 'delete');
    $canCancelNow    = $purchase->canCancel() && $canEdit && !($canDeleteNow && $purchase->status === \App\Models\Purchase::STATUS_DRAFT);
    $canPrintPo      = in_array($purchase->status, \App\Models\Purchase::COMMITTED, true) && $user->hasPermission('purchase', 'view');
    $payInMenu       = $canPayNow && !$payRegular;
    $hasMenu         = $canPrintPo || $canReturnNow || $canEditOrderNow || $canCancelNow || $canDeleteNow || $payInMenu;

    // Satu aksi utama (biru penuh) per halaman: yang pertama tersedia menurut urutan ini.
    $primaryKey = collect([
        'approve' => $canDecide, 'submit' => $canSubmitNow, 'order' => $canOrderNow,
        'fund' => $canFundNow, 'receive' => $canReceiveNow, 'pay' => $canPayNow && $payRegular,
    ])->filter()->keys()->first();
    $btn = fn (string $key) => $primaryKey === $key ? 'btn-primary' : 'btn-outline-primary';
    $isCommitted = in_array($purchase->status, \App\Models\Purchase::COMMITTED, true);
    // Kolom Diterima hanya berarti setelah pesanan dibuat atau ada barang yang masuk.
    $showReceived = in_array($purchase->status, [\App\Models\Purchase::STATUS_ORDERED, \App\Models\Purchase::STATUS_PARTIAL, \App\Models\Purchase::STATUS_RECEIVED], true)
        || $purchase->items->contains(fn ($i) => $i->received_qty > 0);
@endphp

<div class="container-fluid p-0">

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-3" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <a data-back href="{{ route('purchases.index') }}" class="page-back"><i class="bi bi-arrow-left"></i> Pembelian</a>
            <h3 class="fw-bold mb-1">{{ $purchase->code }}</h3>
            <span class="badge {{ \App\Models\Purchase::STATUS_BADGES[$purchase->status] ?? 'bg-secondary' }}">{{ $purchase->status }}</span>
            @if($isCommitted)
                <span class="badge {{ $purchase->money_badge }}">{{ $purchase->money_stage }}</span>
            @endif
            @php
                $due = $purchase->dueState();
            @endphp
            @if($due && $due['soon'])
                <span class="badge {{ $due['overdue'] ? 'bg-danger' : 'bg-warning text-dark' }}">
                    {{ $due['overdue'] ? 'Terlambat ' . abs($due['days']) . ' hari' : ($due['days'] === 0 ? 'Jatuh tempo hari ini' : 'Jatuh tempo ' . $due['days'] . ' hari lagi') }}
                </span>
            @endif
            @if($purchase->resubmit_count > 0)
                <span class="badge bg-light text-dark border">Diajukan ulang ke-{{ $purchase->resubmit_count + 1 }}</span>
            @endif
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if($hasMenu)
                <div class="dropdown">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 fw-medium" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-three-dots"></i> Lainnya
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        @if($canPrintPo)
                            <li><a class="dropdown-item" href="{{ route('purchases.po', $purchase) }}" target="_blank" rel="noopener"><i class="bi bi-printer me-2"></i>Cetak PO</a></li>
                        @endif
                        @if($canEditOrderNow)
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#orderModal"><i class="bi bi-pencil-square me-2"></i>Ubah Info Pesanan</button></li>
                        @endif
                        @if($payInMenu)
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#payModal"><i class="bi bi-cash-coin me-2"></i>Bayar Langsung ke Vendor</button></li>
                        @endif
                        @if($canReturnNow)
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#returnModal"><i class="bi bi-box-arrow-up me-2"></i>Retur Barang</button></li>
                        @endif
                        @if(($canCancelNow || $canDeleteNow) && ($canPrintPo || $canEditOrderNow || $payInMenu || $canReturnNow))
                            <li><hr class="dropdown-divider"></li>
                        @endif
                        @if($canCancelNow)
                            <li><button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="bi bi-x-circle me-2"></i>Batalkan</button></li>
                        @endif
                        @if($canDeleteNow)
                            <li>
                                <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" data-confirm="Hapus draft pembelian ini?" data-confirm-label="Hapus" data-confirm-danger>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Hapus</button>
                                </form>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif

            @if($canDecide)
                <button type="button" class="btn btn-outline-danger d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-circle"></i> Tolak
                </button>
                <button type="button" class="btn {{ $btn('approve') }} d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#approveModal">
                    <i class="bi bi-check2-circle"></i> Setujui
                </button>
            @endif

            @if($canSubmitNow)
                <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-outline-primary d-flex align-items-center gap-2 fw-medium">
                    <i class="bi bi-pencil-square"></i> Ubah
                </a>
                <form action="{{ route('purchases.submit', $purchase) }}" method="POST" data-confirm="Ajukan pembelian ini untuk approval?" data-confirm-label="Ajukan">
                    @csrf
                    <button type="submit" class="btn {{ $btn('submit') }} d-flex align-items-center gap-2 fw-medium"><i class="bi bi-send"></i> Ajukan Approval</button>
                </form>
            @endif

            @if($canOrderNow)
                @if(!$purchase->isOnline())
                    <button type="button" class="btn btn-outline-success d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#contactVendorModal">
                        <i class="bi bi-chat-dots"></i> Hubungi Vendor
                    </button>
                @endif
                <button type="button" class="btn {{ $btn('order') }} d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#orderModal">
                    <i class="bi bi-bag-check"></i> Tandai Dipesan
                </button>
            @endif

            @if($canFundNow)
                <button type="button" class="btn {{ $btn('fund') }} d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#fundModal">
                    <i class="bi bi-bank"></i> Transfer Dana ke Finance
                </button>
            @endif

            @if($canReceiveNow)
                <button type="button" class="btn {{ $btn('receive') }} d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#receiveModal">
                    <i class="bi bi-box-arrow-in-down"></i> {{ $purchase->status === \App\Models\Purchase::STATUS_PARTIAL ? 'Terima Sisa Barang' : 'Barang Diterima' }}
                </button>
            @endif

            @if($canPayNow && $payRegular)
                <button type="button" class="btn {{ $btn('pay') }} d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#payModal">
                    <i class="bi bi-cash-coin"></i> Catat Pembayaran
                </button>
            @endif
        </div>
    </div>

    @if($purchase->status === \App\Models\Purchase::STATUS_REJECTED && $latestApproval)
        <div class="alert alert-danger border-0 shadow-sm">
            <div class="fw-semibold">Pembelian ditolak oleh {{ $latestApproval->decidedBy->name ?? '-' }}</div>
            <div class="small">{{ $latestApproval->decision_note ?: 'Tanpa catatan.' }} Ubah lalu ajukan kembali.</div>
        </div>
    @endif

    @include('purchase.partials.next-step')

    <div class="row g-3">

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Informasi</h6>

                    <div class="mb-2">
                        <div class="text-muted small">Vendor</div>
                        @if($purchase->vendor && $user->hasPermission('kontak', 'view'))
                            <a href="{{ route('contacts.show', $purchase->vendor) }}" class="text-decoration-none fw-semibold">{{ $purchase->vendor_name }}</a>
                        @else
                            <div class="fw-semibold">{{ $purchase->vendor_name }}</div>
                        @endif
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Project</div>
                        @if($purchase->project)
                            <a href="{{ route('projects.show', $purchase->project) }}" class="text-decoration-none" title="{{ $purchase->project->name }}">{{ $purchase->project->short_name }}</a>
                        @else
                            <div>Stok umum</div>
                        @endif
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Tanggal Pembelian</div>
                        <div>{{ $purchase->purchase_date->translatedFormat('d M Y') }}</div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Cara Belanja</div>
                        <div>{{ \App\Models\Purchase::SHOP_TYPES[$purchase->shopping_type] ?? '-' }}</div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Waktu Bayar</div>
                        <div>
                            {{ \App\Models\Purchase::PAYMENT_TIMINGS[$purchase->payment_timing] ?? 'Belum diatur' }}
                            @if($purchase->due_date) - jatuh tempo {{ $purchase->due_date->translatedFormat('d M Y') }} @endif
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Dibuat Oleh</div>
                        <div>{{ $purchase->creator->name ?? '-' }}</div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Catatan</div>
                        <div class="{{ $purchase->notes ? '' : 'text-muted' }}">{{ $purchase->notes ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-muted small">Lampiran Pengajuan</div>
                        @if($purchase->attachment)
                            <a href="{{ route('purchases.attachment', $purchase) }}" target="_blank" rel="noopener" class="text-decoration-none">
                                <i class="bi bi-paperclip"></i> Lihat lampiran
                            </a>
                        @else
                            <div class="text-muted">-</div>
                        @endif
                    </div>
                </div>
            </div>

            @if($purchase->vendor)
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Hubungi Vendor</h6>

                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-telephone text-secondary"></i>
                            @if($purchase->vendor->phone)
                                <a href="tel:{{ $purchase->vendor->phone }}" class="text-decoration-none">{{ $purchase->vendor->phone }}</a>
                            @else
                                <span class="text-muted">Belum diisi</span>
                            @endif
                        </div>

                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-whatsapp text-secondary"></i>
                            @if($purchase->vendorWhatsapp())
                                <a href="https://wa.me/{{ $purchase->vendorWhatsapp() }}" target="_blank" rel="noopener" class="text-decoration-none">
                                    Chat WhatsApp
                                </a>
                            @else
                                <span class="text-muted">Tidak tersedia</span>
                            @endif
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-envelope text-secondary"></i>
                            @if($purchase->vendor->email)
                                <a href="mailto:{{ $purchase->vendor->email }}" class="text-decoration-none">{{ $purchase->vendor->email }}</a>
                            @else
                                <span class="text-muted">Belum diisi</span>
                            @endif
                        </div>

                        @if($canOrderNow && !$purchase->isOnline())
                            <button type="button" class="btn btn-sm btn-outline-success mt-3" data-bs-toggle="modal" data-bs-target="#contactVendorModal">
                                <i class="bi bi-chat-dots me-1"></i> Kirim Pesan Pesanan
                            </button>
                        @endif
                    </div>
                </div>
            @endif

        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>Barang</th>
                                <th class="text-end">Jumlah</th>
                                <th class="text-end text-nowrap">Harga Satuan</th>
                                <th class="text-end">Subtotal</th>
                                @if($showReceived)
                                    <th class="text-end">Diterima</th>
                                @endif
                                <th>Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $item)
                                <tr class="border-top">
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td class="text-end">{{ number_format($item->qty) }}</td>
                                    <td class="text-end text-nowrap">
                                        {{ \App\Support\Money::formatRupiah($item->unit_price) }}
                                        @if($item->actual_unit_price !== null)
                                            @php
                                                $delta = $item->effective_price - (float) $item->unit_price;
                                            @endphp
                                            <div class="small {{ $delta > 0 ? 'text-danger' : 'text-success' }}">
                                                Aktual {{ \App\Support\Money::formatRupiah($item->actual_unit_price) }} ({{ $delta > 0 ? '+' : '-' }}{{ \App\Support\Money::formatRupiah(abs($delta)) }})
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Money::formatRupiah($item->subtotal) }}</td>
                                    @if($showReceived)
                                        <td class="text-end small text-nowrap">
                                            {{ number_format($item->received_qty) }}/{{ number_format($item->qty) }}
                                            @if($item->returned_qty > 0)
                                                <div class="text-muted">retur {{ number_format($item->returned_qty) }}</div>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="small">
                                        @if($item->inventory)
                                            @if($canOpenInventory)
                                                <a href="{{ route('inventory.show', $item->inventory) }}" class="text-decoration-none">{{ $item->inventory->name }}</a>
                                            @else
                                                {{ $item->inventory->name }}
                                            @endif
                                            <div class="text-muted">{{ $item->stock_mode === \App\Models\PurchaseItem::STOCK_NEW ? 'Barang baru' : 'Tambah stok' }}</div>
                                            @if($item->inventory->serial_pending)
                                                <span class="badge bg-warning text-dark">Serial belum diisi</span>
                                            @endif
                                        @elseif($item->stock_mode === \App\Models\PurchaseItem::STOCK_NONE)
                                            <span class="text-muted">Bukan stok</span>
                                        @else
                                            <span class="text-muted">{{ $item->stock_mode === \App\Models\PurchaseItem::STOCK_NEW ? 'Barang baru' : 'Tambah stok' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-top">
                                <td colspan="3" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold text-nowrap">{{ \App\Support\Money::formatRupiah($purchase->total) }}</td>
                                <td colspan="{{ $showReceived ? 2 : 1 }}"></td>
                            </tr>
                            @if(abs($purchase->priceVariance()) >= 0.005)
                                <tr>
                                    <td colspan="3" class="text-end small text-muted">Selisih harga aktual dari PO</td>
                                    <td class="text-end small text-nowrap {{ $purchase->priceVariance() > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $purchase->priceVariance() > 0 ? '+' : '-' }}{{ \App\Support\Money::formatRupiah(abs($purchase->priceVariance())) }}
                                    </td>
                                    <td colspan="{{ $showReceived ? 2 : 1 }}"></td>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>

            @include('purchase.partials.order-fund-card')
            @include('purchase.partials.attachments-card')
            @include('purchase.partials.history-card')
        </div>

    </div>

    @include('purchase.partials.stage-modals')

    @if($purchase->canReceive() && $user->hasPermission('purchase', 'receive'))
        @php
            $receivable = $purchase->items->filter(fn ($i) => $i->remaining_qty > 0);
            $needsSerialAny = $receivable->contains(fn ($i) => $i->stock_mode === \App\Models\PurchaseItem::STOCK_NEW && !$i->inventory_id);
        @endphp
        <div class="modal fade" id="receiveModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <form action="{{ route('purchases.receive', $purchase) }}" method="POST">
                        @csrf
                        <input type="hidden" name="_modal" value="receiveModal">
                        <div class="modal-header">
                            <h6 class="modal-title">Barang Diterima - {{ $purchase->code }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted mb-3">Isi jumlah yang tiba, 0 bila belum datang. Harga aktual cukup diisi bila berbeda dari PO.</p>

                            @if($needsSerialAny)
                                <div class="alert alert-light border small py-2 mb-3">
                                    <i class="bi bi-info-circle me-1"></i>Barang baru tetap bisa diterima tanpa serial number, tetapi serial harus segera dilengkapi di detail Inventory.
                                </div>
                            @endif

                            @foreach($receivable as $item)
                                @php
                                    $needsSerial = $item->stock_mode === \App\Models\PurchaseItem::STOCK_NEW && !$item->inventory_id;
                                @endphp
                                <div class="{{ $loop->last ? '' : 'pb-3 mb-3 border-bottom' }}">
                                    <div class="fw-semibold">{{ $item->name }}</div>
                                    <div class="small text-muted mb-2">
                                        Dipesan {{ number_format($item->qty) }}, sudah masuk {{ number_format($item->received_qty) }}, harga PO {{ \App\Support\Money::formatRupiah($item->unit_price) }}
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-5">
                                            <label class="form-label small mb-1">Diterima</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="lines[{{ $item->id }}][qty]" class="form-control text-end"
                                                       min="0" max="{{ $item->remaining_qty }}" value="{{ old("lines.{$item->id}.qty", $item->remaining_qty) }}">
                                                <span class="input-group-text">/ {{ number_format($item->remaining_qty) }}</span>
                                            </div>
                                        </div>
                                        <div class="col-7">
                                            <label class="form-label small mb-1">Harga aktual per unit</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" inputmode="numeric" name="lines[{{ $item->id }}][price]" max="{{ \App\Support\Limits::money() }}" class="form-control text-end js-money"
                                                       placeholder="{{ number_format($item->effective_price, 0, ',', '.') }}" value="{{ old("lines.{$item->id}.price") }}">
                                            </div>
                                        </div>
                                        @if($needsSerial)
                                            <div class="col-12">
                                                <label class="form-label small mb-1">Serial number <span class="text-muted">(opsional, bisa diisi nanti)</span></label>
                                                <input type="text" name="lines[{{ $item->id }}][serial]" class="form-control form-control-sm" maxlength="{{ \App\Support\TextLimits::SERIAL }}" value="{{ old("lines.{$item->id}.serial") }}">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Konfirmasi Diterima</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($purchase->canPayBy($user))
        <div class="modal fade" id="payModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('purchases.pay', $purchase) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h6 class="modal-title">Catat Pembayaran - {{ $purchase->code }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body row g-2">
                            <div class="col-12">
                                <label class="form-label small">Nominal Dibayar <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" inputmode="numeric" name="amount" max="{{ \App\Support\Limits::money() }}" class="form-control js-money" value="{{ old('amount', number_format($purchase->payableTotal(), 0, ',', '.')) }}" required>
                                </div>
                                <div class="form-text">
                                    Nominal PO {{ \App\Support\Money::formatRupiah($purchase->total) }}@if(abs($purchase->payableTotal() - (float) $purchase->total) >= 0.005), sesuai penerimaan {{ \App\Support\Money::formatRupiah($purchase->payableTotal()) }}@endif.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Tanggal Bayar</label>
                                <input type="date" name="paid_at" class="form-control" value="{{ old('paid_at', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Metode</label>
                                <select name="payment_method" class="form-select" required>
                                    @foreach(\App\Models\Purchase::PAYMENT_METHODS as $method)
                                        <option value="{{ $method }}" @selected(old('payment_method', 'Transfer') === $method)>{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Bukti Pembayaran</label>
                                <input type="file" name="proof" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                <div class="form-text">Wajib untuk metode Transfer. JPG, PNG, WEBP, atau PDF, maksimal 5MB.</div>
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

    @if($canDecide)
        <div class="modal fade" id="approveModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('approval.approve', $pendingApproval) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h6 class="modal-title">Setujui Pembelian {{ $purchase->code }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label small">Catatan (opsional)</label>
                            <textarea name="note" class="form-control" rows="2" maxlength="255"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Setujui</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="rejectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('approval.reject', $pendingApproval) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h6 class="modal-title">Tolak Pembelian {{ $purchase->code }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label small">Catatan <span class="text-danger">*</span></label>
                            <textarea name="note" class="form-control" rows="2" maxlength="255" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Tolak</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any() && old('paid_at') !== null)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const el = document.getElementById('payModal');
                    if (el) new bootstrap.Modal(el).show();
                });
            </script>
        @endpush
    @endif

</div>
@endsection
