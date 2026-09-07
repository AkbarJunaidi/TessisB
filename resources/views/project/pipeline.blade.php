@extends('layouts.app')

@section('title', 'Pipeline Project')

@section('content')
<div class="container-fluid px-4 py-3">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Pipeline Project</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-decoration-none">Project Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pipeline</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 fw-medium">
            <i class="bi bi-list-ul"></i> Lihat Daftar
        </a>
    </div>

    <div id="pipelineAlertPlaceholder"></div>

    <div class="row g-3 flex-nowrap overflow-auto pb-2" id="pipelineBoard" style="min-height: 65vh;">

        @foreach($board as $status => $data)
            @php
                $color = \App\Models\Project::PIPELINE_STATUS_COLORS[$status] ?? 'secondary';
                $statusLabel = \App\Models\Project::STATUS_LABELS[$status] ?? $status;
            @endphp

            <div class="col-12" style="min-width: 280px; max-width: 300px;">
                <div class="card bg-light border-0 shadow-sm h-100 rounded-3">

                    <div class="card-header bg-transparent border-0 pt-3 pb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle bg-{{ $color }}" style="width: 10px; height: 10px; display: inline-block;"></span>
                                <h6 class="fw-bold text-dark m-0 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">{{ $statusLabel }}</h6>
                            </div>
                            <span class="badge bg-white text-dark border rounded-pill px-2 py-1 font-monospace fw-bold shadow-sm pipeline-col-count">
                                {{ $data['projects']->count() }}
                            </span>
                        </div>
                        <small class="text-muted d-block pipeline-col-value">
                            <i class="bi bi-cash-coin me-1"></i>{{ \App\Support\Money::formatRupiah($data['total_value']) }}
                        </small>
                    </div>

                    <div
                        class="card-body p-2 d-flex flex-column gap-2 pipeline-dropzone"
                        data-status="{{ $status }}"
                        style="max-height: 60vh; overflow-y: auto; min-height: 120px;"
                    >

                        @forelse($data['projects'] as $project)
                            <div
                                class="card border-0 shadow-sm rounded-2 bg-white card-pipeline"
                                draggable="true"
                                data-project-id="{{ $project->id }}"
                                data-value="{{ (float) ($project->estimated_value ?? 0) }}"
                                onclick="if(!window.__pipelineWasDragging){ window.location='{{ route('projects.show', $project->id) }}'; }"
                                style="cursor: grab;"
                            >
                                <div class="card-body p-3">

                                    <h6 class="fw-bold text-dark mb-2 text-wrap" style="line-height: 1.4; font-size: 0.9rem;">
                                        {{ $project->name }}
                                    </h6>

                                    <div class="d-flex align-items-center gap-2 text-secondary small mb-1">
                                        <i class="bi bi-building"></i>
                                        <span class="text-truncate">{{ $project->client ?? '-' }}</span>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 text-secondary small mb-1">
                                        <i class="bi bi-person-circle"></i>
                                        <span class="text-truncate">{{ $project->pic ?? '-' }}</span>
                                    </div>

                                    @if($project->event_date)
                                        <div class="d-flex align-items-center gap-2 text-secondary small mb-2">
                                            <i class="bi bi-calendar-event"></i>
                                            <span>{{ $project->event_date->format('d/m/Y') }}</span>
                                        </div>
                                    @endif

                                    @if($project->estimated_value)
                                        <div class="d-flex align-items-center gap-2 pt-2 border-top border-light fw-semibold" style="font-size: 0.8rem;">
                                            <i class="bi bi-cash-coin text-success"></i>
                                            <span>{{ \App\Support\Money::formatRupiah($project->estimated_value) }}</span>
                                        </div>
                                    @endif

                                    {{-- Pengganti drag & drop di mobile (drag bawaan browser tidak
                                         berfungsi di layar sentuh) - dropdown "Pindahkan ke...", pakai
                                         endpoint yang SAMA (projects.update-status) dengan drag-drop
                                         di desktop, tidak ada perubahan backend. --}}
                                    <div class="dropdown mt-2 d-md-none" onclick="event.stopPropagation();">
                                        <button type="button" class="btn btn-sm btn-outline-secondary w-100 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-arrow-left-right me-1"></i> Pindahkan ke...
                                        </button>
                                        <ul class="dropdown-menu w-100">
                                            @foreach(\App\Models\Project::STATUS_LABELS as $targetStatus => $targetLabel)
                                                @if($targetStatus !== $status)
                                                    <li>
                                                        <button type="button" class="dropdown-item pipeline-move-btn" data-target-status="{{ $targetStatus }}">
                                                            {{ $targetLabel }}
                                                        </button>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>

                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted border border-dashed rounded-3 bg-white bg-opacity-50 pipeline-empty-placeholder">
                                <i class="bi bi-inbox opacity-25 d-block mb-1 fs-4"></i>
                                <small style="font-size: 0.75rem;">Belum ada project di tahap ini.</small>
                            </div>
                        @endforelse

                    </div>

                </div>
            </div>
        @endforeach

    </div>

