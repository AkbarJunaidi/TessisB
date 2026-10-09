{{-- Tab Kanban. Variabel: $project, $groupedTasks; list dinamis per project, drag-drop AJAX memindahkan status task. --}}

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">

    <div class="d-flex justify-content-end gap-2 mb-3">
    <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#modalAddList">
        <i class="bi bi-layout-three-columns"></i> Add List
    </button>
    @if(auth()->user()->hasPermission('tracking_progress', 'create_task'))
    <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary btn-sm d-flex align-items-center gap-2 shadow-sm fw-medium">
        <i class="bi bi-plus-lg"></i> Add New Task
    </a>
    @endif
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

@php
    $boardLists = $project->getBoardLists();
@endphp

{{-- Tab pemilih kolom (mobile/tablet <768px) harus DI LUAR .kanban-board (flex/nowrap);
     di dalamnya tab ikut menjadi anak flex dan sejajar satu baris dengan board. --}}
<ul class="nav nav-pills flex-nowrap overflow-auto gap-1 mb-3 d-md-none kanban-tab-selector" role="tablist">
    @foreach($boardLists as $list)
        <li class="nav-item" role="presentation">
            <button type="button"
                    class="nav-link {{ $loop->first ? 'active' : '' }}"
                    data-bs-toggle="pill"
                    data-bs-target="#kanban-col-{{ $loop->index }}"
                    role="tab">
                {{ $list['label'] }}
                <span class="badge bg-white text-dark border rounded-pill ms-1 board-list-count" data-list-count-for="{{ $list['label'] }}">
                    {{ isset($groupedTasks[$list['label']]) ? $groupedTasks[$list['label']]->count() : 0 }}
                </span>
            </button>
        </li>
    @endforeach
</ul>

<div class="row g-3 flex-nowrap overflow-auto pb-2 kanban-board u-minh-60vh">

    <div class="tab-content w-100 d-flex flex-nowrap overflow-auto gap-3">
    @foreach($boardLists as $list)
        @php
            $statusKey = $list['label'];
            $currentTasks = isset($groupedTasks[$statusKey]) ? $groupedTasks[$statusKey] : collect();
        @endphp

        <div class="col-12 tab-pane kanban-col {{ $loop->first ? 'show active' : '' }} u-minw-280px u-maxw-300px" id="kanban-col-{{ $loop->index }}">
            <div class="card bg-light border-0 shadow-sm h-100 rounded-3">

                <div class="card-header bg-transparent border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="rounded-circle bg-{{ $list['color'] }} u-w-10px u-h-10px u-d-inline-block"></span>
                        <h6 class="fw-bold text-dark m-0 text-uppercase u-ls-0p5px">{{ $statusKey }}</h6>
                    </div>
                    <span class="badge bg-white text-dark border rounded-pill px-2 py-1 font-monospace fw-bold shadow-sm board-list-count">
                        {{ $currentTasks->count() }}
                    </span>
                </div>

                <div
                    class="card-body p-2 d-flex flex-column gap-2 board-list-dropzone u-maxh-55vh u-oy-auto u-minh-120px"
                    data-list="{{ $statusKey }}"
                   
                >

                    @forelse($currentTasks as $task)
                        <div
                            class="card border-0 shadow-sm rounded-2 bg-white card-task u-cur-grab"
                            draggable="true"
                            data-task-id="{{ $task->id }}"
                            onclick="if(!window.__wasDragging){ window.location='{{ route('tasks.show', $task->id) }}'; }"
                           
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

                                    <small class="text-muted font-monospace u-fs-0p75rem">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $task->deadline }}
                                    </small>
                                </div>

                                <h6 class="fw-bold text-dark mb-2 text-wrap u-lh-1p4">
                                    {{ $task->title }}
                                </h6>

                                @php
                                    $lastComment = $task->comments ? $task->comments->sortByDesc('created_at')->first() : null;
                                @endphp
                                @if($lastComment)
                                    <div class="bg-light p-2 rounded small text-secondary my-2 border-start border-primary border-3 u-fs-0p75rem">
                                        <i class="bi bi-chat-text text-primary me-1"></i>
                                        <strong class="text-dark">{{ $lastComment->user->name }}:</strong>
                                        <span class="fst-italic">"{{ Str::limit($lastComment->comment, 40) }}"</span>
                                    </div>
                                @endif

                                <div class="d-flex align-items-center justify-content-between pt-2 border-top border-light mt-3">
                                    <div class="d-flex align-items-center gap-1.5 text-secondary">
                                        <i class="bi bi-person-circle text-primary u-fs-0p9rem"></i>
                                        <span class="small fw-medium u-fs-0p8rem">
                                            {{ $task->assignee ? $task->assignee->name : 'Unassigned' }}
                                        </span>
                                    </div>

                                    <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-link p-0 text-decoration-none fw-bold u-fs-0p8rem" onclick="event.stopPropagation();">
                                        Detail <i class="bi bi-chevron-right small"></i>
                                    </a>
                                </div>

                                {{-- Dropdown 'Pindahkan ke...' pengganti drag-drop di layar sentuh; endpoint sama dengan desktop. --}}
                                <div class="dropdown mt-2 d-md-none" onclick="event.stopPropagation();">
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-arrow-left-right me-1"></i> Pindahkan ke...
                                    </button>
                                    <ul class="dropdown-menu w-100">
                                        @foreach($boardLists as $targetList)
                                            @if($targetList['label'] !== $statusKey)
                                                <li>
                                                    <button type="button" class="dropdown-item kanban-move-btn" data-target-list="{{ $targetList['label'] }}">
                                                        {{ $targetList['label'] }}
                                                    </button>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>

                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted border border-dashed rounded-3 bg-white bg-opacity-50 board-empty-placeholder">
                            <i class="bi bi-inbox opacity-25 d-block mb-1 fs-4"></i>
                            <small class="u-fs-0p75rem">Belum ada task di list ini.</small>
                        </div>
                    @endforelse

                </div>

            </div>
        </div>
    @endforeach
    </div>

