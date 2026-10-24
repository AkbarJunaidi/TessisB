{{-- Konten Kwitansi dan ringkasan keuangan project; dipakai bersama kwitansi-tab dan kwitansi/project-detail.
     Variabel: $project (relasi financeItems dan kwitansis sudah di-load). --}}

@php
    $canCreateKwitansi = auth()->user()->hasPermission('finance', 'create_kwitansi');
    $canVoidKwitansi   = auth()->user()->hasPermission('finance', 'void_kwitansi');
    $incomeItems = $project->financeItems->where('type', 'income');
    $expenseItems = $project->financeItems->where('type', 'expense');
    $manualIncomeItems = $incomeItems->filter(fn ($item) => $item->source_type === null && $item->category !== \App\Support\FinanceCategory::PURCHASE_REFUND);
    $kwitansiNomors = $project->kwitansis->pluck('nomor', 'id');
    $userSignatures = auth()->user()->signatures;
    $payment = $project->paymentBreakdown();
    $maxDate = now()->format('Y-m-d');
    $minDate = \App\Support\FinanceLock::date()?->copy()->addDay()->format('Y-m-d');
@endphp

{{-- ===== RINGKASAN: Total Diterima dihitung dari buku kas (rumus di Project::combinePayments) ===== --}}
<div class="row g-2 mb-2">
    <div class="col-6 col-md-3">
        <div class="p-3 rounded-3 bg-info-subtle h-100">
            <div class="small text-muted">Estimasi Pendapatan</div>
            <div class="fw-bold fs-5 text-info">{{ \App\Support\Money::formatRupiah($project->estimated_value) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 rounded-3 bg-primary-subtle h-100">
            <div class="small text-muted">Total Diterima</div>
            <div class="fw-bold fs-5 text-primary">{{ \App\Support\Money::formatRupiah($payment['received']) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 rounded-3 bg-warning-subtle h-100">
            <div class="small text-muted">Sisa</div>
            <div class="fw-bold fs-5 text-warning-emphasis">{{ \App\Support\Money::formatRupiah($project->sisa_pembayaran) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 rounded-3 h-100 {{ $project->status_pembayaran === 'Lunas' ? 'bg-success-subtle' : 'bg-warning-subtle' }}">
            <div class="small text-muted">Status Pembayaran</div>
            <div class="fw-bold fs-5">{{ $project->status_pembayaran }}</div>
        </div>
    </div>
</div>
<div class="small text-muted mb-4">
    Rincian diterima: dari Kwitansi <strong>{{ \App\Support\Money::formatRupiah($payment['kwitansi']) }}</strong>,
    pencatatan manual <strong>{{ \App\Support\Money::formatRupiah($payment['manual']) }}</strong>
    @if($payment['legacy'] > 0)
        , kwitansi lama belum tertaut <strong>{{ \App\Support\Money::formatRupiah($payment['legacy']) }}</strong>.
        Karena ada kwitansi lama, yang dipakai adalah nilai terbesar antara pencatatan manual dan kwitansi lama.
    @else
        .
    @endif
</div>

{{-- Pendapatan tercatat (buku kas): pemasukan manual bisa langsung Cetak Kwitansi (kwitansi ditautkan ke baris itu); baris Kwitansi terkunci. --}}
<h6 class="fw-bold mb-2">Pendapatan Tercatat (Data Keuangan)</h6>
<div class="table-responsive mb-4">
    <table class="table table-sm align-middle mb-0">
        <tbody>
            @forelse($incomeItems as $item)
                <tr>
                    <td class="fw-semibold text-success u-w-30pct">{{ \App\Support\Money::formatRupiah($item->amount) }}</td>
                    <td class="text-muted">{{ $item->description ?: '-' }}</td>
                    <td class="text-end u-w-160px">
                        @if($item->source_type === \App\Models\ProjectFinanceItem::SOURCE_KWITANSI)
                            <span class="badge bg-light text-dark border" title="Otomatis dari kwitansi, tidak bisa diubah di sini">
                                <i class="bi bi-lock"></i> {{ $kwitansiNomors[$item->source_id] ?? 'Kwitansi' }}
                            </span>
                        @elseif($canCreateKwitansi && $manualIncomeItems->contains('id', $item->id))
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#cetakDariPendapatan{{ $item->id }}">
                                <i class="bi bi-printer"></i> Cetak Kwitansi
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td class="text-muted small">Belum ada data pendapatan di tab Data Keuangan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ===== PENGELUARAN (dari tab Data Keuangan) - read-only, buat konteks saja ===== --}}
<h6 class="fw-bold mb-2">Pengeluaran (Data Keuangan)</h6>
<div class="table-responsive mb-4">
    <table class="table table-sm align-middle mb-0">
        <tbody>
            @forelse($expenseItems as $item)
                <tr>
                    <td class="fw-semibold text-danger u-w-30pct">{{ \App\Support\Money::formatRupiah($item->amount) }}</td>
                    <td class="text-muted">{{ $item->description ?: '-' }}</td>
                </tr>
            @empty
                <tr><td class="text-muted small">Belum ada data pengeluaran di tab Data Keuangan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ===== RIWAYAT KWITANSI ===== --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold m-0">Riwayat Kwitansi</h6>
    @if($canCreateKwitansi)
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#formKwitansi{{ $project->id }}">
            <i class="bi bi-plus-lg"></i> Tambah Kwitansi
        </button>
    @endif
</div>

@if($canCreateKwitansi)
    <div class="collapse mb-4" id="formKwitansi{{ $project->id }}">
        <form action="{{ route('kwitansi.store', $project) }}" method="POST" class="row g-2 p-3 bg-light rounded-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label small">Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ old('tanggal', $maxDate) }}" max="{{ $maxDate }}" @if($minDate) min="{{ $minDate }}" @endif required>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Jumlah (Rp)</label>
                <input type="number" name="jumlah" min="1" max="{{ \App\Support\Limits::money() }}" class="form-control form-control-sm" value="{{ old('jumlah') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Metode Pembayaran</label>
                <input type="text" name="metode_pembayaran" class="form-control form-control-sm" placeholder="Transfer / Tunai" value="{{ old('metode_pembayaran') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Keterangan</label>
                <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="DP 50%, Pelunasan, dst" value="{{ old('keterangan') }}">
            </div>
            @include('project.partials.kwitansi-form-fields', ['userSignatures' => $userSignatures, 'uniqueId' => 'add' . $project->id])
            <div class="col-12 form-text">Pembayaran ini otomatis dicatat sebagai pemasukan di buku kas. Jangan dicatat lagi secara manual.</div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Simpan Kwitansi</button>
            </div>
        </form>
    </div>
@endif

{{-- ===== MOBILE: satu kartu per Kwitansi ===== --}}
<div class="d-md-none">
    @forelse($project->kwitansis as $kw)
        <div class="border rounded-3 p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-bold">{{ $kw->nomor }}</span>
                <span class="badge {{ $kw->status === 'Aktif' ? 'bg-success' : 'bg-secondary' }}">{{ $kw->status }}</span>
            </div>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Tanggal</span>
                <span>{{ $kw->tanggal->translatedFormat('d M Y') }}</span>
            </div>
            <div class="d-flex justify-content-between small mb-2">
                <span class="text-muted">Jumlah</span>
                <span class="fw-semibold text-success">{{ \App\Support\Money::formatRupiah($kw->jumlah) }}</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('kwitansi.preview', $kw) }}" target="_blank" class="btn btn-sm btn-outline-primary flex-fill"><i class="bi bi-printer"></i> Cetak</a>
                <a href="{{ route('kwitansi.download', $kw) }}" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-download"></i> Unduh</a>
            </div>
            @if($canVoidKwitansi && $kw->status === 'Aktif')
                <button type="button" class="btn btn-sm btn-outline-danger w-100 mt-2" data-bs-toggle="modal" data-bs-target="#voidKwitansiModal{{ $kw->id }}">
                    <i class="bi bi-x-circle"></i> Ajukan Pembatalan
                </button>
            @endif
        </div>
    @empty
        <p class="text-center text-muted py-4 mb-0">Belum ada Kwitansi untuk project ini.</p>
    @endforelse