</div>

<script>
(function () {
    // Template URL update-status: ganti __ID__ dengan id project saat dipakai.
    const updateUrlTemplate = @json(route('projects.update-status', ['project' => '__ID__']));
    const csrfToken = @json(csrf_token());
    const alertPlaceholder = document.getElementById('pipelineAlertPlaceholder');
    let draggedCard = null;

    function showAlert(type, message) {
        const alertEl = document.createElement('div');
        alertEl.className = `alert alert-${type} alert-dismissible fade show mb-4`;
        alertEl.role = 'alert';
        alertEl.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        alertPlaceholder.innerHTML = '';
        alertPlaceholder.appendChild(alertEl);
    }

    document.querySelectorAll('#pipelineBoard .card-pipeline').forEach((card) => {

        card.addEventListener('dragstart', function () {
            draggedCard = this;
            window.__pipelineWasDragging = false;
            setTimeout(() => this.classList.add('opacity-50'), 0);
        });

        card.addEventListener('drag', function () {
            window.__pipelineWasDragging = true;
        });

        card.addEventListener('dragend', function () {
            this.classList.remove('opacity-50');
            setTimeout(() => { window.__pipelineWasDragging = false; }, 50);
        });

    });

    document.querySelectorAll('#pipelineBoard .pipeline-dropzone').forEach((zone) => {

        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            this.classList.add('bg-white', 'border', 'border-primary');
        });

        zone.addEventListener('dragleave', function () {
            this.classList.remove('bg-white', 'border', 'border-primary');
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.remove('bg-white', 'border', 'border-primary');

            if (!draggedCard) return;

            const originZone = draggedCard.closest('.pipeline-dropzone');
            if (originZone === this) return;

            moveProject(draggedCard, this.dataset.status);
            draggedCard = null;
        });

    });

    // Tombol "Pindahkan ke..." (dropdown) - pengganti drag & drop khusus
    // mobile, karena drag bawaan browser tidak berfungsi di layar sentuh.
    // Pakai fungsi moveProject() yang SAMA dengan drag-drop desktop di atas.
    document.querySelectorAll('#pipelineBoard .pipeline-move-btn').forEach((btn) => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const card = this.closest('.card-pipeline');
            if (!card) return;
            moveProject(card, this.dataset.targetStatus);
        });
    });

    function moveProject(cardEl, targetStatus) {
        const originZone = cardEl.closest('.pipeline-dropzone');
        if (!originZone || originZone.dataset.status === targetStatus) return;

        const targetZone = document.querySelector('#pipelineBoard .pipeline-dropzone[data-status="' + CSS.escape(targetStatus) + '"]');
        if (!targetZone) return;

        const projectId = cardEl.dataset.projectId;
        const url = updateUrlTemplate.replace('__ID__', projectId);

        fetch(url, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ status: targetStatus }),
        })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Gagal memindahkan project.');
                return data;
            })
            .then(() => {
                const emptyPlaceholder = targetZone.querySelector('.pipeline-empty-placeholder');
                if (emptyPlaceholder) emptyPlaceholder.remove();

                targetZone.appendChild(cardEl);
                updateColumnStats(targetZone);
                updateColumnStats(originZone);

                if (!originZone.querySelector('.card-pipeline')) {
                    originZone.insertAdjacentHTML('beforeend', `
                        <div class="text-center py-4 text-muted border border-dashed rounded-3 bg-white bg-opacity-50 pipeline-empty-placeholder">
                            <i class="bi bi-inbox opacity-25 d-block mb-1 fs-4"></i>
                            <small style="font-size: 0.75rem;">Belum ada project di tahap ini.</small>
                        </div>`);
                }
            })
            .catch((err) => {
                showAlert('danger', err.message);
            });
    }

    function updateColumnStats(zone) {
        const cards = zone.querySelectorAll('.card-pipeline');
        const header = zone.closest('.card').querySelector('.card-header');

        const countBadge = header.querySelector('.pipeline-col-count');
        if (countBadge) countBadge.textContent = cards.length;

        let total = 0;
        cards.forEach((c) => { total += parseFloat(c.dataset.value || '0'); });

        const valueEl = header.querySelector('.pipeline-col-value');
        if (valueEl) {
            const formatted = new Intl.NumberFormat('id-ID').format(Math.round(total));
            valueEl.innerHTML = `<i class="bi bi-cash-coin me-1"></i>Rp ${formatted}`;
        }
    }
})();
</script>
@endsection
