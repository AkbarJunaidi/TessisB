@extends('layouts.app')

@section('title', 'Keuangan')

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

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Keuangan</h3>
        <p class="text-muted mb-0">Ringkasan tagihan & pembayaran seluruh project - gabungan Pendapatan (Data Keuangan) + Kwitansi.</p>
    </div>

    @include('finance.partials.tabs', ['active' => 'projects'])

    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control" placeholder="Cari nama project / client..." value="{{ $search }}">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
            </form>
        </div>
    </div>

    <div class="app-panel overflow-hidden">
        <div class="px-3 px-md-4 pt-3 pt-md-4">
            <h6 class="fw-bold mb-3">Semua Project</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Project</th>
                        <th>Estimasi</th>
                        <th>Pendapatan Tercatat</th>
                        <th>Kwitansi</th>
                        <th>Total Diterima</th>
                        <th>Sisa</th>
                        <th>Status</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($projects as $project)
                        @php
                            $pendapatanTercatat = (float) ($project->pendapatan_tercatat ?? 0);
                            $kwitansiDibayar = (float) ($project->kwitansi_total_dibayar ?? 0);
                            $totalDiterima = \App\Models\Project::combinePayments($pendapatanTercatat, $kwitansiDibayar);
                            $estimasi = (float) ($project->estimated_value ?? 0);
                            $sisa = max(0, $estimasi - $totalDiterima);
                            $statusPembayaran = $estimasi <= 0
                                ? 'Belum Ada Tagihan'
                                : ($totalDiterima >= $estimasi ? 'Lunas' : 'Belum Lunas');
                            $badgeClass = match ($statusPembayaran) {
                                'Lunas' => 'bg-success',
                                'Belum Lunas' => 'bg-warning text-dark',
                                default => 'bg-secondary',
                            };
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $project->name }}</div>
                                <div class="text-muted small">{{ $project->client ?: $project->company ?: '-' }}</div>
                            </td>
                            <td>{{ \App\Support\Money::formatRupiah($estimasi) }}</td>
                            <td class="text-success">{{ \App\Support\Money::formatRupiah($pendapatanTercatat) }}</td>
                            <td class="text-success">{{ \App\Support\Money::formatRupiah($kwitansiDibayar) }}</td>
                            <td class="text-success fw-semibold">{{ \App\Support\Money::formatRupiah($totalDiterima) }}</td>
                            <td class="{{ $sisa > 0 ? 'text-danger' : 'text-muted' }}">{{ \App\Support\Money::formatRupiah($sisa) }}</td>
                            <td><span class="badge {{ $badgeClass }}">{{ $statusPembayaran }}</span></td>
                            <td class="pe-4 text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('kwitansi.show-project', $project) }}" class="btn btn-sm btn-outline-primary"
                                       title="Lihat Detail" aria-label="Lihat detail kwitansi {{ $project->name }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($canCreate)
                                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#kwitansiModal{{ $project->id }}"
                                                title="Tambah Kwitansi" aria-label="Tambah kwitansi {{ $project->name }}">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada project.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($projects->hasPages())
        <div class="mt-3">{{ $projects->links('pagination::bootstrap-5') }}</div>
    @endif

    {{-- Modal "Tambah Kwitansi" per project - entry point ke-2 (selain tab
         Kwitansi di Project Detail), mirip pola Barang Pinjaman. --}}
    @if($canCreate)
        @php $userSignatures = auth()->user()->signatures; @endphp
        @foreach($projects as $project)
            <div class="modal fade" id="kwitansiModal{{ $project->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('kwitansi.store', $project) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h6 class="modal-title">Tambah Kwitansi - {{ $project->name }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Tanggal</label>
                                    <input type="date" name="tanggal" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Jumlah (Rp)</label>
                                    <input type="number" name="jumlah" min="1" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Metode Pembayaran</label>
                                    <input type="text" name="metode_pembayaran" class="form-control" placeholder="Transfer / Tunai">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Keterangan</label>
                                    <input type="text" name="keterangan" class="form-control" placeholder="DP 50%, dst">
                                </div>
                                @include('project.partials.kwitansi-form-fields', ['userSignatures' => $userSignatures, 'uniqueId' => 'keu' . $project->id])
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

</div>
@endsection
