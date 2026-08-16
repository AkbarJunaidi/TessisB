@extends('layouts.app')

@section('title', 'Detail Project - ' . $project->name)

@section('content')
<div class="container-fluid p-0 pb-5 pb-md-0">

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

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h3 class="fw-bold text-dark m-0">{{ $project->name }}</h3>

                @if(auth()->user()->hasPermission('tracking_progress', 'edit_project'))
                    <form action="{{ route('projects.update-status', $project) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="form-select form-select-sm border-0 bg-light fw-semibold" onchange="this.form.submit()" style="width:auto;">
                            @foreach(['Draft','Scheduled','Confirmed','In Progress','On Review','Done'] as $s)
                                <option value="{{ $s }}" @selected($project->status === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <span class="badge bg-success-subtle text-success">{{ $project->status }}</span>
                @endif
            </div>
            <p class="text-muted small m-0">Kelola informasi project, tim, barang, dan dokumen.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>

            @if(auth()->user()->hasPermission('tracking_progress', 'edit_project'))
                <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i> Edit Project
                </a>
            @endif

            @if(auth()->user()->hasPermission('surat_jalan', 'view'))
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-file-earmark-text"></i> Surat Jalan
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @if(auth()->user()->hasPermission('surat_jalan', 'create'))
                            <li><a class="dropdown-item" href="{{ route('surat-jalan.create', $project) }}"><i class="bi bi-plus-lg me-1"></i> Buat Surat Jalan</a></li>
                        @endif
                        <li>
                            <a class="dropdown-item go-to-surat-jalan-tab" href="#tab-suratjalan">
                                <i class="bi bi-eye me-1"></i> Lihat Surat Jalan
                            </a>
                        </li>
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Informasi Kunci --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">{{ $project->category ?: '-' }}</h5>
                    <div class="row g-3 small">
                        <div class="col-md-6">
                            <div class="text-muted">Client</div>
                            <div class="fw-semibold">{{ $project->client }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">PIC</div>
                            <div class="fw-semibold">{{ $project->pic }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">Tanggal Acara</div>
                            <div class="fw-semibold">
                                {{ optional($project->event_date)->translatedFormat('d F Y') }}
                                @if($project->event_end_date && !$project->event_end_date->isSameDay($project->event_date))
                                    &ndash; {{ $project->event_end_date->translatedFormat('d F Y') }}
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">Jam Acara</div>
                            <div class="fw-semibold">{{ substr($project->event_time_start ?? '', 0, 5) }}{{ $project->event_time_end ? ' - '.substr($project->event_time_end, 0, 5) : '' }} WIB</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">Lokasi / Venue</div>
                            <div class="fw-semibold">{{ $project->location }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">Alamat Lengkap</div>
                            <div class="fw-semibold">{{ $project->address }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">Estimasi Durasi</div>
                            <div class="fw-semibold">{{ $project->estimated_duration_minutes ? round($project->estimated_duration_minutes / 60, 1).' jam' : '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">Prioritas</div>
                            <div class="fw-semibold">{{ $project->priority }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted">Deskripsi Project</div>
                            <div>{{ $project->description ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Crew + Dokumen --}}
        <div class="col-lg-4 d-flex flex-column gap-3">
            @include('project.partials.crew-form', ['project' => $project])

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold m-0">Dokumen</h6>
                        <div class="d-flex align-items-center gap-2">
                            @if($project->folder)
                                <a href="{{ route('folders.show', $project->folder) }}" class="small text-decoration-none">Lihat Semua</a>
                            @endif
                        </div>
                    </div>

                    @php
                        $recentFiles = $project->folder ? $project->folder->files->sortByDesc('created_at')->take(5) : collect();
                        $fileIconMap = [
                            'pdf'  => ['bg-danger-subtle', 'text-danger', 'bi-file-earmark-pdf'],
                            'xlsx' => ['bg-success-subtle', 'text-success', 'bi-file-earmark-excel'],
                            'xls'  => ['bg-success-subtle', 'text-success', 'bi-file-earmark-excel'],
                            'csv'  => ['bg-success-subtle', 'text-success', 'bi-file-earmark-excel'],
                            'docx' => ['bg-primary-subtle', 'text-primary', 'bi-file-earmark-word'],
                            'doc'  => ['bg-primary-subtle', 'text-primary', 'bi-file-earmark-word'],
                            'pptx' => ['bg-warning-subtle', 'text-warning', 'bi-file-earmark-ppt'],
                            'ppt'  => ['bg-warning-subtle', 'text-warning', 'bi-file-earmark-ppt'],
                            'zip'  => ['bg-secondary-subtle', 'text-secondary', 'bi-file-earmark-zip'],
                            'jpg'  => ['bg-info-subtle', 'text-info', 'bi-file-earmark-image'],
                            'jpeg' => ['bg-info-subtle', 'text-info', 'bi-file-earmark-image'],
                            'png'  => ['bg-info-subtle', 'text-info', 'bi-file-earmark-image'],
                        ];
                    @endphp

                    @forelse($recentFiles as $file)
                        @php
                            $iconInfo = $fileIconMap[strtolower($file->file_type)] ?? ['bg-light', 'text-secondary', 'bi-file-earmark-text'];
                        @endphp
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="rounded {{ $iconInfo[0] }} {{ $iconInfo[1] }} d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;">
                                <i class="bi {{ $iconInfo[2] }}"></i>
                            </div>
                            <div class="flex-grow-1" style="min-width:0;">
                                <div class="small fw-semibold text-truncate">{{ $file->file_name }}</div>
                                <div class="small text-muted">{{ strtoupper($file->file_type) }} &middot; {{ optional($file->created_at)->translatedFormat('d M Y') }} &middot; {{ $file->readable_size }}</div>
                            </div>
                            <a href="{{ route('files.download', $file) }}" class="btn btn-sm btn-outline-secondary flex-shrink-0"><i class="bi bi-download"></i></a>
                        </div>
                    @empty
                        <p class="text-muted small m-0">Belum ada dokumen.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if(auth()->user()->hasPermission('finance', 'view'))
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Data Keuangan</h6>

                        @if(auth()->user()->hasPermission('finance', 'manage'))
                        <form id="financeForm" action="{{ route('projects.finance.update', $project) }}" method="POST">
                            @csrf
                            @method('PUT')

                            {{-- PENDAPATAN --}}
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold m-0">Pendapatan</h6>
                                <button type="button" id="addIncomeRow" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Pendapatan
                                </button>
                            </div>
                            <div class="row g-2 small text-secondary fw-semibold mb-1 d-none d-md-flex">
                                <div class="col-md-4">Nominal (Rp)</div>
                                <div class="col-md-7">Keterangan</div>
                            </div>
                            <div id="incomeRows">
                                @foreach($project->financeItems->where('type', 'income')->values() as $i => $item)
                                    @include('project.partials.finance-row', ['group' => 'incomes', 'index' => $i, 'item' => $item])
                                @endforeach
                            </div>
                            <div class="p-2 px-3 rounded-3 bg-success-subtle d-flex justify-content-between align-items-center mb-4 mt-2">
                                <span class="fw-semibold small">Total Pendapatan</span>
                                <span class="fw-bold text-success" id="totalIncomeDisplay">{{ \App\Support\Money::formatRupiah($project->total_income) }}</span>
                            </div>

                            {{-- PENGELUARAN --}}
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold m-0">Pengeluaran</h6>
                                <button type="button" id="addExpenseRow" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Pengeluaran
                                </button>
                            </div>
                            <div class="row g-2 small text-secondary fw-semibold mb-1 d-none d-md-flex">
                                <div class="col-md-4">Nominal (Rp)</div>
                                <div class="col-md-7">Keterangan</div>
                            </div>
                            <div id="expenseRows">
                                @foreach($project->financeItems->where('type', 'expense')->values() as $i => $item)
                                    @include('project.partials.finance-row', ['group' => 'expenses', 'index' => $i, 'item' => $item])
                                @endforeach
                            </div>
                            <div class="p-2 px-3 rounded-3 bg-danger-subtle d-flex justify-content-between align-items-center mb-4 mt-2">
                                <span class="fw-semibold small">Total Pengeluaran</span>
                                <span class="fw-bold text-danger" id="totalExpenseDisplay">{{ \App\Support\Money::formatRupiah($project->total_expense) }}</span>
                            </div>

                            {{-- LABA BERSIH --}}
                            <div class="p-3 rounded-3 {{ $project->profit >= 0 ? 'bg-success-subtle' : 'bg-danger-subtle' }} d-flex justify-content-between align-items-center mb-3" id="profitBox">
                                <span class="fw-bold">Laba Bersih</span>
                                <span class="fw-bold fs-5 {{ $project->profit >= 0 ? 'text-success' : 'text-danger' }}" id="profitDisplay">{{ \App\Support\Money::formatRupiah($project->profit) }}</span>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Simpan Data Keuangan
                                </button>
                            </div>
                        </form>
                        @else
                        {{-- Hanya boleh melihat (hasPermission finance.view tanpa finance.manage) --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold m-0">Pendapatan</h6>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle mb-0">
                                <tbody>
                                    @forelse($project->financeItems->where('type', 'income') as $item)
                                        <tr>
                                            <td class="fw-semibold text-success" style="width: 35%;">{{ \App\Support\Money::formatRupiah($item->amount) }}</td>
                                            <td class="text-muted">{{ $item->description ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-muted small">Belum ada data pendapatan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-2 px-3 rounded-3 bg-success-subtle d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-semibold small">Total Pendapatan</span>
                            <span class="fw-bold text-success">{{ \App\Support\Money::formatRupiah($project->total_income) }}</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold m-0">Pengeluaran</h6>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle mb-0">
                                <tbody>
                                    @forelse($project->financeItems->where('type', 'expense') as $item)
                                        <tr>
                                            <td class="fw-semibold text-danger" style="width: 35%;">{{ \App\Support\Money::formatRupiah($item->amount) }}</td>
                                            <td class="text-muted">{{ $item->description ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-muted small">Belum ada data pengeluaran.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-2 px-3 rounded-3 bg-danger-subtle d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-semibold small">Total Pengeluaran</span>
                            <span class="fw-bold text-danger">{{ \App\Support\Money::formatRupiah($project->total_expense) }}</span>
                        </div>

                        <div class="p-3 rounded-3 {{ $project->profit >= 0 ? 'bg-success-subtle' : 'bg-danger-subtle' }} d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Laba Bersih</span>
                            <span class="fw-bold fs-5 {{ $project->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ \App\Support\Money::formatRupiah($project->profit) }}</span>
                        </div>
                        <small class="text-muted d-block mt-3">
                            <i class="bi bi-info-circle me-1"></i>Anda hanya memiliki akses lihat untuk data keuangan.
                        </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3 d-none d-md-flex" id="projectTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-kanban" type="button">Kanban</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-barang" type="button">Barang</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-suratjalan" type="button">Surat Jalan</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-dokumen" type="button">Dokumen</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-kanban">
            @include('project.partials.kanban-tab', ['project' => $project, 'groupedTasks' => $groupedTasks])
        </div>

        <div class="tab-pane fade" id="tab-barang">
            @include('project.partials.barang-tab', ['project' => $project])
        </div>

        <div class="tab-pane fade" id="tab-suratjalan">
            @include('project.partials.surat-jalan-tab', ['project' => $project])
        </div>

        <div class="tab-pane fade" id="tab-dokumen">
            @include('project.partials.documents-tab', ['project' => $project, 'allFolders' => $allFolders])
        </div>
    </div>

    {{-- Catatan: card penuh di bagian paling bawah (bukan tab lagi) --}}
    <div class="mt-3">
        @include('project.partials.notes-tab', ['project' => $project])
    </div>

</div>

{{-- Bottom Navigation khusus Mobile (tanpa Floating Action Button, sesuai PRD) --}}
<nav class="d-md-none fixed-bottom bg-white border-top shadow-sm">
    <div class="d-flex justify-content-around py-2">
        <a href="#tab-kanban" data-bs-toggle="tab" class="text-center text-decoration-none text-secondary small mobile-tab-link active">
            <i class="bi bi-kanban d-block fs-5"></i>Kanban
        </a>
        <a href="#tab-barang" data-bs-toggle="tab" class="text-center text-decoration-none text-secondary small mobile-tab-link">
            <i class="bi bi-box-seam d-block fs-5"></i>Barang
        </a>
        <a href="#tab-suratjalan" data-bs-toggle="tab" class="text-center text-decoration-none text-secondary small mobile-tab-link">
            <i class="bi bi-file-earmark-text d-block fs-5"></i>Surat Jalan
        </a>
        <a href="#tab-dokumen" data-bs-toggle="tab" class="text-center text-decoration-none text-secondary small mobile-tab-link">
            <i class="bi bi-folder d-block fs-5"></i>Dokumen
        </a>
    </div>
</nav>

<script>
    // Bottom nav mobile ikut menyorot tab aktif (konsisten dengan tab desktop)
    document.querySelectorAll('.mobile-tab-link').forEach(function (link) {
        link.addEventListener('click', function () {
            document.querySelectorAll('.mobile-tab-link').forEach(l => l.classList.remove('active', 'text-primary'));
            this.classList.add('active', 'text-primary');
        });
    });

    // "Lihat Surat Jalan" di dropdown header: pindah tab (dengan memicu tombol tab
    // aslinya secara langsung, supaya Bootstrap benar-benar menonaktifkan tab yang
    // sedang aktif) + scroll ke section tab-nya + sinkronkan bottom nav mobile
    document.querySelectorAll('.go-to-surat-jalan-tab').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const realTabButton = document.querySelector('#projectTabs [data-bs-target="#tab-suratjalan"]');
            if (realTabButton) {
                bootstrap.Tab.getOrCreateInstance(realTabButton).show();
            }

            // Sinkronkan highlight bottom nav mobile juga
            document.querySelectorAll('.mobile-tab-link').forEach(l => l.classList.remove('active', 'text-primary'));
            const mobileLink = document.querySelector('.mobile-tab-link[href="#tab-suratjalan"]');
            if (mobileLink) mobileLink.classList.add('active', 'text-primary');

            setTimeout(function () {
                const tabsSection = document.getElementById('projectTabs');
                if (tabsSection) tabsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 50);
        });
    });
</script>

{{-- Script Data Keuangan: tambah/hapus baris + kalkulasi total live (client-side) --}}
@if(auth()->user()->hasPermission('finance', 'manage'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    let incomeIndex = {{ $project->financeItems->where('type', 'income')->count() }};
    let expenseIndex = {{ $project->financeItems->where('type', 'expense')->count() }};

    const incomeRows = document.getElementById('incomeRows');
    const expenseRows = document.getElementById('expenseRows');

    function formatRupiah(value) {
        return 'Rp ' + Math.round(value).toLocaleString('id-ID');
    }

    function buildRow(group, index) {
        return `
            <div class="row g-2 mb-2 finance-row">
                <div class="col-4">
                    <input type="text" inputmode="numeric" name="${group}[${index}][amount]" class="form-control form-control-sm finance-amount-input" placeholder="0" required>
                </div>
                <div class="col-7">
                    <input type="text" name="${group}[${index}][description]" class="form-control form-control-sm" placeholder="Keterangan">
                </div>
                <div class="col-1">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-finance-row"><i class="bi bi-trash"></i></button>
                </div>
            </div>`;
    }

    function parseAmount(value) {
        // Hilangkan semua karakter selain digit (titik ribuan dibuang), baru jadi angka murni
        const digitsOnly = String(value).replace(/\D/g, '');
        return digitsOnly === '' ? 0 : parseInt(digitsOnly, 10);
    }

    function recalculate() {
        const sumRows = (container) => Array.from(container.querySelectorAll('.finance-amount-input'))
            .reduce((sum, input) => sum + parseAmount(input.value), 0);

        const totalIncome = sumRows(incomeRows);
        const totalExpense = sumRows(expenseRows);
        const profit = totalIncome - totalExpense;

        document.getElementById('totalIncomeDisplay').textContent = formatRupiah(totalIncome);
        document.getElementById('totalExpenseDisplay').textContent = formatRupiah(totalExpense);

        const profitDisplay = document.getElementById('profitDisplay');
        const profitBox = document.getElementById('profitBox');
        profitDisplay.textContent = formatRupiah(profit);

        profitDisplay.classList.toggle('text-success', profit >= 0);
        profitDisplay.classList.toggle('text-danger', profit < 0);
        profitBox.classList.toggle('bg-success-subtle', profit >= 0);
        profitBox.classList.toggle('bg-danger-subtle', profit < 0);
    }

    document.getElementById('addIncomeRow').addEventListener('click', function () {
        incomeRows.insertAdjacentHTML('beforeend', buildRow('incomes', incomeIndex));
        incomeIndex++;
    });

    document.getElementById('addExpenseRow').addEventListener('click', function () {
        expenseRows.insertAdjacentHTML('beforeend', buildRow('expenses', expenseIndex));
        expenseIndex++;
    });

    document.getElementById('financeForm').addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-finance-row');
        if (btn) {
            btn.closest('.finance-row').remove();
            recalculate();
        }
    });

    document.getElementById('financeForm').addEventListener('input', function (e) {
        if (e.target.classList.contains('finance-amount-input')) {
            const cursorFromEnd = e.target.value.length - e.target.selectionStart;
            const rawValue = parseAmount(e.target.value);

            e.target.value = rawValue === 0 ? '' : rawValue.toLocaleString('id-ID');

            // Pertahankan posisi kursor relatif dari akhir teks, supaya tidak "lompat" ke ujung
            const newPos = Math.max(e.target.value.length - cursorFromEnd, 0);
            e.target.setSelectionRange(newPos, newPos);

            recalculate();
        }
    });
});
</script>
@endif
@endsection
