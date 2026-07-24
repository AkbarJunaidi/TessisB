@extends('layouts.app')

@section('title', 'Project Board - ' . $project->name)

@section('content')
<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="text-muted small fw-semibold text-uppercase mb-1">
                <i class="bi bi-kanban me-1 text-primary"></i> Tracking Progress Board
            </div>
            <h3 class="fw-bold text-dark m-0">{{ $project->name }}</h3>
            <p class="text-muted small m-0 mt-1">Project Deadline: <strong class="text-danger font-monospace">{{ $project->deadline }}</strong></p>
        </div>

        <div class="d-flex gap-2">
            <button
                type="button"
                class="btn btn-outline-primary d-flex align-items-center gap-2 fw-medium"
                data-bs-toggle="modal"
                data-bs-target="#modalAddList"
            >
                <i class="bi bi-layout-three-columns"></i> Add List
            </button>

            <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm fw-medium">
                <i class="bi bi-plus-lg"></i> Add New Task
            </a>
        </div>
    </div>

    <div class="modal fade" id="modalAddList" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('projects.lists.store', $project->id) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah List Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="label" class="form-label fw-semibold small text-secondary">Nama List</label>
                    <input type="text" name="label" id="label" class="form-control" placeholder="Contoh: Testing" maxlength="50" required autofocus>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan List</button>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3 flex-nowrap overflow-auto pb-2" style="min-height: 70vh;">

        @php
            // Daftar list/kolom board diambil dari project (custom atau default)
            $boardLists = $project->getBoardLists();
        @endphp

        @foreach($boardLists as $list)
            @php
                $statusKey = $list['label'];
                $currentTasks = isset($groupedTasks[$statusKey]) ? $groupedTasks[$statusKey] : collect();
            @endphp

            <div class="col-12" style="min-width: 280px; max-width: 300px;">
                <div class="card bg-light border-0 shadow-sm h-100 rounded-3">

                    <div class="card-header bg-transparent border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle bg-{{ $list['color'] }}" style="width: 10px; height: 10px; display: inline-block;"></span>
                            <h6 class="fw-bold text-dark m-0 text-uppercase" style="letter-spacing: 0.5px;">{{ $statusKey }}</h6>
                        </div>
                        <span class="badge bg-white text-dark border rounded-pill px-2 py-1 font-monospace fw-bold shadow-sm board-list-count">
                            {{ $currentTasks->count() }}
                        </span>
                    </div>

                    <div
                        class="card-body p-2 d-flex flex-column gap-2 board-list-dropzone"
                        data-list="{{ $statusKey }}"
                        style="max-height: 65vh; overflow-y: auto; min-height: 120px;"
                    >

                        @forelse($currentTasks as $task)
                            <div
                                class="card border-0 shadow-sm rounded-2 bg-white card-task"
                                draggable="true"
                                data-task-id="{{ $task->id }}"
                                onclick="if(!window.__wasDragging){ window.location='{{ route('tasks.show', $task->id) }}'; }"
                                style="cursor: grab;"
                            >
                                <div class="card-body p-3">

                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        @if($task->priority === 'High')
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1 rounded">High</span>
                                        @elseif($task->priority === 'Medium')
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 rounded">Medium</span>
                                        @else
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded">Low</span>
                                        @endif

                                        <small class="text-muted font-monospace" style="font-size: 0.75rem;">
                                            <i class="bi bi-calendar3 me-1"></i>{{ $task->deadline }}
                                        </small>
                                    </div>

                                    <h6 class="fw-bold text-dark mb-2 text-wrap" style="line-height: 1.4;">
                                        {{ $task->title }}
                                    </h6>

                                    @php
                                        // Mengambil komentar terbaru (urutan teratas) dari relasi database
                                        $lastComment = $task->comments ? $task->comments->sortByDesc('created_at')->first() : null;
                                    @endphp
                                    @if($lastComment)
                                        <div class="bg-light p-2 rounded small text-secondary my-2 border-start border-primary border-3" style="font-size: 0.75rem;">
                                            <i class="bi bi-chat-text text-primary me-1"></i>
                                            <strong class="text-dark">{{ $lastComment->user->name }}:</strong>
                                            <span class="fst-italic">"{{ Str::limit($lastComment->comment, 40) }}"</span>
                                        </div>
                                    @endif

                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top border-light mt-3">
                                        <div class="d-flex align-items-center gap-1.5 text-secondary">
                                            <i class="bi bi-person-circle text-primary" style="font-size: 0.9rem;"></i>
                                            <span class="small fw-medium" style="font-size: 0.8rem;">
                                                {{ $task->assignee ? $task->assignee->name : 'Unassigned' }}
                                            </span>
                                        </div>

                                        <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-link p-0 text-decoration-none fw-bold" style="font-size: 0.8rem;" onclick="event.stopPropagation();">
                                            Detail <i class="bi bi-chevron-right small"></i>
                                        </a>
                                    </div>

                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted border border-dashed rounded-3 bg-white bg-opacity-50 board-empty-placeholder">
                                <i class="bi bi-inbox opacity-25 d-block mb-1 fs-4"></i>
                                <small style="font-size: 0.75rem;">Belum ada task di list ini.</small>
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
    // Template URL update-status: ganti __ID__ dengan id task saat dipakai.
    const updateUrlTemplate = @json(route('tasks.update-status', ['task' => '__ID__']));
    const csrfToken = @json(csrf_token());
    let draggedCard = null;

    document.querySelectorAll('.card-task').forEach((card) => {

        card.addEventListener('dragstart', function () {
            draggedCard = this;
            window.__wasDragging = false;
            setTimeout(() => this.classList.add('opacity-50'), 0);
        });

        card.addEventListener('drag', function () {
            window.__wasDragging = true;
        });

        card.addEventListener('dragend', function () {
            this.classList.remove('opacity-50');
            // Delay kecil supaya event click (buka detail task) tidak
            // ikut terpicu tepat setelah selesai drag.
            setTimeout(() => { window.__wasDragging = false; }, 50);
        });

    });

    document.querySelectorAll('.board-list-dropzone').forEach((zone) => {

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

            const originZone = draggedCard.closest('.board-list-dropzone');
            if (originZone === this) return;

            const taskId = draggedCard.dataset.taskId;
            const targetList = this.dataset.list;
            const url = updateUrlTemplate.replace('__ID__', taskId);
            const movedCard = draggedCard;

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ status: targetList }),
            })
                .then((res) => {
                    if (!res.ok) throw new Error('Gagal memperbarui status task.');
                    return res.json();
                })
                .then(() => {
                    // Pindahkan kartu ke kolom baru & sesuaikan penghitung
                    const emptyPlaceholder = this.querySelector('.board-empty-placeholder');
                    if (emptyPlaceholder) emptyPlaceholder.remove();

                    this.appendChild(movedCard);
                    updateColumnCount(this);
                    updateColumnCount(originZone);

                    if (!originZone.querySelector('.card-task')) {
                        originZone.insertAdjacentHTML('beforeend', `
                            <div class="text-center py-4 text-muted border border-dashed rounded-3 bg-white bg-opacity-50 board-empty-placeholder">
                                <i class="bi bi-inbox opacity-25 d-block mb-1 fs-4"></i>
                                <small style="font-size: 0.75rem;">Belum ada task di list ini.</small>
                            </div>
                        `);
                    }
                })
                .catch(() => {
                    alert('Gagal memindahkan task. Silakan coba lagi.');
                })
                .finally(() => {
                    draggedCard = null;
                });
        });

    });

    function updateColumnCount(zone) {
        const count = zone.querySelectorAll('.card-task').length;
        const badge = zone.closest('.card').querySelector('.board-list-count');
        if (badge) badge.textContent = count;
    }
})();
</script>
@endsection