</div>

{{-- ===== DESKTOP: tabel ===== --}}
<div class="table-responsive d-none d-md-block">
    <table class="table align-middle">
        <thead>
            <tr class="text-muted small">
                <th class="u-w-18pct">Nomor</th>
                <th class="u-w-14pct">Tanggal</th>
                <th class="text-end u-w-16pct">Jumlah</th>
                <th class="u-w-28pct">Keterangan</th>
                <th class="u-w-14pct">Status</th>
                <th class="text-center u-w-10pct">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($project->kwitansis as $kw)
                <tr class="border-top">
                    <td class="fw-semibold text-nowrap">{{ $kw->nomor }}</td>
                    <td class="text-nowrap">{{ $kw->tanggal->translatedFormat('d M Y') }}</td>
                    <td class="fw-semibold text-success text-end text-nowrap">{{ \App\Support\Money::formatRupiah($kw->jumlah) }}</td>
                    <td>{{ $kw->keterangan ?: '-' }}</td>
                    <td class=""><span class="badge {{ $kw->status === 'Aktif' ? 'bg-success' : 'bg-secondary' }}">{{ $kw->status }}</span></td>
                    <td class="text-center">
                        <a href="{{ route('kwitansi.preview', $kw) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak"><i class="bi bi-printer"></i></a>
                        <a href="{{ route('kwitansi.download', $kw) }}" class="btn btn-sm btn-primary" title="Unduh"><i class="bi bi-download"></i></a>
                        @if($canVoidKwitansi && $kw->status === 'Aktif')
                            <button type="button" class="btn btn-sm btn-outline-danger" title="Ajukan Pembatalan" data-bs-toggle="modal" data-bs-target="#voidKwitansiModal{{ $kw->id }}">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada Kwitansi untuk project ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal Cetak Kwitansi dari baris pemasukan manual: nominal dan keterangan terisi dari baris itu, tanggal default hari ini;
     submit langsung ke preview PDF (print_after=1). --}}
