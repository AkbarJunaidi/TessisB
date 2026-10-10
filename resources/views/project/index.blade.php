@extends('layouts.app')

@section('title', 'Project Management')

@section('content')
<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Project Management</h3>
            <p class="text-muted small m-0">Kelola seluruh project dan pantau jadwal event dengan mudah.</p>
        </div>
        <div class="d-flex gap-2">
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                <a href="{{ route('projects.pipeline') }}" class="btn btn-outline-primary d-flex align-items-center gap-2 fw-medium">
                    <i class="bi bi-kanban"></i> Lihat Pipeline
                </a>
            @endif
            @if(auth()->user()->hasPermission('tracking_progress', 'create_project'))
                <a href="{{ route('projects.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm fw-medium">
                    <i class="bi bi-folder-plus"></i> Tambah Project
                </a>
            @endif
            @if(auth()->user()->isSuperAdmin())
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm fw-medium" data-bs-toggle="modal" data-bs-target="#pickProjectModal">
                    <i class="bi bi-file-earmark-text"></i> Buat Surat Jalan
                </button>
            @endif
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-tile">
                        <i class="bi bi-folder2 fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['total'] }}</div>
                        <div class="small text-muted">Total Project</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-tile">
                        <i class="bi bi-calendar-event fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['today'] }}</div>
                        <div class="small text-muted">Project Hari Ini</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-tile">
                        <i class="bi bi-activity fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['in_progress'] }}</div>
                        <div class="small text-muted">Sedang Berjalan</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-tile">
                        <i class="bi bi-check-circle fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['done'] }}</div>
                        <div class="small text-muted">Selesai</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kalender + Panel Tanggal Terpilih --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold m-0"><i class="bi bi-calendar3 me-1"></i> Kalender Project</h6>
                        <div class="d-flex align-items-center gap-2">
                            @php
                                $prevMonth = $calendarMonth - 1; $prevYear = $calendarYear;
                                if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
                                $nextMonth = $calendarMonth + 1; $nextYear = $calendarYear;
                                if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
                            @endphp
                            <a href="{{ request()->fullUrlWithQuery(['cal_month' => $prevMonth, 'cal_year' => $prevYear]) }}" class="btn btn-sm btn-light"><i class="bi bi-chevron-left"></i></a>
                            <span class="fw-semibold small">{{ \Carbon\Carbon::createFromDate($calendarYear, $calendarMonth, 1)->translatedFormat('F Y') }}</span>
                            <a href="{{ request()->fullUrlWithQuery(['cal_month' => $nextMonth, 'cal_year' => $nextYear]) }}" class="btn btn-sm btn-light"><i class="bi bi-chevron-right"></i></a>
                        </div>
                    </div>

                    @php
                        $firstDay = \Carbon\Carbon::createFromDate($calendarYear, $calendarMonth, 1);
                        $daysInMonth = $firstDay->daysInMonth;
                        $startWeekday = $firstDay->dayOfWeek; // 0 = Minggu
                        $today = now()->format('Y-m-d');
                    @endphp

                    <table class="table table-borderless text-center small mb-2">
                        <thead>
                            <tr class="text-muted">
                                <th>Min</th><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                @for($i = 0; $i < $startWeekday; $i++)
                                    <td></td>
                                @endfor
                                @for($day = 1; $day <= $daysInMonth; $day++)
                                    @php
                                        $dateStr = sprintf('%04d-%02d-%02d', $calendarYear, $calendarMonth, $day);
                                        $count = $calendarData[$dateStr] ?? 0;
                                        $isToday = $dateStr === $today;
                                    @endphp
                                    <td class="p-1">
                                        <a href="{{ request()->fullUrlWithQuery(['date' => $dateStr]) }}"
                                           class="d-inline-flex flex-column align-items-center justify-content-center rounded-circle {{ $isToday ? 'bg-primary text-white' : ($count > 0 ? 'bg-light fw-semibold' : '') }} u-w-32px u-h-32px u-td-none u-c-inherit"
                                          >
                                            {{ $day }}
                                        </a>
                                        @if($count > 0)
                                            <div class="{{ $count > 4 ? 'text-danger' : ($count >= 3 ? 'text-warning' : 'text-primary') }} u-fs-8px">&#9679;</div>
                                        @endif
                                    </td>
                                    @if(($startWeekday + $day) % 7 === 0 && $day !== $daysInMonth)
                                        </tr><tr>
                                    @endif
                                @endfor
                            </tr>
                        </tbody>
                    </table>
                    <div class="small text-muted">
                        <span class="text-primary">&#9679;</span> 1-2 Project &nbsp;
                        <span class="text-warning">&#9679;</span> 3-4 Project &nbsp;
                        <span class="text-danger">&#9679;</span> &gt; 4 Project
                    </div>

                    @if(auth()->user()->hasPermission('finance', 'export_report'))
                        <hr class="my-3">
                        <button type="button" id="exportMonthlyReportBtn" class="btn btn-outline-primary btn-sm w-100 d-flex align-items-center justify-content-center gap-2"
                                data-month="{{ $calendarMonth }}" data-year="{{ $calendarYear }}">
                            <span class="btn-text"><i class="bi bi-file-earmark-pdf me-1"></i> Export Laporan Bulanan</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold m-0">
                            Project pada {{ !empty($filters['date']) ? \Carbon\Carbon::parse($filters['date'])->translatedFormat('d F Y') : 'Semua Tanggal' }}
                        </h6>
                        @if(!empty($filters['date']))
                            <a href="{{ request()->fullUrlWithQuery(['date' => null]) }}" class="small text-danger">Reset</a>
                        @endif
                    </div>

                    @forelse($projects->take(5) as $project)
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div class="flex-grow-1 u-minw-0">
                                <div class="fw-semibold small text-truncate" title="{{ $project->name }}">{{ $project->short_name }}</div>
                                <div class="text-muted small text-truncate">
                                    <i class="bi bi-calendar-event"></i> {{ optional($project->event_date)->translatedFormat('d M Y') }}
                                    &middot; <i class="bi bi-geo-alt"></i> {{ $project->location }}
                                </div>
                            </div>
                            <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-primary flex-shrink-0 ms-2">Detail</a>
                        </div>
                    @empty
                        <p class="text-muted small m-0">Tidak ada project pada rentang ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Form Search & Filter --}}
    <div class="app-panel mb-4">
        <div class="p-3 p-md-4">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-12 col-lg-4">
                    <label for="search" class="visually-hidden">Cari project</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="search" name="search" class="form-control bg-light border-start-0 ps-0"
                               placeholder="Cari nama project, PIC, lokasi..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="status" class="visually-hidden">Filter status</label>
                    <select name="status" id="status" class="form-select bg-light">
                        <option value="">Semua Status</option>
                        @foreach(\App\Models\Project::STATUS_LABELS as $s => $label)
                            <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="pic" class="visually-hidden">Filter PIC</label>
                    <select name="pic" id="pic" class="form-select bg-light">
                        <option value="">Semua PIC</option>
                        @foreach($pics as $p)
                            <option value="{{ $p }}" @selected(($filters['pic'] ?? '') === $p)>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="month" class="visually-hidden">Filter bulan</label>
                    <select name="month" id="month" class="form-select bg-light">
                        <option value="">Semua Bulan</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected((string)($filters['month'] ?? '') === (string)$m)>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-6 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    @if(collect($filters ?? [])->filter()->isNotEmpty())
                        <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary" title="Reset Filter" aria-label="Reset filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel Semua Project --}}
    <div class="app-panel overflow-hidden">
        <div class="px-3 px-md-4 pt-3 pt-md-4">
            <h6 class="fw-bold mb-3">Semua Project ({{ $projects->total() }})</h6>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-modern table-stack align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4 py-3">No</th>
                        <th class="py-3">Nama Project</th>
                        <th class="py-3">PIC</th>
                        <th class="py-3">Tanggal</th>
                        <th class="py-3">Lokasi</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Surat Jalan</th>
                        <th class="py-3 text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($projects as $index => $project)
                        <tr>
                            <td class="ps-4 py-3 fw-semibold text-secondary" data-label="No">{{ $projects->firstItem() + $index }}</td>
                            <td class="py-3 fw-semibold" data-label="Nama Project">
                                <div class="text-truncate u-maxw-260px" title="{{ $project->name }}">
                                    {{ $project->short_name }}
                                </div>
                            </td>
                            <td class="py-3" data-label="PIC">{{ $project->pic }}</td>
                            <td class="py-3" data-label="Tanggal">{{ optional($project->event_date)->translatedFormat('d M Y') }}</td>
                            <td class="py-3" data-label="Lokasi">{{ $project->location }}</td>
                            <td class="py-3" data-label="Status">
                                <span class="badge bg-light text-dark border">{{ \App\Models\Project::STATUS_LABELS[$project->status] ?? $project->status }}</span>
                            </td>
                            <td class="py-3" data-label="Surat Jalan">
                                @if($project->suratJalans->isNotEmpty())
                                    <a href="{{ route('projects.show', $project) }}#tab-suratjalan" class="text-primary">{{ $project->suratJalans->first()->nomor }}</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="py-3 text-center pe-3 cell-block" data-label="Aksi">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('projects.show', $project) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Lihat Detail" aria-label="Lihat detail {{ $project->name }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(auth()->user()->hasPermission('tracking_progress', 'delete_project'))
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteProjectModal"
                                                data-id="{{ $project->id }}"
                                                data-name="{{ $project->name }}"
                                                title="Hapus Project" aria-label="Hapus project {{ $project->name }}">
                                            <i class="bi bi-trash"></i>
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

    <div class="mt-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
        <div class="text-muted small">
            Menampilkan {{ $projects->firstItem() ?? 0 }} - {{ $projects->lastItem() ?? 0 }} dari {{ $projects->total() }} project
        </div>
        <div>
            {{ $projects->links('pagination::bootstrap-5') }}
        </div>
    </div>

</div>

{{-- Modal Konfirmasi Hapus Project --}}
@if(auth()->user()->hasPermission('tracking_progress', 'delete_project'))
<div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-labelledby="deleteProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="deleteProjectModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus Project
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="deleteProjectForm" method="POST">
                @csrf
                @method('DELETE')

                <div class="modal-body p-4">
                    <p class="text-dark fw-medium mb-3">Apakah Anda yakin ingin menghapus project ini?</p>

                    <div class="bg-light p-3 rounded-3 border">
                        <small class="text-muted d-block text-uppercase fw-bold u-fs-0p7rem">NAMA PROJECT:</small>
                        <span id="modal-project-name" class="fw-bold text-dark fs-6">-</span>
                    </div>

                    <small class="text-danger d-block mt-3">
                        <i class="bi bi-info-circle me-1"></i>Data ini akan dipindahkan ke Sampah dan masih dapat dipulihkan kembali.
                    </small>

                    <div id="return-status-loading" class="text-muted small mt-3 d-none">
                        <span class="spinner-border spinner-border-sm me-1"></span> Memeriksa status pengembalian barang...
                    </div>

                    <div id="return-status-ok" class="text-success small mt-3 d-none">
                        <i class="bi bi-check-circle me-1"></i>Seluruh barang yang dipinjam untuk project ini sudah dikembalikan.
                    </div>

                    <div id="return-status-warning" class="alert alert-warning small mt-3 mb-0 d-none">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Barang berikut untuk project ini <strong>belum dikembalikan</strong>:
                        <ul id="return-status-list" class="mb-0 mt-2 ps-3"></ul>
                        <div class="mt-2">Jika project ini dihapus, unit barang di atas akan otomatis ditandai <strong>"Hilang"</strong> di Inventory.</div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-secondary px-3 fw-medium" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4 fw-medium shadow-sm">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ \App\Support\AppAsset::url('js/project/index.js') }}"></script>
@endif

{{-- Modal pilih project untuk Buat Surat Jalan langsung dari halaman index --}}
@if(auth()->user()->isSuperAdmin())
<div class="modal fade" id="pickProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Buat Surat Jalan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small fw-semibold text-secondary">Pilih Project</label>
                <select id="pickProjectSelect" class="form-select">
                    <option value="">-- Pilih Project --</option>
                    @foreach($allProjectsForPicker as $project)
                        <option value="{{ route('surat-jalan.create', $project) }}">{{ $project->short_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="pickProjectGo" class="btn btn-primary">Lanjutkan</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('pickProjectGo').addEventListener('click', function () {
        const url = document.getElementById('pickProjectSelect').value;
        if (url) { window.location.href = url; }
    });
</script>
@endif

@if(auth()->user()->hasPermission('finance', 'export_report'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('exportMonthlyReportBtn');
    if (!btn) return;

    btn.addEventListener('click', function () {
        const month = btn.dataset.month;
        const year = btn.dataset.year;
        const btnText = btn.querySelector('.btn-text');
        const spinner = btn.querySelector('.spinner-border');

        btn.disabled = true;
        btnText.classList.add('d-none');
        spinner.classList.remove('d-none');

        fetch(`{{ route('reports.finance.monthly') }}?month=${month}&year=${year}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
        })
            .then(async function (response) {
                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    throw new Error(data.message || 'Gagal membuat laporan.');
                }
                return response.blob();
            })
            .then(function (blob) {
                const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                const filename = `Laporan-Keuangan-${monthNames[month - 1]}-${year}.pdf`;

                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            })
            .catch(function (err) {
                AppUI.toast(err.message);
            })
            .finally(function () {
                btn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
            });
    });
});
</script>
@endif
@endsection
