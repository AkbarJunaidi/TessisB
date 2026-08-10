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

        <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm fw-medium">
            <i class="bi bi-plus-lg"></i> Add New Task
        </a>
    </div>

    <div class="row g-3" style="min-height: 70vh;">

        @php
            // Menyiapkan daftar status resmi sebagai acuan mapping kolom
            $statuses = [
                'Todo'        => ['bg' => 'bg-secondary', 'title' => 'TODO'],
                'In Progress' => ['bg' => 'bg-primary',   'title' => 'IN PROGRESS'],
                'Review'      => ['bg' => 'bg-warning',   'title' => 'REVIEW'],
                'Done'        => ['bg' => 'bg-success',   'title' => 'DONE']
            ];
        @endphp

        @foreach($statuses as $statusKey => $statusConfig)
            @php
                // Mengambil tumpukan task berdasarkan kelompok status, atau array kosong jika tidak ada data
                $currentTasks = isset($groupedTasks[$statusKey]) ? $groupedTasks[$statusKey] : collect();
            @endphp

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card bg-light border-0 shadow-sm h-100 rounded-3">

                    <div class="card-header bg-transparent border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle {{ $statusConfig['bg'] }}" style="width: 10px; height: 10px; display: inline-block;"></span>
                            <h6 class="fw-bold text-dark m-0 text-uppercase" style="letter-spacing: 0.5px;">{{ $statusConfig['title'] }}</h6>
                        </div>
                        <span class="badge bg-white text-dark border rounded-pill px-2.5 py-1 font-monospace fw-bold shadow-sm">
                            {{ $currentTasks->count() }}
                        </span>
                    </div>

                    <div class="card-body p-2 d-flex flex-column gap-2" style="max-height: 65vh; overflow-y: auto;">

                        @forelse($currentTasks as $task)
                            <div class="card border-0 shadow-sm rounded-2 bg-white card-task" onclick="window.location='{{ route('tasks.show', $task->id) }}'" style="cursor: pointer;">
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
                            <div class="text-center py-4 text-muted border border-dashed rounded-3 bg-white bg-opacity-50">
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
@endsection