@if($canCreateKwitansi)
    @foreach($manualIncomeItems as $item)
        <div class="modal fade" id="cetakDariPendapatan{{ $item->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('kwitansi.store', $project) }}" method="POST">
                        @csrf
                        <input type="hidden" name="print_after" value="1">
                        <input type="hidden" name="link_item_id" value="{{ $item->id }}">
                        <div class="modal-header">
                            <h6 class="modal-title">Cetak Kwitansi dari Pendapatan</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="{{ $maxDate }}" max="{{ $maxDate }}" @if($minDate) min="{{ $minDate }}" @endif required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Jumlah (Rp)</label>
                                <input type="number" name="jumlah" min="1" max="{{ \App\Support\Limits::money() }}" class="form-control" value="{{ (int) $item->amount }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Metode Pembayaran</label>
                                <input type="text" name="metode_pembayaran" class="form-control" placeholder="Transfer / Tunai">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Keterangan</label>
                                <input type="text" name="keterangan" class="form-control" value="{{ $item->description }}">
                            </div>
                            @include('project.partials.kwitansi-form-fields', ['userSignatures' => $userSignatures, 'uniqueId' => 'inc' . $item->id])
                            <div class="col-12 form-text">Selama jumlah tidak diubah, kwitansi ditautkan ke pemasukan ini dan tidak mencatat pemasukan baru.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-printer"></i> Simpan & Cetak</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif

{{-- Modal ajukan pembatalan - dibuat di luar tabel/kartu supaya tidak nested --}}
@if($canVoidKwitansi)
    @foreach($project->kwitansis->where('status', 'Aktif') as $kw)
        <div class="modal fade" id="voidKwitansiModal{{ $kw->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('kwitansi.request-void', $kw) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h6 class="modal-title">Ajukan Pembatalan Kwitansi {{ $kw->nomor }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted">Pembatalan baru berlaku setelah disetujui Super Admin lewat halaman Approval.</p>
                            <label class="form-label small">Alasan Pembatalan</label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Ajukan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif
