@extends('layouts.app')

@section('title', 'Pembelian')

@section('content')
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

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Pembelian</h3>
            <p class="d-none d-md-block text-muted mb-0">Pembelian ke vendor.</p>
        </div>
        @if(auth()->user()->hasPermission('purchase', 'create'))
            <a href="{{ route('purchases.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Buat Pembelian
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

    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari kode / vendor..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        @foreach(\App\Models\Purchase::STATUSES as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="payment_status" class="form-select form-select-sm">
                        <option value="">Semua Pembayaran</option>
                        @foreach([\App\Models\Purchase::PAYMENT_UNPAID, \App\Models\Purchase::PAYMENT_PAID] as $payment)
                            <option value="{{ $payment }}" @selected(($filters['payment_status'] ?? '') === $payment)>{{ $payment }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $filters['from'] ?? '' }}" title="Dari tanggal">
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $filters['to'] ?? '' }}" title="Sampai tanggal">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-search"></i></button>
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
                        <th class="ps-4">Kode</th>
                        <th>Tanggal</th>
                        <th>Vendor</th>
                        <th>Project</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th>Pembayaran</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($purchases as $purchase)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $purchase->code }}</td>
                            <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                            <td>{{ $purchase->vendor_name }}</td>
                            <td title="{{ $purchase->project?->name }}">{{ $purchase->project?->short_name ?? '-' }}</td>
                            <td class="text-end">{{ \App\Support\Money::formatRupiah($purchase->total) }}</td>
                            <td><span class="badge {{ \App\Models\Purchase::STATUS_BADGES[$purchase->status] ?? 'bg-secondary' }}">{{ $purchase->status }}</span></td>
                            <td>
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
        <div class="mt-3">{{ $purchases->links('pagination::bootstrap-5') }}</div>
    @endif

</div>
@endsection
