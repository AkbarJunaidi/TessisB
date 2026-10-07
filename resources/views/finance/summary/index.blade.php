@extends('layouts.app')

@section('title', 'Keuangan')

@section('content')
@php
    $rp  = fn ($n) => \App\Support\Money::formatRupiah($n);
    $cur = $overview['current'];
    $net = $cur['net'];
    $fmt = fn ($d) => $d->format('d/m/Y');

    $hasTrendData = collect($trend['income'])->sum() + collect($trend['expense'])->sum() > 0;
@endphp

<div class="container-fluid p-0">

    <div class="mb-3">
        <h3 class="fw-bold mb-1">Keuangan</h3>
        <p class="text-muted mb-0">Kondisi keuangan perusahaan, dihitung menurut tanggal transaksi.</p>
    </div>

    @include('finance.partials.tabs', ['active' => 'summary'])

    {{-- Periode --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('finance.summary') }}" class="d-flex flex-wrap align-items-end gap-2">
                @foreach($presets as $key => $label)
                    <a href="{{ route('finance.summary', ['period' => $key]) }}"
                       class="btn btn-sm {{ $period['preset'] === $key ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                @endforeach
                <span class="vr d-none d-md-inline"></span>
                <input type="hidden" name="period" value="custom">
                <div>
                    <label class="form-label small text-muted mb-0" for="sFrom">Dari</label>
                    <input type="date" name="from" id="sFrom" class="form-control form-control-sm" value="{{ $period['from']->toDateString() }}" required>
                </div>
                <div>
                    <label class="form-label small text-muted mb-0" for="sTo">Sampai</label>
                    <input type="date" name="to" id="sTo" class="form-control form-control-sm" value="{{ $period['to']->toDateString() }}" required>
                </div>
                <button type="submit" class="btn btn-sm {{ $period['preset'] === 'custom' ? 'btn-primary' : 'btn-outline-primary' }}">Terapkan</button>
            </form>
            <div class="text-muted small mt-2">
                Periode {{ $fmt($period['from']) }} - {{ $fmt($period['to']) }}, pembanding {{ $fmt($overview['prevFrom']) }} - {{ $fmt($overview['prevTo']) }}.
            </div>
        </div>
    </div>

    {{-- Kartu utama --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted mb-1"><i class="bi bi-arrow-down-circle text-success me-1"></i>Pemasukan</div>
                    <div class="fs-4 fw-bold text-success">{{ $rp($cur['income']) }}</div>
                    @include('finance.partials.delta', ['value' => $overview['delta']['income'], 'upIsGood' => true])
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted mb-1"><i class="bi bi-arrow-up-circle text-danger me-1"></i>Pengeluaran</div>
                    <div class="fs-4 fw-bold text-danger">{{ $rp($cur['expense']) }}</div>
                    @include('finance.partials.delta', ['value' => $overview['delta']['expense'], 'upIsGood' => false])
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100 {{ $net >= 0 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                <div class="card-body p-3">
                    <div class="small text-muted mb-1">
                        <i class="bi {{ $net >= 0 ? 'bi-graph-up-arrow text-success' : 'bi-graph-down-arrow text-danger' }} me-1"></i>{{ $net >= 0 ? 'Laba' : 'Rugi' }}
                    </div>
                    <div class="fs-4 fw-bold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">{{ $rp(abs($net)) }}</div>
                    <div>
                        @include('finance.partials.delta', ['value' => $overview['delta']['net'], 'upIsGood' => true])
                    </div>
                    @if($overview['margin'] !== null)
                        <div class="text-muted small">Margin {{ number_format($overview['margin'], 1, ',', '.') }}%</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted mb-1"><i class="bi bi-person-lines-fill text-primary me-1"></i>Piutang Client</div>
                    <div class="fs-4 fw-bold text-primary">{{ $rp($receivables['total']) }}</div>
                    <div class="text-muted small">{{ $receivables['count'] }} project belum lunas</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted mb-1"><i class="bi bi-hourglass-split text-warning me-1"></i>Hutang Vendor</div>
                    <div class="fs-4 fw-bold text-warning-emphasis">{{ $rp($payables['total']) }}</div>
                    <div class="text-muted small">{{ $payables['count'] }} pembelian belum dibayar</div>
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
                        <div style="position: relative; height: 300px;"><canvas id="trendChart" aria-label="Grafik tren pemasukan, pengeluaran, dan laba 12 bulan"></canvas></div>
                    @else
                        <div class="text-center text-muted py-5"><i class="bi bi-bar-chart fs-3 d-block mb-2 opacity-50"></i>Belum ada transaksi pada 12 bulan ini.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">Komposisi Pengeluaran</h6>
                    @if(count($categories) > 0)
                        <div style="position: relative; height: 200px;"><canvas id="categoryChart" aria-label="Grafik komposisi pengeluaran per kategori"></canvas></div>
                        <ul class="list-unstyled small mt-3 mb-0">
                            @foreach($categories as $i => $c)
                                <li class="d-flex justify-content-between gap-2 py-1 border-top">
                                    <span><span class="category-dot d-inline-block rounded-circle me-2" data-index="{{ $i }}" style="width: 10px; height: 10px;"></span>{{ $c['name'] }}</span>
                                    <span class="text-nowrap">{{ $rp($c['total']) }} <span class="text-muted">({{ number_format($c['percent'], 0) }}%)</span></span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center text-muted py-5"><i class="bi bi-pie-chart fs-3 d-block mb-2 opacity-50"></i>Belum ada pengeluaran pada periode ini.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Peringkat --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">Project Paling Menguntungkan</h6>
                    @forelse($projects['top'] as $p)
                        <div class="d-flex justify-content-between gap-2 py-2 border-top">
                            <a href="{{ route('kwitansi.show-project', $p['id']) }}" class="text-decoration-none text-dark">{{ $p['name'] }}</a>
                            <span class="text-success fw-semibold text-nowrap">{{ $rp($p['net']) }}</span>
                        </div>
                    @empty
                        <div class="text-muted small">Belum ada project berlaba pada periode ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">Project Merugi</h6>
                    @forelse($projects['loss'] as $p)
                        <div class="d-flex justify-content-between gap-2 py-2 border-top">
                            <a href="{{ route('kwitansi.show-project', $p['id']) }}" class="text-decoration-none text-dark">{{ $p['name'] }}</a>
                            <span class="text-danger fw-semibold text-nowrap">{{ $rp(abs($p['net'])) }}</span>
                        </div>
                    @empty
                        <div class="text-muted small">Tidak ada project merugi pada periode ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">Client Pemasukan Terbesar</h6>
                    @forelse($clients as $c)
                        <div class="d-flex justify-content-between gap-2 py-2 border-top">
                            <span>{{ $c['name'] }}</span>
                            <span class="text-success fw-semibold text-nowrap">{{ $rp($c['total']) }}</span>
                        </div>
                    @empty
                        <div class="text-muted small">Belum ada pemasukan pada periode ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Piutang, hutang, dan anggaran --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">Piutang Client Terbesar</h6>
                    @forelse($receivables['items'] as $r)
                        <div class="d-flex justify-content-between gap-2 py-2 border-top">
                            <a href="{{ route('kwitansi.show-project', $r['id']) }}" class="text-decoration-none text-dark">
                                {{ $r['name'] }}
                                <span class="d-block text-muted small">{{ $r['client'] ?: '-' }}</span>
                            </a>
                            <span class="text-primary fw-semibold text-nowrap">{{ $rp($r['sisa']) }}</span>
                        </div>
                    @empty
                        <div class="text-muted small">Tidak ada tagihan yang belum lunas.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">Hutang Vendor Terlama</h6>
                    @forelse($payables['items'] as $p)
                        <div class="d-flex justify-content-between gap-2 py-2 border-top">
                            <a href="{{ route('purchases.show', $p['id']) }}" class="text-decoration-none text-dark">
                                {{ $p['vendor'] ?: $p['code'] }}
                                <span class="d-block text-muted small">{{ $p['code'] }} - {{ $p['age'] }} hari</span>
                            </a>
                            <span class="text-warning-emphasis fw-semibold text-nowrap">{{ $rp($p['total']) }}</span>
                        </div>
                    @empty
                        <div class="text-muted small">Tidak ada pembelian yang belum dibayar.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">Project Melebihi Anggaran</h6>
                    @forelse($overBudget as $b)
                        <div class="d-flex justify-content-between gap-2 py-2 border-top">
                            <a href="{{ route('projects.show', $b['id']) }}" class="text-decoration-none text-dark">
                                {{ $b['name'] }}
                                <span class="d-block text-muted small">{{ $rp($b['spent']) }} dari {{ $rp($b['budget']) }}</span>
                            </a>
                            <span class="text-danger fw-semibold text-nowrap">+{{ $rp($b['over']) }}</span>
                        </div>
                    @empty
                        <div class="text-muted small">Tidak ada project yang melebihi anggaran.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <p class="text-muted small mb-4">
        <i class="bi bi-info-circle me-1"></i>
        Kwitansi aktif pada periode ini {{ $rp($kwitansi) }}. Angka ini tidak dihitung ke laba/rugi, karena pemasukan diambil dari Data Keuangan.
    </p>
</div>

@if($hasTrendData || count($categories) > 0)
{{ \App\Support\VendorAsset::script('chartjs') }}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rp = (v) => 'Rp ' + Math.round(v).toLocaleString('id-ID');
    const short = function (v) {
        const a = Math.abs(v), s = v < 0 ? '-' : '';
        if (a >= 1e9) return s + (a / 1e9).toFixed(1).replace('.0', '') + ' M';
        if (a >= 1e6) return s + (a / 1e6).toFixed(1).replace('.0', '') + ' jt';
        if (a >= 1e3) return s + Math.round(a / 1e3) + ' rb';
        return s + a;
    };

    const trend = @json($trend);
    const trendEl = document.getElementById('trendChart');

    if (trendEl) {
        new Chart(trendEl, {
            type: 'bar',
            data: {
                labels: trend.labels,
                datasets: [
                    { type: 'bar', label: 'Pemasukan', data: trend.income, backgroundColor: '#198754', borderRadius: 4 },
                    { type: 'bar', label: 'Pengeluaran', data: trend.expense, backgroundColor: '#dc3545', borderRadius: 4 },
                    { type: 'line', label: 'Laba/Rugi', data: trend.net, borderColor: '#0b6fd6', backgroundColor: '#0b6fd6', tension: 0.3, pointRadius: 3 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + rp(c.parsed.y) } } },
                scales: { y: { ticks: { callback: (v) => short(v) } } }
            }
        });
    }

    const categories = @json($categories);
    const categoryEl = document.getElementById('categoryChart');
    const palette = ['#0b2447', '#0b6fd6', '#4a90e2', '#86b6ee', '#b9d5f6', '#546680', '#8fa1b8', '#c8d3e0'];

    if (categoryEl && categories.length) {
        new Chart(categoryEl, {
            type: 'doughnut',
            data: {
                labels: categories.map((c) => c.name),
                datasets: [{ data: categories.map((c) => c.total), backgroundColor: categories.map((c, i) => palette[i % palette.length]) }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => c.label + ': ' + rp(c.parsed) } } }
            }
        });

        document.querySelectorAll('.category-dot').forEach(function (dot) {
            dot.style.backgroundColor = palette[parseInt(dot.dataset.index, 10) % palette.length];
        });
    }
});
</script>
@endif
@endsection
