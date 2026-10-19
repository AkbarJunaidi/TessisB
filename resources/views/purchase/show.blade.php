@extends('layouts.app')

@section('title', 'Detail Pembelian')

@section('content')
@php
    $user = auth()->user();
    $canEdit = $user->hasPermission('purchase', 'edit');
    $newItems = $purchase->items->where('stock_mode', \App\Models\PurchaseItem::STOCK_NEW);
    $latestApproval = $purchase->approvalRequests->first();
    $pendingApproval = $purchase->approvalRequests->firstWhere('status', 'pending');
    $canDecide = $purchase->status === \App\Models\Purchase::STATUS_SUBMITTED
        && $pendingApproval
        && $user->hasPermission('approval', 'decide');
    $canOpenInventory = $user->hasRole('super_admin', 'admin') && $user->hasPermission('inventory', 'view');
    $canDelete = $purchase->canDelete() && $user->hasPermission('purchase', 'delete');
    $canCancel = $purchase->canCancel() && $canEdit;
    $canSubmit = $purchase->isEditable() && $canEdit;
    $canOrderNow = $purchase->canOrder() && $canEdit;
    $canEditOrderNow = $purchase->canEditOrder() && $canEdit;
    $canReceiveNow = $purchase->canReceive() && $user->hasPermission('purchase', 'receive');
    $canPayNow = $purchase->canPay() && $user->hasPermission('purchase', 'pay');
    $canFundNow = $purchase->canRecordFund() && $user->hasPermission('approval', 'decide');
    $canAttach = $user->hasPermission('purchase', 'edit') || $user->hasPermission('purchase', 'pay');
    $canReturnNow = $purchase->canReturn() && $user->hasPermission('purchase', 'receive');
    $stages = \App\Support\PurchaseNextStep::stages($purchase);
    $hasMore = $purchase->canPrintPo() || $canEditOrderNow || $canFundNow || $canReturnNow || $canCancel;
    // Satu aksi utama (biru penuh) per halaman: yang pertama tersedia menurut urutan prioritas ini.
    $primaryKey = collect(['approve' => $canDecide, 'submit' => $canSubmit, 'order' => $canOrderNow, 'receive' => $canReceiveNow, 'pay' => $canPayNow])->filter()->keys()->first();
    $btn = fn (string $key) => $primaryKey === $key ? 'btn-primary' : 'btn-outline-primary';
    $nextStep = \App\Support\PurchaseNextStep::for($purchase, $user, $canDecide);
    $approvedNow = (int) session('purchase_approved_id') === $purchase->id;
    $waPhone = $purchase->vendor && $purchase->vendor->phone && $purchase->vendor->has_whatsapp
        ? preg_replace('/^0/', '62', preg_replace('/\D/', '', $purchase->vendor->phone))
        : null;
    $waUrl = $waPhone ? 'https://wa.me/' . $waPhone . '?text=' . rawurlencode($purchase->whatsappMessage()) : null;
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
            <span class="badge {{ $purchase->payment_status === \App\Models\Purchase::PAYMENT_PAID ? 'bg-success' : 'bg-warning text-dark' }}">{{ $purchase->payment_status }}</span>
            @if($purchase->fund_status && $purchase->payment_status !== \App\Models\Purchase::PAYMENT_PAID)
                <span class="badge {{ $purchase->fund_status === \App\Models\Purchase::FUND_AT_FINANCE ? 'bg-info text-dark' : 'bg-warning text-dark' }}">{{ \App\Models\Purchase::FUND_LABELS[$purchase->fund_status] }}</span>
            @endif
            @if($purchase->submit_count > 1)
                <span class="badge bg-light text-dark border">Diajukan ulang ke-{{ $purchase->submit_count }}</span>
            @endif
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if($canDelete)
                <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" data-confirm="Hapus draft pembelian ini?" data-confirm-label="Hapus" data-confirm-danger>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger d-flex align-items-center gap-2 fw-medium"><i class="bi bi-trash"></i> Hapus</button>
                </form>
            @endif

            @if($canDecide)
                <button type="button" class="btn btn-outline-danger d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-circle"></i> Tolak
                </button>
            @endif

            {{-- Aksi yang jarang dipakai dikumpulkan di sini supaya tombol utama tetap sedikit. --}}
            @if($hasMore)
                <div class="dropdown">
                    <button type="button" class="btn btn-outline-primary dropdown-toggle d-flex align-items-center gap-2 fw-medium" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-three-dots"></i> Lainnya
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        @if($purchase->canPrintPo())
                            <li><a class="dropdown-item" href="{{ route('purchases.pdf', $purchase) }}" target="_blank" rel="noopener"><i class="bi bi-printer me-2"></i> Cetak PO</a></li>
                        @endif
                        @if($canEditOrderNow)
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#orderModal"><i class="bi bi-truck me-2"></i> Ubah Info Pesanan</button></li>
                        @endif
                        @if($canFundNow)
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#fundModal"><i class="bi bi-bank me-2"></i> Catat Transfer Dana</button></li>
                        @endif
                        @if($canReturnNow)
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#returnModal"><i class="bi bi-arrow-return-left me-2"></i> Retur Barang</button></li>
                        @endif
                        @if($canCancel)
                            @if($purchase->canPrintPo() || $canEditOrderNow || $canFundNow || $canReturnNow)
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            <li><button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="bi bi-x-circle me-2"></i> Batalkan Pembelian</button></li>
                        @endif
                    </ul>
                </div>
            @endif

            @if($canSubmit)
                <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-outline-primary d-flex align-items-center gap-2 fw-medium">
                    <i class="bi bi-pencil-square"></i> Ubah
                </a>
            @endif

            @if($canPayNow)
                <button type="button" class="btn {{ $btn('pay') }} d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#payModal">
                    <i class="bi bi-cash-coin"></i> Catat Pembayaran
                </button>
            @endif

            @if($canReceiveNow)
                <button type="button" class="btn {{ $btn('receive') }} d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#receiveModal">
                    <i class="bi bi-box-arrow-in-down"></i> {{ $purchase->status === \App\Models\Purchase::STATUS_PARTIAL ? 'Terima Sisa Barang' : 'Barang Diterima' }}
                </button>
            @endif

            @if($canOrderNow)
                <button type="button" class="btn {{ $btn('order') }} d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#orderModal">
                    <i class="bi bi-bag-check"></i> Tandai Dipesan
                </button>
            @endif

            @if($canSubmit)
                <form action="{{ route('purchases.submit', $purchase) }}" method="POST" data-confirm="Ajukan pembelian ini untuk approval?" data-confirm-label="Ajukan">
                    @csrf
                    <button type="submit" class="btn {{ $btn('submit') }} d-flex align-items-center gap-2 fw-medium"><i class="bi bi-send"></i> Ajukan Approval</button>
                </form>
            @endif

            @if($canDecide)
                <button type="button" class="btn {{ $btn('approve') }} d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#approveModal">
                    <i class="bi bi-check2-circle"></i> Setujui
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

    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body py-3">
            <div class="fw-bold mb-3"><i class="bi bi-signpost-split me-1"></i> Tahapan pembelian</div>

            @if($stages['cancelled'])
                <div class="small text-muted mb-1"><i class="bi bi-x-circle-fill text-danger me-1"></i> Pembelian ini dibatalkan.</div>
            @else
                <div class="mb-3">
                    <div class="small text-muted mb-1"><i class="bi bi-box-seam me-1"></i> Barang</div>
                    @include('purchase.partials.stages', ['steps' => $stages['goods']])
                </div>
                <div class="mb-1">
                    <div class="small text-muted mb-1"><i class="bi bi-cash-coin me-1"></i> Uang</div>
                    @include('purchase.partials.stages', ['steps' => $stages['money']])
                </div>
            @endif

            @if($nextStep['goods'] || $nextStep['money'])
                <div class="border-top pt-3 mt-3 small">
                    <div class="fw-semibold mb-2">Langkah berikutnya</div>
                    @if($nextStep['goods'])
                        <div class="d-flex gap-2 mb-1">
                            <i class="bi bi-box-seam text-muted"></i>
                            <div><span class="text-muted">Barang:</span> {{ $nextStep['goods'] }}</div>
                        </div>
                    @endif
                    @if($nextStep['money'])
                        <div class="d-flex gap-2">
                            <i class="bi bi-cash-coin text-muted"></i>
                            <div><span class="text-muted">Uang:</span> {{ $nextStep['money'] }}</div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

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
                        <div>{{ \App\Models\Purchase::SHOP_CHANNELS[$purchase->shopping_channel] ?? '-' }}</div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Waktu Bayar</div>
                        <div>
                            {{ \App\Models\Purchase::PAYMENT_TIMINGS[$purchase->payment_timing] ?? '-' }}
                            @if($purchase->payment_timing === \App\Models\Purchase::TIMING_TERM && $purchase->due_date)
                                (jatuh tempo {{ $purchase->due_date->translatedFormat('d M Y') }})
                            @endif
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
                        <div class="text-muted small">Lampiran</div>
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

            @if($purchase->vendor && !$purchase->isOnline())
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
                            @if($waUrl)
                                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="text-decoration-none">
                                    Chat WhatsApp (pesan sudah terisi)
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
                    </div>
                </div>
            @endif

            @if($purchase->ordered_at || ($purchase->isOnline() && $purchase->canOrder()))
                @php
                    $orderRows = array_filter([
                        'Dipesan'       => $purchase->ordered_at ? $purchase->ordered_at->translatedFormat('d M Y H:i') . ' - ' . ($purchase->orderer->name ?? '-') : null,
                        'Toko'          => $purchase->shop_name,
                        'Nomor pesanan' => $purchase->order_number,
                        'Ekspedisi'     => $purchase->courier,
                        'Resi'          => $purchase->tracking_number,
                        'Estimasi tiba' => $purchase->expected_arrival?->translatedFormat('d M Y'),
                        'Catatan'       => $purchase->order_notes,
                    ]);
                @endphp
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Info Pesanan</h6>
                        @forelse($orderRows as $label => $value)
                            <div class="mb-2">
                                <div class="text-muted small">{{ $label }}</div>
                                <div class="text-break">{{ $value }}</div>
                            </div>
                        @empty
                            <div class="text-muted small">Belum dipesan. Buat pesanan di toko online lalu tandai Dipesan.</div>
                        @endforelse
                        @if($purchase->order_url)
                            <a href="{{ $purchase->order_url }}" target="_blank" rel="noopener noreferrer" class="small text-decoration-none">
                                <i class="bi bi-box-arrow-up-right"></i> Buka halaman pesanan
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if($purchase->fund_status)
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Dana</h6>
                        <div class="mb-2">
                            <div class="text-muted small">Status</div>
                            <div>{{ \App\Models\Purchase::FUND_LABELS[$purchase->fund_status] ?? '-' }}</div>
                        </div>
                        <div class="mb-2">
                            <div class="text-muted small">Nominal</div>
                            <div>{{ \App\Support\Money::formatRupiah($purchase->fund_amount ?? $purchase->total) }}</div>
                        </div>
                        @if($purchase->fund_requested_at)
                            <div class="mb-2">
                                <div class="text-muted small">Diminta</div>
                                <div>{{ $purchase->fund_requested_at->translatedFormat('d M Y H:i') }}</div>
                            </div>
                        @endif
                        @if($purchase->fund_transferred_on)
                            <div class="mb-2">
                                <div class="text-muted small">Ditransfer ke finance</div>
                                <div>{{ $purchase->fund_transferred_on->translatedFormat('d M Y') }} - {{ $purchase->fundTransferrer->name ?? '-' }}</div>
                            </div>
                        @endif
                        @if($purchase->fund_note)
                            <div>
                                <div class="text-muted small">Catatan</div>
                                <div class="text-break">{{ $purchase->fund_note }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Riwayat Status</h6>

                    @if($purchase->statusLogs->isNotEmpty())
                        @foreach($purchase->statusLogs as $log)
                            <div class="small {{ $loop->first ? '' : 'border-top pt-2' }} mb-2">
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="fw-semibold">{{ $log->action }}</span>
                                    <span class="text-muted text-nowrap">{{ $log->created_at->translatedFormat('d M Y H:i') }}</span>
                                </div>
                                <div class="text-muted">{{ $log->user->name ?? 'Sistem' }}</div>
                                @if($log->note)
                                    <div class="text-break">{{ $log->note }}</div>
                                @endif
                            </div>
                        @endforeach
                    @else
                    <div class="small mb-2">
                        <span class="text-muted">Dibuat</span>
                        <div>{{ $purchase->created_at->translatedFormat('d M Y H:i') }}</div>
                    </div>
                    @if($purchase->submitted_at)
                        <div class="small mb-2">
                            <span class="text-muted">Diajukan</span>
                            <div>{{ $purchase->submitted_at->translatedFormat('d M Y H:i') }}</div>
                        </div>
                    @endif
                    @if($purchase->approved_at)
                        <div class="small mb-2">
                            <span class="text-muted">Disetujui</span>
                            <div>{{ $purchase->approved_at->translatedFormat('d M Y H:i') }}</div>
                        </div>
                    @endif
                    @if($purchase->received_at)
                        <div class="small mb-2">
                            <span class="text-muted">Diterima</span>
                            <div>{{ $purchase->received_at->translatedFormat('d M Y H:i') }} - {{ $purchase->receiver->name ?? '-' }}</div>
                        </div>
                    @endif
                    @if($purchase->paid_at)
                        <div class="small">
                            <span class="text-muted">Dibayar</span>
                            <div>
                                {{ $purchase->paid_at->translatedFormat('d M Y') }}
                                @if($purchase->payment_method) ({{ $purchase->payment_method }}) @endif
                                - {{ $purchase->payer->name ?? '-' }}
                            </div>
                        </div>
                    @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>Barang</th>
                                <th class="text-end">Jumlah</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Subtotal</th>
                                <th>Barang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $item)
                                <tr class="border-top">
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td class="text-end">
                                        {{ number_format($item->qty) }}
                                        @if($item->qty_received > 0 && $item->qty_received < $item->qty)
                                            <div class="small text-success">Diterima {{ number_format($item->qty_received) }}</div>
                                        @endif
                                        @if($item->qty_returned > 0)
                                            <div class="small text-danger">Diretur {{ number_format($item->qty_returned) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ \App\Support\Money::formatRupiah($item->unit_price) }}
                                        @if($item->po_unit_price)
                                            <div class="small text-muted text-decoration-line-through">PO: {{ \App\Support\Money::formatRupiah($item->po_unit_price) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ \App\Support\Money::formatRupiah($item->netValue()) }}
                                        @if($item->qty_returned > 0)
                                            <div class="small text-muted">setelah retur</div>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if($item->inventory)
                                            @if($canOpenInventory)
                                                <a href="{{ route('inventory.show', $item->inventory) }}" class="text-decoration-none">{{ $item->inventory->name }}</a>
                                            @else
                                                {{ $item->inventory->name }}
                                            @endif
                                            <div class="text-muted">{{ $item->stock_mode === \App\Models\PurchaseItem::STOCK_NEW ? 'Barang baru' : 'Tambah stok' }}</div>
                                        @elseif($item->stock_mode === \App\Models\PurchaseItem::STOCK_NONE)
                                            <span class="text-muted">Bukan stok</span>
                                        @else
                                            <span class="text-muted">{{ \App\Models\PurchaseItem::STOCK_MODES[$item->stock_mode] ?? '-' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-top">
                                <td colspan="3" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold">{{ \App\Support\Money::formatRupiah($purchase->total) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 mt-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Lampiran per Tahap</h6>

                    @forelse($purchase->attachments as $file)
                        <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-top">
                            <div class="min-w-0">
                                <a href="{{ route('purchases.files.show', [$purchase, $file]) }}" target="_blank" rel="noopener" class="text-decoration-none text-break">
                                    <i class="bi bi-paperclip"></i> {{ $file->original_name }}
                                </a>
                                <div class="text-muted small">{{ $file->stageLabel() }} - {{ $file->uploader->name ?? '-' }} - {{ $file->created_at->translatedFormat('d M Y H:i') }}</div>
                            </div>
                            @if($canAttach && ($file->uploaded_by === $user->id || $user->isSuperAdmin()))
                                <form action="{{ route('purchases.files.destroy', [$purchase, $file]) }}" method="POST" data-confirm="Hapus lampiran ini?" data-confirm-label="Hapus" data-confirm-danger>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus lampiran" aria-label="Hapus lampiran {{ $file->original_name }}"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="text-muted small mb-2">Belum ada lampiran. Bukti pesanan, transfer dana, dan pembayaran ikut tersimpan di sini.</div>
                    @endforelse

                    @if($canAttach && $purchase->status !== \App\Models\Purchase::STATUS_CANCELLED)
                        <form action="{{ route('purchases.files.store', $purchase) }}" method="POST" enctype="multipart/form-data" class="row g-2 border-top pt-3 mt-2">
                            @csrf
                            <div class="col-md-4">
                                <select name="stage" class="form-select form-select-sm" aria-label="Jenis lampiran" required>
                                    @foreach(\App\Models\PurchaseAttachment::MANUAL_STAGES as $stage)
                                        <option value="{{ $stage }}">{{ \App\Models\PurchaseAttachment::STAGES[$stage] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <input type="file" name="file" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,.pdf" aria-label="File lampiran" required>
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-upload"></i> Unggah</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

    </div>

    @if($canReceiveNow)
        @php $unpaidNow = $purchase->payment_status === \App\Models\Purchase::PAYMENT_UNPAID; @endphp
        <div class="modal fade" id="receiveModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form action="{{ route('purchases.receive', $purchase) }}" method="POST">
                        @csrf
                        <input type="hidden" name="_modal" value="receive">
                        <div class="modal-header">
                            <h6 class="modal-title">{{ $purchase->status === \App\Models\Purchase::STATUS_PARTIAL ? 'Terima Sisa Barang' : 'Barang Diterima' }} - {{ $purchase->code }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted mb-3">
                                Isi jumlah yang tiba sekarang. Barang boleh datang bertahap, yang belum tiba bisa diterima nanti.
                                Stok Inventory bertambah sesuai jumlah yang diterima.
                            </p>

                            @error('qty')
                                <div class="alert alert-danger py-2 small">{{ $message }}</div>
                            @enderror

                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-2">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th>Barang</th>
                                            <th class="text-end">Dipesan</th>
                                            <th class="text-end">Sudah masuk</th>
                                            <th class="u-w-130px">Diterima sekarang</th>
                                            @if($unpaidNow)
                                                <th class="u-w-160px">Harga satuan</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($purchase->items as $item)
                                            <tr>
                                                <td>
                                                    {{ $item->name }}
                                                    @if($item->stock_mode === \App\Models\PurchaseItem::STOCK_NEW && !$item->inventory_id)
                                                        <input type="text" name="serials[{{ $item->id }}]" maxlength="255"
                                                               class="form-control form-control-sm mt-1 @error("serials.{$item->id}") is-invalid @enderror"
                                                               placeholder="Serial number (wajib saat pertama masuk)" value="{{ old("serials.{$item->id}") }}">
                                                        @error("serials.{$item->id}")
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    @endif
                                                    @error("qty.{$item->id}")
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="text-end">{{ number_format($item->qty) }}</td>
                                                <td class="text-end">{{ number_format($item->qty_received) }}</td>
                                                <td>
                                                    @if($item->remaining() > 0)
                                                        <input type="number" name="qty[{{ $item->id }}]" class="form-control form-control-sm text-end"
                                                               min="0" max="{{ $item->remaining() }}" value="{{ old("qty.{$item->id}", $item->remaining()) }}">
                                                    @else
                                                        <span class="text-muted small">Lengkap</span>
                                                    @endif
                                                </td>
                                                @if($unpaidNow)
                                                    <td>
                                                        <input type="number" name="price[{{ $item->id }}]" class="form-control form-control-sm text-end"
                                                               min="0" step="any" value="{{ old("price.{$item->id}", rtrim(rtrim(number_format((float) $item->unit_price, 2, '.', ''), '0'), '.')) }}">
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if($unpaidNow)
                                <div class="form-text">Harga hanya bisa diubah sebelum dibayar. Bila harga di nota berbeda dari PO, ubah di sini dan total pembelian ikut berubah.</div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Penerimaan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($canPayNow)
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
                                <div class="text-muted small">Nominal</div>
                                <div class="fw-bold fs-5">{{ \App\Support\Money::formatRupiah($purchase->total) }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Tanggal Bayar</label>
                                <input type="date" name="paid_at" class="form-control" value="{{ old('paid_at', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Metode</label>
                                <input type="text" name="payment_method" class="form-control" maxlength="50" value="{{ old('payment_method') }}" placeholder="Transfer / Tunai">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Bukti Pembayaran</label>
                                <input type="file" name="proof" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                <div class="form-text">Bukti transfer atau nota. JPG, PNG, WEBP, atau PDF, maksimal 5MB.</div>
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
                            <label class="form-label small">Catatan penolakan <span class="text-danger">*</span></label>
                            <textarea name="note" class="form-control" rows="2" maxlength="255" required placeholder="Jelaskan apa yang perlu diperbaiki"></textarea>
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

    @if($canOrderNow || $canEditOrderNow)
        <div class="modal fade" id="orderModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('purchases.order', $purchase) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="_modal" value="order">
                        <div class="modal-header">
                            <h6 class="modal-title">{{ $canOrderNow ? 'Tandai Dipesan' : 'Ubah Info Pesanan' }} - {{ $purchase->code }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body row g-2">
                            <div class="col-12">
                                <p class="small text-muted mb-1">
                                    @if($purchase->isOnline())
                                        Isi data pesanan dari toko online. Nama toko dan nomor pesanan wajib diisi.
                                    @else
                                        Pastikan vendor sudah dihubungi dan pesanan sudah dikonfirmasi.
                                    @endif
                                </p>
                            </div>
                            @if($canOrderNow)
                                <div class="col-md-6">
                                    <label class="form-label small">Tanggal Pesan</label>
                                    <input type="date" name="ordered_at" class="form-control" value="{{ old('ordered_at', now()->format('Y-m-d')) }}">
                                </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label small">Estimasi Tiba</label>
                                <input type="date" name="expected_arrival" class="form-control" value="{{ old('expected_arrival', $purchase->expected_arrival?->format('Y-m-d')) }}">
                            </div>
                            @if($purchase->isOnline())
                                <div class="col-md-6">
                                    <label class="form-label small">Nama Toko <span class="text-danger">*</span></label>
                                    <input type="text" name="shop_name" class="form-control" maxlength="150" value="{{ old('shop_name', $purchase->shop_name) }}" required>
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
                                <input type="text" name="courier" class="form-control" maxlength="100" value="{{ old('courier', $purchase->courier) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Nomor Resi</label>
                                <input type="text" name="tracking_number" class="form-control" maxlength="100" value="{{ old('tracking_number', $purchase->tracking_number) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Catatan</label>
                                <textarea name="order_notes" class="form-control" rows="2" maxlength="500">{{ old('order_notes', $purchase->order_notes) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Bukti Pesanan</label>
                                <input type="file" name="proof" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                <div class="form-text">Tangkapan layar pesanan atau chat vendor. JPG, PNG, WEBP, atau PDF, maksimal 5MB.</div>
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

    @if($canFundNow)
        <div class="modal fade" id="fundModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('purchases.fund', $purchase) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="_modal" value="fund">
                        <div class="modal-header">
                            <h6 class="modal-title">Catat Transfer Dana - {{ $purchase->code }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body row g-2">
                            <div class="col-12">
                                <p class="small text-muted mb-1">
                                    Dana ditransfer ke finance untuk dibayarkan ke vendor. Ini belum pengeluaran dan baru masuk buku kas saat finance mencatat pembayaran.
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Tanggal Transfer <span class="text-danger">*</span></label>
                                <input type="date" name="transferred_on" class="form-control" value="{{ old('transferred_on', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Nominal <span class="text-danger">*</span></label>
                                <input type="text" name="amount" inputmode="numeric" class="form-control" value="{{ old('amount', number_format((float) ($purchase->fund_amount ?? $purchase->total), 0, ',', '.')) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Catatan</label>
                                <input type="text" name="note" class="form-control" maxlength="255" value="{{ old('note') }}" placeholder="Mis. transfer ke rekening kas finance">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Bukti Transfer</label>
                                <input type="file" name="proof" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                <div class="form-text">JPG, PNG, WEBP, atau PDF, maksimal 5MB.</div>
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

    @if($approvedNow)
        <div class="modal fade" id="nextStepModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title"><i class="bi bi-check-circle text-success me-1"></i> {{ $purchase->code }} disetujui</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body small">
                        <div class="fw-semibold mb-2">Langkah berikutnya</div>
                        @if($nextStep['goods'])
                            <div class="d-flex gap-2 mb-2"><i class="bi bi-box-seam text-muted"></i><div>{{ $nextStep['goods'] }}</div></div>
                        @endif
                        @if($nextStep['money'])
                            <div class="d-flex gap-2 mb-2"><i class="bi bi-cash-coin text-muted"></i><div>{{ $nextStep['money'] }}</div></div>
                        @endif
                        <div class="text-muted">Pembuat dan finance sudah menerima notifikasi.</div>
                    </div>
                    <div class="modal-footer">
                        @if($waUrl && !$purchase->isOnline())
                            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary d-flex align-items-center gap-2"><i class="bi bi-whatsapp"></i> Hubungi vendor</a>
                        @endif
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Mengerti</button>
                    </div>
                </div>
            </div>
        </div>
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const el = document.getElementById('nextStepModal');
                    if (el) new bootstrap.Modal(el).show();
                });
            </script>
        @endpush
    @endif

    @if($errors->any() && in_array(old('_modal'), ['order', 'fund', 'receive', 'return', 'cancel'], true))
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const el = document.getElementById('{{ old('_modal') }}Modal');
                    if (el) new bootstrap.Modal(el).show();
                });
            </script>
        @endpush
    @endif

    @if($canReturnNow)
        @php $paidNow = $purchase->payment_status === \App\Models\Purchase::PAYMENT_PAID; @endphp
        <div class="modal fade" id="returnModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form action="{{ route('purchases.return', $purchase) }}" method="POST">
                        @csrf
                        <input type="hidden" name="_modal" value="return">
                        <div class="modal-header">
                            <h6 class="modal-title">Retur Barang - {{ $purchase->code }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted mb-3">
                                Pilih barang yang dikembalikan ke vendor. Stok Inventory berkurang sesuai jumlah retur.
                                @if($paidNow)
                                    Pembelian ini sudah dibayar, jadi isi uang kembali bila vendor mengembalikan uangnya.
                                @else
                                    Pembelian ini belum dibayar, jadi total otomatis turun sebesar nilai barang yang diretur.
                                @endif
                            </p>

                            @error('qty')
                                <div class="alert alert-danger py-2 small">{{ $message }}</div>
                            @enderror

                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-3">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th>Barang</th>
                                            <th class="text-end">Sudah diterima</th>
                                            <th class="text-end">Sudah diretur</th>
                                            <th class="u-w-130px">Retur sekarang</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($purchase->items as $item)
                                            <tr>
                                                <td>
                                                    {{ $item->name }}
                                                    @error("qty.{$item->id}")
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="text-end">{{ number_format($item->qty_received) }}</td>
                                                <td class="text-end">{{ number_format($item->qty_returned) }}</td>
                                                <td>
                                                    @if($item->returnable() > 0)
                                                        <input type="number" name="qty[{{ $item->id }}]" class="form-control form-control-sm text-end"
                                                               min="0" max="{{ $item->returnable() }}" value="{{ old("qty.{$item->id}", 0) }}">
                                                    @else
                                                        <span class="text-muted small">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label small">Alasan retur <span class="text-danger">*</span></label>
                                    <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror" maxlength="255" value="{{ old('reason') }}" placeholder="Mis. barang rusak atau tidak sesuai pesanan" required>
                                    @error('reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @if($paidNow)
                                    <div class="col-md-6">
                                        <label class="form-label small">Uang kembali dari vendor</label>
                                        <input type="text" name="refund_amount" inputmode="numeric" class="form-control @error('refund_amount') is-invalid @enderror" value="{{ old('refund_amount') }}" placeholder="Kosongkan bila belum ada">
                                        @error('refund_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Tanggal uang kembali</label>
                                        <input type="date" name="refunded_on" class="form-control @error('refunded_on') is-invalid @enderror" value="{{ old('refunded_on', now()->format('Y-m-d')) }}">
                                        @error('refunded_on')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Retur</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($canCancel)
        @php
            $cancelNeedsReason = in_array($purchase->status, [\App\Models\Purchase::STATUS_APPROVED, \App\Models\Purchase::STATUS_ORDERED], true);
            $cancelPaid = $purchase->payment_status === \App\Models\Purchase::PAYMENT_PAID;
        @endphp
        <div class="modal fade" id="cancelModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('purchases.cancel', $purchase) }}" method="POST">
                        @csrf
                        <input type="hidden" name="_modal" value="cancel">
                        <div class="modal-header">
                            <h6 class="modal-title">Batalkan Pembelian - {{ $purchase->code }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body row g-2">
                            <div class="col-12">
                                <p class="small text-muted mb-1">
                                    @if($purchase->status === \App\Models\Purchase::STATUS_ORDERED)
                                        Pesanan sudah dibuat. Pastikan vendor atau toko sudah diberi tahu sebelum membatalkan.
                                    @else
                                        Pembelian yang dibatalkan tidak bisa dilanjutkan lagi.
                                    @endif
                                </p>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Alasan pembatalan @if($cancelNeedsReason)<span class="text-danger">*</span>@else(opsional)@endif</label>
                                <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror" maxlength="255" value="{{ old('reason') }}" @required($cancelNeedsReason)>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if($cancelPaid)
                                <div class="col-12">
                                    <div class="alert alert-warning py-2 small mb-0">Pembelian ini sudah dibayar. Isi uang kembali dari vendor supaya buku kas tetap benar.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Uang kembali dari vendor</label>
                                    <input type="text" name="refund_amount" inputmode="numeric" class="form-control @error('refund_amount') is-invalid @enderror" value="{{ old('refund_amount', number_format((float) $purchase->total, 0, ',', '.')) }}">
                                    @error('refund_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Tanggal uang kembali</label>
                                    <input type="date" name="refunded_on" class="form-control @error('refunded_on') is-invalid @enderror" value="{{ old('refunded_on', now()->format('Y-m-d')) }}">
                                    @error('refunded_on')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kembali</button>
                            <button type="submit" class="btn btn-danger">Batalkan Pembelian</button>
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
