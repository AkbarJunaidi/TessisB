@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    @php
        $user = auth()->user();
        $rp = fn ($v) => \App\Support\Money::formatRupiah($v);
        $compact = fn ($v) => \App\Support\Money::compact($v);
        $isManager = $user->hasRole('super_admin', 'admin');

        $stageMap = collect($pipeline ?? [])->keyBy('status');
        $activeProjects = collect($pipeline ?? [])->where('status', '!=', 'Done')->sum('count');
        $borrowedCount = collect($equipment['items'] ?? [])->firstWhere('label', 'Dipinjam')['count'] ?? 0;

        $stageBars = ['Draft' => 'dash-bar-mute', 'In Progress' => 'dash-bar-navy', 'Done' => 'dash-bar-mute'];
        $unitBars = ['Dipinjam' => 'dash-bar-navy', 'Perbaikan' => 'dash-bar-warn', 'Rusak' => 'dash-bar-danger', 'Hilang' => 'dash-bar-mute', 'Lainnya' => 'dash-bar-mute'];

        $hasPipeline = !empty($pipeline);
        $hasEquipment = !empty($equipment);

        // Rute Pipeline dan Inventaris khusus admin; role lain diarahkan ke halaman yang boleh dibuka.
        $pipelineUrl = $isManager ? route('projects.pipeline') : route('projects.index');
        $inventoryUrl = $isManager ? route('inventory.index') : null;
        $borrowedUrl = $user->hasPermission('borrowed_items', 'view') ? route('borrowed-items.index') : $inventoryUrl;

        // Pintasan sesuai izin; aksi utama (Scan Barang) satu-satunya yang penuh warna dan paling kanan.
        $shortcuts = array_filter([
            $user->hasPermission('finance', 'manage') ? ['Catat Transaksi', 'bi-plus-lg', route('finance.transactions.index'), false] : null,
            $isManager && $user->hasPermission('inventory', 'create') ? ['Tambah Barang', 'bi-plus-circle', route('inventory.create'), false] : null,
            $user->hasPermission('tracking_progress', 'create_project') ? ['Project Baru', 'bi-folder-plus', route('projects.create'), false] : null,
            $user->hasPermission('scan_barang', 'view') ? ['Scan Barang', 'bi-upc-scan', route('scan.index'), true] : null,
        ]);

        // Baris Ringkasan di sebelah kartu Keuangan: [label, ikon, nilai, keterangan, url, kelas warna nilai].
        $summaryRows = array_filter([
            $financeSummary ? ['Piutang Client', 'bi-person-lines-fill', $rp($financeSummary['receivables']['total']), $financeSummary['receivables']['count'] . ' belum lunas', route('finance.summary'), 'text-primary'] : null,
            $financeSummary ? ['Hutang Vendor', 'bi-bag-check', $rp($financeSummary['payables']['total']), $financeSummary['payables']['count'] . ' belum dibayar', $user->hasPermission('purchase', 'view') ? route('purchases.index') : route('finance.summary'), 'text-warning-emphasis'] : null,
            $hasPipeline ? ['Project Berjalan', 'bi-kanban', $stageMap->get('In Progress')['count'] ?? 0, $activeProjects . ' project aktif', $pipelineUrl, ''] : null,
            $hasEquipment ? ['Alat di Lapangan', 'bi-box-arrow-up-right', $borrowedCount, 'dari ' . $equipment['total'] . ' unit', $borrowedUrl, ''] : null,
        ]);

        $defaultPeriod = 'month';
        $periodLabels = ['week' => '1 Minggu Terakhir', 'month' => '1 Bulan Terakhir', 'year' => '1 Tahun Terakhir'];
        $fin = $financeSummary['periods'][$defaultPeriod] ?? null;
    @endphp

    <div class="container-fluid p-0">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark m-0">Dashboard</h3>
                <p class="d-none d-md-block text-muted small m-0">Ringkasan operasional dan keuangan.</p>
            </div>
            @if($shortcuts)
                <div class="d-flex flex-wrap gap-2">
                    @foreach($shortcuts as [$label, $icon, $url, $primary])
                        <a href="{{ $url }}" class="btn {{ $primary ? 'btn-primary' : 'btn-outline-primary' }} d-flex align-items-center gap-2 fw-medium"><i class="bi {{ $icon }}"></i> {{ $label }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Perlu perhatian (Super Admin dan Admin): bisa dilipat, terbuka otomatis hanya bila ada yang bernilai; semua 0 = tertutup. --}}
        @if(!empty($attention))
            @php
                $attentionTotal = collect($attention)->sum('count');
                $attentionOpen = $attentionTotal > 0;
            @endphp
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body py-2 px-3">
                    <button type="button" id="attentionToggle" class="btn w-100 d-flex justify-content-between align-items-center gap-3 text-start p-0 border-0 bg-transparent"
                            data-bs-toggle="collapse" data-bs-target="#attentionCollapse" aria-expanded="{{ $attentionOpen ? 'true' : 'false' }}" aria-controls="attentionCollapse">
                        <span class="fw-bold small"><i class="bi bi-exclamation-circle me-2"></i>Perlu perhatian</span>
                        <span class="d-flex align-items-center gap-2 text-nowrap">
                            <span id="attentionSummary" class="small {{ $attentionOpen ? 'd-none' : '' }}">
                                @if($attentionTotal > 0)
                                    <span class="fw-bold text-danger">{{ $attentionTotal }}</span> <span class="text-muted">perlu ditindak</span>
                                @else
                                    <span class="text-muted"><i class="bi bi-check-circle me-1"></i>Semua aman</span>
                                @endif
                            </span>
                            <i class="bi {{ $attentionOpen ? 'bi-chevron-up' : 'bi-chevron-down' }} attention-toggle-icon" aria-hidden="true"></i>
                        </span>
                    </button>

                    {{-- Satu baris: kartu menyusut sama rata (label boleh turun baris), di layar sempit digeser ke samping. --}}
                    <div class="collapse mt-2 {{ $attentionOpen ? 'show' : '' }}" id="attentionCollapse">
                        <div class="d-flex gap-2 overflow-auto pb-1">
                            @foreach($attention as $card)
                                <a href="{{ route($card['route'], $card['params'] ?? []) }}" class="flex-fill border rounded-3 py-2 px-3 d-flex justify-content-between align-items-center gap-2 text-decoration-none text-dark hover-lift">
                                    <span class="small"><i class="bi {{ $card['icon'] }} me-1 {{ $card['count'] > 0 ? 'text-danger' : 'text-muted' }}"></i>{{ $card['label'] }}</span>
                                    <span class="fs-5 fw-bold {{ $card['count'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $card['text'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            @push('scripts')
                <script src="{{ \App\Support\AppAsset::url('js/dashboard/attention.js') }}"></script>
            @endpush
        @endif

        {{-- Keuangan satu kartu dengan pilihan periode, ditemani ringkasan angka penting. --}}
        @if($fin || $summaryRows)
            <div class="row g-3 mb-3">

                @if($fin)
                    <div class="col-12 col-xl-8">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body p-3" id="dashFinance" data-periods="{{ json_encode($financeSummary['periods']) }}">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <h6 class="fw-bold mb-0">Keuangan</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <select id="dashPeriod" class="form-select form-select-sm w-auto" aria-label="Periode keuangan">
                                            @foreach($periodLabels as $key => $label)
                                                <option value="{{ $key }}" @selected($key === $defaultPeriod)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <a href="{{ route('finance.summary') }}" class="small text-decoration-none text-nowrap">Detail</a>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-12 col-sm-4">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="small text-muted mb-1"><i class="bi bi-arrow-down-circle text-success me-1"></i>Pemasukan</div>
                                            <div class="dash-value text-success" data-f="income">{{ $rp($fin['income']) }}</div>
                                            <div class="small" data-f="income-delta"></div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="small text-muted mb-1"><i class="bi bi-arrow-up-circle text-danger me-1"></i>Pengeluaran</div>
                                            <div class="dash-value text-danger" data-f="expense">{{ $rp($fin['expense']) }}</div>
                                            <div class="small" data-f="expense-delta"></div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="rounded-3 p-3 h-100 {{ $fin['net'] >= 0 ? 'bg-success-subtle' : 'bg-danger-subtle' }}" data-f="net-tile">
                                            <div class="small text-muted mb-1"><i class="bi me-1 {{ $fin['net'] >= 0 ? 'bi-graph-up-arrow text-success' : 'bi-graph-down-arrow text-danger' }}" data-f="net-icon"></i><span data-f="net-label">{{ $fin['net'] >= 0 ? 'Laba' : 'Rugi' }}</span></div>
                                            <div class="dash-value {{ $fin['net'] >= 0 ? 'text-success' : 'text-danger' }}" data-f="net">{{ $rp(abs($fin['net'])) }}</div>
                                            <div class="small text-muted" data-f="net-meta"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="u-pos-relative u-h-200px"><canvas id="dashTrendChart" aria-label="Grafik garis pemasukan dan pengeluaran"></canvas></div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($summaryRows)
                    <div class="col-12 {{ $fin ? 'col-xl-4' : '' }}">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body p-3 dash-summary">
                                <h6 class="fw-bold mb-3">Ringkasan</h6>
                                @foreach($summaryRows as [$label, $icon, $value, $note, $url, $tone])
                                    @if($url)
                                        <a href="{{ $url }}" class="dash-summary-row d-flex align-items-center gap-3 py-2 text-decoration-none text-dark {{ $loop->first ? '' : 'border-top' }}">
                                    @else
                                        <div class="dash-summary-row d-flex align-items-center gap-3 py-2 {{ $loop->first ? '' : 'border-top' }}">
                                    @endif
                                        <span class="avatar-initial u-w-30px u-h-30px"><i class="bi {{ $icon }}"></i></span>
                                        <span class="flex-grow-1 min-w-0">
                                            <span class="d-block small fw-semibold">{{ $label }}</span>
                                            <span class="d-block text-muted small">{{ $note }}</span>
                                        </span>
                                        <span class="fw-semibold text-nowrap {{ $tone }}">{{ $value }}</span>
                                    @if($url)
                                        </a>
                                    @else
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        @endif

        {{-- Minggu ini: 7 hari mulai hari ini; pilih hari untuk melihat acaranya. --}}
        @if($week)
            @php $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']; @endphp
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Minggu Ini</h6>
                        <a href="{{ route('projects.index') }}" class="small text-decoration-none">Daftar Project</a>
                    </div>

                    <div class="dash-week" id="dashWeek">
                        <div class="dash-week-strip" role="tablist" aria-label="Pilih hari">
                            @foreach($week['days'] as $day)
                                <button type="button" role="tab" id="dashDayTab{{ $loop->index }}" aria-controls="dashDayPanel{{ $loop->index }}"
                                        class="dash-day {{ $day['is_today'] ? 'is-active' : '' }}"
                                        aria-selected="{{ $day['is_today'] ? 'true' : 'false' }}" tabindex="{{ $day['is_today'] ? '0' : '-1' }}">
                                    <span class="dash-day-name">{{ $day['name'] }}</span>
                                    <span class="dash-day-num">{{ $day['date']->format('j') }}</span>
                                    <span class="dash-day-dot {{ $day['projects']->isNotEmpty() ? 'has-event' : '' }}"></span>
                                </button>
                            @endforeach
                        </div>

                        <div class="dash-week-panels">
                            @foreach($week['days'] as $day)
                                <div role="tabpanel" id="dashDayPanel{{ $loop->index }}" aria-labelledby="dashDayTab{{ $loop->index }}" @if(!$day['is_today']) hidden @endif>
                                    <div class="small text-muted mb-1">{{ $day['name'] }}, {{ $day['date']->format('j') }} {{ $bulan[$day['date']->month - 1] }}{{ $day['is_today'] ? ' - Hari ini' : '' }}</div>

                                    @forelse($day['projects']->take(4) as $project)
                                        <a href="{{ route('projects.show', $project->id) }}" class="dash-event" title="{{ $project->name }}">
                                            <span class="dash-event-dot"></span>
                                            <span class="flex-grow-1 min-w-0">
                                                <span class="dash-event-name">{{ $project->short_name }}</span>
                                                <span class="dash-event-meta">{{ $project->event_time_start ? substr($project->event_time_start, 0, 5) . ' - ' : '' }}{{ $project->client ?: 'Tanpa client' }}</span>
                                            </span>
                                            <span class="dash-event-status">{{ \App\Models\Project::STATUS_LABELS[$project->status] ?? $project->status }}</span>
                                        </a>
                                    @empty
                                        <p class="small text-muted mb-0 pt-2">Tidak ada acara.</p>
                                    @endforelse

                                    @if($day['projects']->count() > 4)
                                        <a href="{{ route('projects.index') }}" class="small fw-semibold text-decoration-none d-inline-block pt-2">+{{ $day['projects']->count() - 4 }} lagi</a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Pipeline project per status dan kondisi unit alat. --}}
        @if($hasPipeline || $hasEquipment)
            <div class="row g-3 mb-3">

                @if($hasPipeline)
                    <div class="col-12 {{ $hasEquipment ? 'col-xl-7' : '' }}">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0">Pipeline Project</h6>
                                    <a href="{{ $pipelineUrl }}" class="small text-decoration-none">{{ $isManager ? 'Buka Pipeline' : 'Daftar Project' }}</a>
                                </div>
                                @php $maxStage = max(1, collect($pipeline)->max('count')); @endphp
                                @foreach($pipeline as $stage)
                                    <a href="{{ route('projects.index', ['status' => $stage['status']]) }}" class="dash-row {{ $financeSummary ? 'has-value' : '' }}">
                                        <span>{{ $stage['label'] }}</span>
                                        <progress class="dash-bar {{ $stageBars[$stage['status']] ?? '' }}" max="100" value="{{ round(($stage['count'] / $maxStage) * 100) }}" aria-label="{{ $stage['label'] }}: {{ $stage['count'] }} project"></progress>
                                        <span class="dash-row-count">{{ $stage['count'] }}</span>
                                        @if($financeSummary)
                                            <span class="dash-row-value">{{ $stage['count'] > 0 ? $compact($stage['value']) : '-' }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if($hasEquipment)
                    <div class="col-12 {{ $hasPipeline ? 'col-xl-5' : '' }}">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0">Kondisi Alat</h6>
                                    @if($inventoryUrl)
                                        <a href="{{ $inventoryUrl }}" class="small text-decoration-none">Inventaris</a>
                                    @endif
                                </div>
                                @foreach($equipment['items'] as $unit)
                                    <div class="dash-row">
                                        <span>{{ $unit['label'] }}</span>
                                        <progress class="dash-bar {{ $unitBars[$unit['label']] ?? '' }}" max="100" value="{{ $equipment['total'] > 0 ? round(($unit['count'] / $equipment['total']) * 100) : 0 }}" aria-label="{{ $unit['label'] }}: {{ $unit['count'] }} unit"></progress>
                                        <span class="dash-row-count">{{ $unit['count'] }}</span>
                                    </div>
                                @endforeach
                                <div class="text-muted small border-top pt-2">{{ $equipment['total'] }} unit tercatat</div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        @endif

        {{-- Task saya dan notifikasi bertumpuk (tinggi mengikuti isi); Surat Jalan terbaru di sebelahnya. --}}
        @php $showSuratJalan = $user->hasPermission('surat_jalan', 'view'); @endphp
        <div class="row g-3 mb-3 align-items-start">

            <div class="col-12 {{ $showSuratJalan ? 'col-lg-5' : '' }}">
                <div class="d-flex flex-column gap-3">

                    @if($hasPipeline)
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body p-3">
                                <h6 class="fw-bold mb-3">Task Saya</h6>
                                @forelse($myTasks as $task)
                                    @php
                                        $deadline = \Carbon\Carbon::parse($task->deadline);
                                        $isOverdue = $deadline->copy()->startOfDay()->lt(now()->startOfDay());
                                    @endphp
                                    <div class="d-flex justify-content-between gap-2 py-2 border-top">
                                        <div class="min-w-0">
                                            <a href="{{ route('projects.show', $task->project_id) }}" class="text-decoration-none text-dark d-block text-truncate">{{ $task->title }}</a>
                                            <div class="text-muted small text-truncate">{{ $task->project->short_name ?? '-' }}</div>
                                        </div>
                                        <span class="{{ $isOverdue ? 'badge-soft-danger' : 'text-muted small' }} text-nowrap align-self-start">{{ $deadline->format('d/m/Y') }}</span>
                                    </div>
                                @empty
                                    <div class="text-muted small">Tidak ada task yang perlu dikerjakan.</div>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Notifikasi Terbaru</h6>
                                <a href="{{ route('announcements.index') }}" class="small text-decoration-none">Lihat Semua</a>
                            </div>
                            @forelse($latestNotifications as $item)
                                <a href="{{ $item['url'] }}" class="d-flex gap-2 py-2 border-top text-decoration-none text-dark">
                                    <i class="bi {{ $item['icon'] }} text-primary"></i>
                                    <div class="min-w-0">
                                        <div class="small fw-semibold text-truncate">{{ $item['title'] }}</div>
                                        <div class="text-muted small text-truncate">{{ $item['message'] }}</div>
                                    </div>
                                </a>
                            @empty
                                <div class="text-muted small">Tidak ada notifikasi baru.</div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>

            @if($showSuratJalan)
                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-3">Surat Jalan Terbaru</h6>
                            @forelse($recentSuratJalan as $suratJalan)
                                <div class="d-flex justify-content-between gap-2 py-2 border-top">
                                    <div class="min-w-0">
                                        <a href="{{ route('surat-jalan.show', $suratJalan->id) }}" class="text-decoration-none text-dark d-block text-truncate">{{ $suratJalan->nomor }}</a>
                                        <div class="text-muted small text-truncate">{{ $suratJalan->referensiLabel() }} &middot; {{ \Carbon\Carbon::parse($suratJalan->tanggal_terbit)->format('d/m/Y') }}</div>
                                    </div>
                                    <span class="{{ $suratJalan->status === 'Selesai' ? 'badge-soft-success' : 'badge-soft-warning' }} text-nowrap align-self-start">{{ $suratJalan->status }}</span>
                                </div>
                            @empty
                                <div class="text-muted small">Belum ada Surat Jalan.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- Aktivitas: Super Admin dan Admin melihat seluruh sistem, role lain hanya miliknya. --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">{{ $isManager ? 'Log Aktivitas Terbaru' : 'Aktivitas Saya Terbaru' }}</h6>
                    @if($isManager)
                        <a href="{{ route('activity-logs.index') }}" class="small text-decoration-none">Lihat Semua</a>
                    @endif
                </div>
                @forelse($recentActivities as $activity)
                    @php $isShortAction = mb_strlen($activity->action) <= 16; @endphp
                    <div class="d-flex align-items-start gap-2 py-2 border-top">
                        <div class="avatar-initial u-w-30px u-h-30px u-fs-p75rem">{{ strtoupper(substr($activity->user->name ?? 'SY', 0, 2)) }}</div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-1">
                                <span class="small fw-semibold">{{ $activity->user->name ?? 'System / Deleted User' }}</span>
                                <span class="badge-soft-secondary">{{ $activity->module }}</span>
                                @if($isShortAction)
                                    @if(in_array($activity->action, ['Delete', 'Logout']))
                                        <span class="badge-soft-danger">{{ $activity->action }}</span>
                                    @elseif(in_array($activity->action, ['Create', 'Login', 'Upload']))
                                        <span class="badge-soft-success">{{ $activity->action }}</span>
                                    @else
                                        <span class="badge-soft-warning">{{ $activity->action }}</span>
                                    @endif
                                @endif
                            </div>
                            @unless($isShortAction)
                                <div class="dash-log-desc small text-muted" title="{{ $activity->action }}">{{ $activity->action }}</div>
                            @endunless
                        </div>
                        <span class="text-muted small text-nowrap">{{ $activity->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                @empty
                    <div class="text-muted small">Belum ada aktivitas.</div>
                @endforelse
            </div>
        </div>

        {{-- Jumlah data tersimpan, tidak menentukan keputusan harian jadi dibuat kecil. --}}
        <div class="d-flex flex-wrap gap-3 small text-muted mb-2">
            @if($user->hasPermission('inventory', 'view'))
                <span>Inventaris <strong class="text-dark">{{ $statistics['total_inventory'] }}</strong></span>
            @endif
            @if($hasPipeline)
                <span>Project <strong class="text-dark">{{ $statistics['total_project'] }}</strong></span>
                <span>Task <strong class="text-dark">{{ $statistics['total_task'] }}</strong></span>
            @endif
            <span>Kontak <strong class="text-dark">{{ $statistics['total_contact'] }}</strong></span>
            @if($user->hasPermission('data_integration', 'view'))
                <span>File <strong class="text-dark">{{ $statistics['total_files'] }}</strong></span>
            @endif
            @if($user->hasPermission('user_management', 'view_user'))
                <span>User <strong class="text-dark">{{ $statistics['total_user'] }}</strong></span>
            @endif
        </div>

    </div>

    {{-- Dimuat lewat stack agar berjalan setelah Bootstrap JS. --}}
    @if($financeSummary)
        @push('scripts')
            {{ \App\Support\VendorAsset::script('chartjs') }}
            <script src="{{ \App\Support\AppAsset::url('js/dashboard/index.js') }}"></script>
        @endpush
    @endif

    @if($week)
        @push('scripts')
            <script src="{{ \App\Support\AppAsset::url('js/dashboard/week.js') }}"></script>
        @endpush
    @endif

@endsection
