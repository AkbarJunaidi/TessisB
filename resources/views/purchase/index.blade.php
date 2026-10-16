@extends('layouts.app')

@section('title', 'Pembelian')

@section('content')
<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Pembelian</h3>
            <p class="d-none d-md-block text-muted mb-0">Pembelian ke vendor.</p>
        </div>
        @if(auth()->user()->hasPermission('purchase', 'create'))
            <a href="{{ route('purchases.create') }}" class="btn btn-primary d-flex align-items-center gap-2 fw-medium">
                <i class="bi bi-plus-lg"></i> Buat Pembelian
            </a>
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Pembelian Bulan Ini</div>
                    <div class="fw-bold fs-5">{{ \App\Support\Money::formatRupiah($stats['month_total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Menunggu Approval</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['pending_approval']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Menunggu Barang Diterima</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['awaiting_receipt']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Belum Dibayar</div>
                    <div class="fw-bold fs-5 text-danger">{{ \App\Support\Money::formatRupiah($stats['unpaid_total']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card filter-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-6">
                    <label for="fSearch" class="form-label">Cari</label>
                    <div class="search-field">
                        <i class="bi bi-search"></i>
                        <input type="text" id="fSearch" name="search" class="form-control" placeholder="Cari kode / vendor..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label for="fStatus" class="form-label">Status</label>
                    <select name="status" id="fStatus" class="form-select">
                        <option value="">Semua Status</option>
                        @foreach(\App\Models\Purchase::STATUSES as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="fPayment" class="form-label">Pembayaran</label>
                    <select name="payment_status" id="fPayment" class="form-select">
                        <option value="">Semua Pembayaran</option>
                        @foreach([\App\Models\Purchase::PAYMENT_UNPAID, \App\Models\Purchase::PAYMENT_PAID] as $payment)
                            <option value="{{ $payment }}" @selected(($filters['payment_status'] ?? '') === $payment)>{{ $payment }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="fFrom" class="form-label">Dari Tanggal</label>
                    <input type="date" name="from" id="fFrom" class="form-control" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="fTo" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="to" id="fTo" class="form-control" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="col-12 col-md-6 filter-actions filter-actions-end">
                    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary px-3">Reset</a>
                    <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search me-1"></i>Cari</button>
                </div>
            </form>
        </div>
    </div>

    <div class="app-panel overflow-hidden">
        <div class="px-3 px-md-4 pt-3 pt-md-4">
            <h6 class="fw-bold mb-3">Semua Pembelian</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4 u-w-11pct">Kode</th>
                        <th class="u-w-12pct">Tanggal</th>
                        <th class="u-w-18pct">Vendor</th>
                        <th class="u-w-18pct">Project</th>
                        <th class="text-end u-w-14pct">Total</th>
                        <th class="u-w-10pct">Status</th>
                        <th class="u-w-10pct">Pembayaran</th>
                        <th class="pe-4 text-center u-w-7pct">Aksi</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($purchases as $purchase)
                        <tr>
                            <td class="ps-4 fw-semibold text-nowrap">{{ $purchase->code }}</td>
                            <td class="text-nowrap">{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                            <td>{{ $purchase->vendor_name }}</td>
                            <td title="{{ $purchase->project?->name }}">{{ $purchase->project?->short_name ?? '-' }}</td>
                            <td class="text-end text-nowrap">{{ \App\Support\Money::formatRupiah($purchase->total) }}</td>
                            <td class=""><span class="badge {{ \App\Models\Purchase::STATUS_BADGES[$purchase->status] ?? 'bg-secondary' }}">{{ $purchase->status }}</span></td>
                            <td class="">
                                <span class="badge {{ $purchase->payment_status === \App\Models\Purchase::PAYMENT_PAID ? 'bg-success' : 'bg-warning text-dark' }}">{{ $purchase->payment_status }}</span>
                            </td>
                            <td class="pe-4 text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-outline-primary"
                                       title="Lihat Detail" aria-label="Lihat detail pembelian {{ $purchase->code }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data pembelian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($purchases->hasPages())
        <div class="mt-3">{{ $purchases->links('pagination.app') }}</div>
    @endif

</div>
@endsection
