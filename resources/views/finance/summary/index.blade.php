@extends('layouts.app')

@section('title', 'Ringkasan Keuangan')

@section('content')
@php
    $rp  = fn ($n) => \App\Support\Money::formatRupiah($n);
    $cur = $overview['current'];
    $net = $cur['net'];
    $fmt = fn ($d) => $d->format('d/m/Y');

    $hasTrendData = collect($trend['income'])->sum() + collect($trend['expense'])->sum() > 0;
    $name = fn ($n) => \App\Models\Project::shorten($n);

    // Baris tiap kartu daftar: url, label, sub, value, tone.
    $topRows = collect($projects['top'])->map(fn ($p) => [
        'url' => route('kwitansi.show-project', $p['id']), 'label' => $name($p['name']), 'value' => $rp($p['net']),
    ])->all();
    $lossRows = collect($projects['loss'])->map(fn ($p) => [
        'url' => route('kwitansi.show-project', $p['id']), 'label' => $name($p['name']), 'value' => $rp(abs($p['net'])), 'tone' => 'text-danger',
    ])->all();
    $clientRows = collect($clients)->map(fn ($c) => ['label' => $c['name'], 'value' => $rp($c['total'])])->all();
    $receivableRows = collect($receivables['items'])->map(fn ($r) => [
        'url' => route('kwitansi.show-project', $r['id']), 'label' => $name($r['name']), 'sub' => $r['client'] ?: null, 'value' => $rp($r['sisa']),
    ])->all();
    $payableRows = collect($payables['items'])->map(fn ($p) => [
        'url' => route('purchases.show', $p['id']), 'label' => $p['vendor'] ?: $p['code'], 'sub' => $p['code'] . ' - ' . $p['age'] . ' hari', 'value' => $rp($p['total']),
    ])->all();
    $overRows = collect($overBudget)->map(fn ($b) => [
        'url' => route('projects.show', $b['id']), 'label' => $name($b['name']), 'sub' => $rp($b['spent']) . ' dari ' . $rp($b['budget']), 'value' => '+' . $rp($b['over']), 'tone' => 'text-danger',
    ])->all();
@endphp

<div class="container-fluid p-0">

    <div class="mb-3">
        <h3 class="fw-bold mb-1">Keuangan</h3>
    </div>

    @include('finance.partials.tabs', ['active' => 'summary'])

    {{-- Periode --}}
    <div class="card filter-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('finance.summary') }}" class="d-flex flex-wrap align-items-end gap-2">
                @foreach($presets as $key => $label)
                    <a href="{{ route('finance.summary', ['period' => $key]) }}"
                       class="btn {{ $period['preset'] === $key ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                @endforeach
                <span class="vr d-none d-md-inline"></span>
                <input type="hidden" name="period" value="custom">
                <div class="flex-fill">
                    <label class="form-label" for="sFrom">Dari Tanggal</label>
                    <input type="date" name="from" id="sFrom" class="form-control" value="{{ $period['from']->toDateString() }}" required>
                </div>
                <div class="flex-fill">
                    <label class="form-label" for="sTo">Sampai Tanggal</label>
                    <input type="date" name="to" id="sTo" class="form-control" value="{{ $period['to']->toDateString() }}" required>
                </div>
                <button type="submit" class="btn btn-primary px-3 flex-fill flex-md-grow-0"><i class="bi bi-search me-1"></i>Cari</button>
            </form>
            <div class="text-muted small mt-2">Pembanding {{ $fmt($overview['prevFrom']) }} - {{ $fmt($overview['prevTo']) }}</div>
        </div>
    </div>

    {{-- Angka utama --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted mb-1"><i class="bi bi-arrow-down-circle me-1"></i>Pemasukan</div>
                    <div class="dash-value text-success">{{ $rp($cur['income']) }}</div>
                    @include('finance.partials.delta', ['value' => $overview['delta']['income'], 'upIsGood' => true])
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted mb-1"><i class="bi bi-arrow-up-circle me-1"></i>Pengeluaran</div>
                    <div class="dash-value text-danger">{{ $rp($cur['expense']) }}</div>
                    @include('finance.partials.delta', ['value' => $overview['delta']['expense'], 'upIsGood' => false])
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 {{ $net >= 0 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                <div class="card-body p-3">
                    <div class="small text-muted mb-1"><i class="bi {{ $net >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' }} me-1"></i>{{ $net >= 0 ? 'Laba' : 'Rugi' }}</div>
                    <div class="dash-value {{ $net >= 0 ? 'text-success' : 'text-danger' }}">{{ $rp(abs($net)) }}</div>
                    @include('finance.partials.delta', ['value' => $overview['delta']['net'], 'upIsGood' => true])
                    @if($overview['margin'] !== null)
                        <div class="text-muted small">Margin {{ number_format($overview['margin'], 1, ',', '.') }}%</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tren dan komposisi --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">Tren 12 Bulan</h6>
                    @if($hasTrendData)
                        <div class="u-pos-relative u-h-300px" id="financeTrend" data-trend="{{ json_encode($trend) }}"><canvas aria-label="Grafik garis pemasukan dan pengeluaran 12 bulan"></canvas></div>
                    @else
                        <div class="text-muted small border-top pt-2">Belum ada data.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">Komposisi Pengeluaran</h6>
                    @forelse($categories as $c)
                        <div class="py-2 {{ $loop->first ? '' : 'border-top' }}">
                            <div class="d-flex justify-content-between gap-2 mb-1">
                                <span class="text-truncate">{{ $c['name'] }}</span>
                                <span class="text-nowrap"><span class="fw-semibold">{{ $rp($c['total']) }}</span> <span class="text-muted small">{{ number_format($c['percent'], 0) }}%</span></span>
                            </div>
                            <progress class="dash-bar dash-bar-navy" max="100" value="{{ round($c['percent']) }}" aria-label="{{ $c['name'] }}: {{ number_format($c['percent'], 0) }}%"></progress>
                        </div>
                    @empty
                        <div class="text-muted small border-top pt-2">Belum ada data.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Peringkat periode --}}
    <div class="row g-3 mb-3">
        @include('finance.partials.list-card', ['title' => 'Project Paling Menguntungkan', 'rows' => $topRows])
        @include('finance.partials.list-card', ['title' => 'Project Merugi', 'rows' => $lossRows])
        @include('finance.partials.list-card', ['title' => 'Client Pemasukan Terbesar', 'rows' => $clientRows])
    </div>

    {{-- Piutang, hutang, dan anggaran --}}
    <div class="row g-3 mb-3">
        @include('finance.partials.list-card', ['title' => 'Piutang Client', 'total' => $rp($receivables['total']) . ' - ' . $receivables['count'] . ' project', 'rows' => $receivableRows])
        @include('finance.partials.list-card', ['title' => 'Hutang Vendor', 'total' => $rp($payables['total']) . ' - ' . $payables['count'] . ' pembelian', 'rows' => $payableRows])
        @include('finance.partials.list-card', ['title' => 'Melebihi Anggaran', 'rows' => $overRows])
    </div>

    <p class="text-muted small mb-4">Kwitansi aktif periode ini {{ $rp($kwitansi) }}, tidak masuk laba/rugi.</p>
</div>

@if($hasTrendData)
    @push('scripts')
        {{ \App\Support\VendorAsset::script('chartjs') }}
        <script src="{{ \App\Support\AppAsset::url('js/finance/summary.js') }}"></script>
    @endpush
@endif
@endsection
