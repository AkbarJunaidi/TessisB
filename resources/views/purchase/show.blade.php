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
@endphp

<div class="container-fluid p-0">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-3" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1">{{ $purchase->code }}</h3>
            <span class="badge {{ \App\Models\Purchase::STATUS_BADGES[$purchase->status] ?? 'bg-secondary' }}">{{ $purchase->status }}</span>
            <span class="badge {{ $purchase->payment_status === \App\Models\Purchase::PAYMENT_PAID ? 'bg-success' : 'bg-warning text-dark' }}">{{ $purchase->payment_status }}</span>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>

            @if($canDecide)
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                    <i class="bi bi-check2-circle me-1"></i> Setujui
                </button>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-circle me-1"></i> Tolak
                </button>
            @endif

            @if($purchase->isEditable() && $canEdit)
                <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-warning">
                    <i class="bi bi-pencil-square me-1"></i> Ubah
                </a>
                <form action="{{ route('purchases.submit', $purchase) }}" method="POST" onsubmit="return confirm('Ajukan pembelian ini untuk approval?');">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Ajukan Approval</button>
                </form>
            @endif

            @if($purchase->canReceive() && $user->hasPermission('purchase', 'receive'))
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#receiveModal">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Barang Diterima
                </button>
            @endif

            @if($purchase->canPay() && $user->hasPermission('purchase', 'pay'))
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payModal">
                    <i class="bi bi-cash-coin me-1"></i> Catat Pembayaran
                </button>
            @endif

            @if($purchase->canCancel() && $canEdit)
                <form action="{{ route('purchases.cancel', $purchase) }}" method="POST" onsubmit="return confirm('Batalkan pembelian ini?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i> Batalkan</button>
                </form>
            @endif

            @if($purchase->canDelete() && $user->hasPermission('purchase', 'delete'))
                <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" onsubmit="return confirm('Hapus draft pembelian ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Hapus</button>
                </form>
            @endif
        </div>
    </div>

    @if($purchase->status === \App\Models\Purchase::STATUS_REJECTED && $latestApproval)
        <div class="alert alert-danger border-0 shadow-sm">
            <div class="fw-semibold">Pembelian ditolak oleh {{ $latestApproval->decidedBy->name ?? '-' }}</div>
            <div class="small">{{ $latestApproval->decision_note ?: 'Tanpa catatan.' }} Ubah lalu ajukan kembali.</div>
        </div>
    @endif

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
                            <a href="{{ route('projects.show', $purchase->project) }}" class="text-decoration-none">{{ $purchase->project->name }}</a>
                        @else
                            <div>Stok umum</div>
                        @endif
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Tanggal Pembelian</div>
                        <div>{{ $purchase->purchase_date->translatedFormat('d M Y') }}</div>
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
                            @if($purchase->vendor->phone && $purchase->vendor->has_whatsapp)
                                <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $purchase->vendor->phone)) }}" target="_blank" rel="noopener" class="text-decoration-none">
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
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Riwayat Status</h6>

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
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>Item</th>
                                <th class="text-end">Jumlah</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Subtotal</th>
                                <th>Inventory</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $item)
                                <tr class="border-top">
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td class="text-end">{{ number_format($item->qty) }}</td>
                                    <td class="text-end">{{ \App\Support\Money::formatRupiah($item->unit_price) }}</td>
                                    <td class="text-end">{{ \App\Support\Money::formatRupiah($item->subtotal) }}</td>
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
        </div>

    </div>

    @if($purchase->canReceive() && $user->hasPermission('purchase', 'receive'))
        <div class="modal fade" id="receiveModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('purchases.receive', $purchase) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h6 class="modal-title">Barang Diterima - {{ $purchase->code }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted mb-3">
                                Stok Inventory akan diperbarui sesuai item yang ditandai sebagai stok.
                            </p>

                            @foreach($newItems as $item)
                                <div class="mb-3">
                                    <label class="form-label small mb-1">
                                        Serial number - {{ $item->name }} ({{ number_format($item->qty) }} unit)
                                    </label>
                                    <input type="text" name="serials[{{ $item->id }}]" class="form-control" maxlength="255"
                                           value="{{ old("serials.{$item->id}") }}" required>
                                </div>
                            @endforeach
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Konfirmasi Diterima</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($purchase->canPay() && $user->hasPermission('purchase', 'pay'))
        <div class="modal fade" id="payModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('purchases.pay', $purchase) }}" method="POST">
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
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Simpan</button>
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
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Setujui</button>
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
                            <label class="form-label small">Catatan (opsional)</label>
                            <textarea name="note" class="form-control" rows="2" maxlength="255"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Tolak</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any() && old('serials') !== null)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const el = document.getElementById('receiveModal');
                    if (el) new bootstrap.Modal(el).show();
                });
            </script>
        @endpush
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
