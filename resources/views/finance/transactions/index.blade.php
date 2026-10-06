@extends('layouts.app')

@section('title', 'Keuangan')

@section('content')
@php
    $rp = fn ($n) => \App\Support\Money::formatRupiah($n);
    $minDate = $lockDate ? $lockDate->copy()->addDay()->toDateString() : null;

    // Disiapkan di sini: @json tidak aman untuk ekspresi dengan banyak koma.
    $oldFormData = collect(old())->only(['type', 'amount', 'tanggal', 'category', 'project_id', 'contact_id', 'recipient', 'payment_method', 'description'])->all();
    $oldEditId = old('_edit_id') ?: null;
    $net = $totals['net'];
    $filterActive = collect($filters)->except(['from', 'to'])->filter()->isNotEmpty();

    $editPayload = fn ($t) => [
        'id'             => $t->id,
        'type'           => $t->type,
        'amount'         => (int) round((float) $t->amount),
        'tanggal'        => $t->tanggal?->format('Y-m-d'),
        'category'       => $t->category,
        'project_id'     => $t->project_id,
        'contact_id'     => $t->contact_id,
        'recipient'      => $t->recipient,
        'payment_method' => $t->payment_method,
        'description'    => $t->description,
    ];
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

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h3 class="fw-bold mb-1">Keuangan</h3>
            <p class="text-muted mb-0">Semua pemasukan dan pengeluaran perusahaan.</p>
        </div>
        @if($canManage)
            <button type="button" class="btn btn-primary" id="btnAddTransaction">
                <i class="bi bi-plus-lg me-1"></i> Catat Transaksi
            </button>
        @endif
    </div>

    @include('finance.partials.tabs', ['active' => 'transactions'])

    @if($lockDate)
        <div class="alert alert-secondary border-0 small py-2 mb-3">
            <i class="bi bi-lock me-1"></i> Buku ditutup sampai {{ $lockDate->format('d/m/Y') }}. Transaksi pada atau sebelum tanggal itu tidak bisa diubah.
        </div>
    @endif

    {{-- Filter --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('finance.transactions.index') }}" id="filterForm">
                <div class="row g-2">
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label small text-muted mb-1" for="fFrom">Dari</label>
                        <input type="date" name="from" id="fFrom" class="form-control form-control-sm" value="{{ $filters['from'] ?? '' }}">
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label small text-muted mb-1" for="fTo">Sampai</label>
                        <input type="date" name="to" id="fTo" class="form-control form-control-sm" value="{{ $filters['to'] ?? '' }}">
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label small text-muted mb-1" for="fType">Tipe</label>
                        <select name="type" id="fType" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="income" @selected(($filters['type'] ?? '') === 'income')>Pemasukan</option>
                            <option value="expense" @selected(($filters['type'] ?? '') === 'expense')>Pengeluaran</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label small text-muted mb-1" for="fCategory">Kategori</label>
                        <select name="category" id="fCategory" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach(\App\Support\FinanceCategory::TYPES as $typeKey => $typeLabel)
                                <optgroup label="{{ $typeLabel }}">
                                    @foreach(collect($categoryMap)->filter(fn ($t) => $t === $typeKey)->keys() as $name)
                                        <option value="{{ $name }}" @selected(($filters['category'] ?? '') === $name)>{{ $name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-2">
                        <label class="form-label small text-muted mb-1" for="fProject">Project</label>
                        <select name="project_id" id="fProject" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) ($filters['project_id'] ?? '') === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-2">
                        <label class="form-label small text-muted mb-1" for="fContact">Client / Vendor</label>
                        <select name="contact_id" id="fContact" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}" @selected((string) ($filters['contact_id'] ?? '') === (string) $contact->id)>{{ $contact->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label small text-muted mb-1" for="fSearch">Cari</label>
                        <input type="text" name="search" id="fSearch" class="form-control form-control-sm" placeholder="Keterangan atau penerima" value="{{ $filters['search'] ?? '' }}">
                    </div>
                    <div class="col-12 col-lg-6 d-flex flex-wrap align-items-end gap-2">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i> Terapkan</button>
                        <a href="{{ route('finance.transactions.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                        <span class="vr d-none d-md-inline"></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-preset" data-preset="month">Bulan Ini</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-preset" data-preset="lastmonth">Bulan Lalu</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-preset" data-preset="year">Tahun Ini</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-preset" data-preset="all">Semua</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Ringkasan sesuai filter --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted mb-1"><i class="bi bi-arrow-down-circle text-success me-1"></i>Total Pemasukan</div>
                    <div class="fs-4 fw-bold text-success">{{ $rp($totals['income']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted mb-1"><i class="bi bi-arrow-up-circle text-danger me-1"></i>Total Pengeluaran</div>
                    <div class="fs-4 fw-bold text-danger">{{ $rp($totals['expense']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 {{ $net >= 0 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                <div class="card-body p-3">
                    <div class="small text-muted mb-1">
                        <i class="bi {{ $net >= 0 ? 'bi-graph-up-arrow text-success' : 'bi-graph-down-arrow text-danger' }} me-1"></i>{{ $net >= 0 ? 'Laba' : 'Rugi' }}
                    </div>
                    <div class="fs-4 fw-bold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">{{ $rp(abs($net)) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel transaksi --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr class="text-muted small">
                        <th class="text-nowrap">Tanggal</th>
                        <th>Keterangan</th>
                        <th>Kategori</th>
                        <th class="text-end">Masuk</th>
                        <th class="text-end">Keluar</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $t)
                        @php
                            $meta = collect([
                                $t->project?->name,
                                $t->contact?->name ?? ($t->project?->client),
                                $t->recipient ? 'Penerima: ' . $t->recipient : null,
                                $t->payment_method,
                            ])->filter()->implode(' - ');
                        @endphp
                        <tr class="border-top">
                            <td class="text-nowrap">{{ $t->tanggal?->format('d/m/Y') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $t->description ?: '-' }}</div>
                                @if($meta !== '')
                                    <div class="text-muted small">{{ $meta }}</div>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $t->category }}</span></td>
                            <td class="text-end text-nowrap text-success fw-semibold">{{ $t->type === 'income' ? $rp($t->amount) : '' }}</td>
                            <td class="text-end text-nowrap text-danger fw-semibold">{{ $t->type === 'expense' ? $rp($t->amount) : '' }}</td>
                            <td class="text-end text-nowrap">
                                @if($t->isAuto())
                                    @if($t->source_type === \App\Models\ProjectFinanceItem::SOURCE_PURCHASE)
                                        <a href="{{ route('purchases.show', $t->source_id) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right me-1"></i> Pembelian</a>
                                    @elseif($t->source_type === \App\Models\ProjectFinanceItem::SOURCE_REPAIR)
                                        <a href="{{ route('inventory.repairs.show', $t->source_id) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right me-1"></i> Perbaikan</a>
                                    @endif
                                @elseif($lockDate && $t->tanggal && $t->tanggal->lte($lockDate))
                                    <span class="text-muted" title="Periode ditutup"><i class="bi bi-lock"></i></span>
                                @elseif($canManage)
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-transaction"
                                            data-url="{{ route('finance.transactions.update', $t) }}"
                                            data-item="{{ json_encode($editPayload($t)) }}" aria-label="Ubah">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('finance.transactions.destroy', $t) }}" class="d-inline"
                                          data-confirm="Hapus transaksi ini?" data-confirm-label="Hapus" data-confirm-danger>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                                Belum ada transaksi{{ $filterActive || !empty($filters['from']) ? ' pada filter ini' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="card-footer bg-white border-0 py-3">{{ $transactions->links() }}</div>
        @endif
    </div>
</div>

@if($canManage)
{{-- Modal catat / ubah transaksi --}}
<div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('finance.transactions.store') }}" id="transactionForm" class="modal-content" novalidate>
            @csrf
            <input type="hidden" name="_method" value="POST" id="tMethod">
            <input type="hidden" name="_form" value="transaction">
            <input type="hidden" name="_edit_id" value="" id="tEditId">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="transactionModalTitle">Catat Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                @if($errors->any() && old('_form') === 'transaction')
                    <div class="alert alert-danger small py-2">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-6">
                        <label for="tType" class="form-label small fw-semibold text-secondary">Tipe <span class="text-danger">*</span></label>
                        <select name="type" id="tType" class="form-select">
                            <option value="income">Pemasukan</option>
                            <option value="expense">Pengeluaran</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label for="tTanggal" class="form-label small fw-semibold text-secondary">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" id="tTanggal" class="form-control" max="{{ now()->toDateString() }}" @if($minDate) min="{{ $minDate }}" @endif value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="tCategory" class="form-label small fw-semibold text-secondary">Kategori <span class="text-danger">*</span></label>
                        <select name="category" id="tCategory" class="form-select"></select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="tAmount" class="form-label small fw-semibold text-secondary">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="text" inputmode="numeric" name="amount" id="tAmount" class="form-control" placeholder="0" autocomplete="off">
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="tProject" class="form-label small fw-semibold text-secondary">Project</label>
                        <select name="project_id" id="tProject" class="form-select">
                            <option value="">Tanpa project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="tContact" class="form-label small fw-semibold text-secondary">Client / Vendor</label>
                        <select name="contact_id" id="tContact" class="form-select">
                            <option value="">Tidak ada</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="tRecipient" class="form-label small fw-semibold text-secondary">Penerima</label>
                        <input type="text" name="recipient" id="tRecipient" class="form-control" maxlength="100" autocomplete="off" placeholder="Mis. nama karyawan atau kru">
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="tMethod2" class="form-label small fw-semibold text-secondary">Metode Bayar</label>
                        <input type="text" name="payment_method" id="tMethod2" class="form-control" maxlength="50" list="paymentMethods" autocomplete="off">
                        <datalist id="paymentMethods">
                            <option value="Tunai"><option value="Transfer"><option value="QRIS">
                        </datalist>
                    </div>
                    <div class="col-12">
                        <label for="tDescription" class="form-label small fw-semibold text-secondary">Keterangan</label>
                        <input type="text" name="description" id="tDescription" class="form-control" maxlength="255" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Preset periode: isi Dari/Sampai lalu kirim form filter.
    const pad = (n) => String(n).padStart(2, '0');
    const ymd = (d) => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());

    document.querySelectorAll('.btn-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const now = new Date();
            let from = '', to = '';

            if (btn.dataset.preset === 'month') { from = ymd(new Date(now.getFullYear(), now.getMonth(), 1)); to = ymd(now); }
            if (btn.dataset.preset === 'lastmonth') { from = ymd(new Date(now.getFullYear(), now.getMonth() - 1, 1)); to = ymd(new Date(now.getFullYear(), now.getMonth(), 0)); }
            if (btn.dataset.preset === 'year') { from = ymd(new Date(now.getFullYear(), 0, 1)); to = ymd(now); }

            document.getElementById('fFrom').value = from;
            document.getElementById('fTo').value = to;
            document.getElementById('filterForm').submit();
        });
    });

    @if($canManage)
    const modalEl = document.getElementById('transactionModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('transactionForm');
    const categories = @json($manualCategories);
    const storeUrl = @json(route('finance.transactions.store'));
    const updateUrlTemplate = @json(route('finance.transactions.update', ['transaction' => '__ID__']));

    const field = (id) => document.getElementById(id);

    function formatAmount(value) {
        const digits = String(value).replace(/\D/g, '');
        return digits === '' ? '' : parseInt(digits, 10).toLocaleString('id-ID');
    }

    function fillCategories(type, selected) {
        const select = field('tCategory');
        select.innerHTML = (categories[type] || []).map(function (name) {
            return '<option value="' + name.replace(/"/g, '&quot;') + '">' + name.replace(/</g, '&lt;') + '</option>';
        }).join('');
        if (selected) select.value = selected;
    }

    function openForm(data, editId) {
        form.action = editId ? updateUrlTemplate.replace('__ID__', editId) : storeUrl;
        field('tMethod').value = editId ? 'PUT' : 'POST';
        field('tEditId').value = editId || '';
        field('transactionModalTitle').textContent = editId ? 'Ubah Transaksi' : 'Catat Transaksi';

        field('tType').value = data.type || 'income';
        fillCategories(field('tType').value, data.category);
        field('tTanggal').value = data.tanggal || @json(now()->toDateString());
        field('tAmount').value = formatAmount(data.amount || '');
        field('tProject').value = data.project_id || '';
        field('tContact').value = data.contact_id || '';
        field('tRecipient').value = data.recipient || '';
        field('tMethod2').value = data.payment_method || '';
        field('tDescription').value = data.description || '';

        modal.show();
    }

    document.getElementById('btnAddTransaction').addEventListener('click', function () { openForm({}, null); });

    document.querySelectorAll('.btn-edit-transaction').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const item = JSON.parse(btn.dataset.item);
            openForm(item, item.id);
        });
    });

    field('tType').addEventListener('change', function () { fillCategories(this.value, null); });
    field('tAmount').addEventListener('input', function () { this.value = formatAmount(this.value); });

    // Gagal validasi: buka lagi modal dengan isian sebelumnya.
    @if($errors->any() && old('_form') === 'transaction')
        openForm(@json($oldFormData), @json($oldEditId));
    @endif
    @endif
});
</script>
@endsection