</div>

    </div>
</div>

<script>
(function () {
    // Template URL update-status: ganti __ID__ dengan id task saat dipakai.
    const updateUrlTemplate = @json(route('tasks.update-status', ['task' => '__ID__']));
    const csrfToken = @json(csrf_token());
    let draggedCard = null;

    document.querySelectorAll('#tab-kanban .card-task').forEach((card) => {

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
            setTimeout(() => { window.__wasDragging = false; }, 50);
        });

    });

    document.querySelectorAll('#tab-kanban .board-list-dropzone').forEach((zone) => {

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

            moveTask(draggedCard, this.dataset.list);
            draggedCard = null;
        });

    });

    // Dropdown 'Pindahkan ke...' pengganti drag-drop di layar sentuh; memakai moveTask() yang sama.
    document.querySelectorAll('#tab-kanban .kanban-move-btn').forEach((btn) => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const card = this.closest('.card-task');
            if (!card) return;
            moveTask(card, this.dataset.targetList);
        });
    });

    // Ubah status task lewat PATCH; kartu baru pindah di UI setelah server sukses.
    function moveTask(cardEl, targetList) {
        const originZone = cardEl.closest('.board-list-dropzone');
        if (!originZone || originZone.dataset.list === targetList) return;

        const targetZone = document.querySelector('#tab-kanban .board-list-dropzone[data-list="' + CSS.escape(targetList) + '"]');
        if (!targetZone) return;

        const taskId = cardEl.dataset.taskId;
        const url = updateUrlTemplate.replace('__ID__', taskId);

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
                const emptyPlaceholder = targetZone.querySelector('.board-empty-placeholder');
                if (emptyPlaceholder) emptyPlaceholder.remove();

                targetZone.appendChild(cardEl);
                updateColumnCount(targetZone);
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
                AppUI.toast('Gagal memindahkan task. Silakan coba lagi.');
            });
    }

    // Perbarui jumlah task pada header kolom.
    function updateColumnCount(zone) {
        const count = zone.querySelectorAll('.card-task').length;
        const listLabel = zone.dataset.list;

        // Badge jumlah di header kolom (desktop dan mobile).
        const headerBadge = zone.closest('.card').querySelector('.board-list-count');
        if (headerBadge) headerBadge.textContent = count;

        // Badge jumlah di tab pemilih kolom (mobile); ikut diperbarui saat task dipindah.
        const tabBadge = document.querySelector('#tab-kanban [data-list-count-for="' + CSS.escape(listLabel) + '"]');
        if (tabBadge) tabBadge.textContent = count;
    }
})();
</script>
