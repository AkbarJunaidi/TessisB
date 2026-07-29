@extends('layouts.app')

@section('title', 'Task Detail - ' . $task->title)

@section('content')
<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="badge bg-primary mb-1"><i class="bi bi-folder2-open me-1"></i>{{ $task->project->name }}</span>
            <h3 class="fw-bold text-dark m-0">Task Detail Specification</h3>
        </div>
        <a href="{{ route('projects.show', $task->project_id) }}" class="btn btn-sm btn-outline-secondary fw-medium">
            <i class="bi bi-arrow-left"></i> Kembali ke Board
        </a>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
                <div class="card-body p-4">

                    <h4 class="fw-bold text-dark mb-3">{{ $task->title }}</h4>

                    <div class="p-3 bg-light rounded-3 text-secondary mb-4" style="min-height: 120px; white-space: pre-line;">
                        {{ $task->description ?? 'Tidak ada deskripsi teknis yang ditambahkan pada tugas ini.' }}
                    </div>

                    <div class="border-top pt-4 mt-2">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-chat-left-text me-2 text-primary"></i>Komentar Progress Kerja</h5>

                        <form action="{{ route('tasks.comments.store') }}" method="POST" class="mb-4">
                            @csrf
                            <input type="hidden" name="task_id" value="{{ $task->id }}">

                            <div class="mb-2">
                                <textarea name="comment"
                                          rows="3"
                                          class="form-control @error('comment') is-invalid @enderror"
                                          placeholder="Tuliskan perkembangan pengerjaan atau hambatan teknis tim di sini..."
                                          required>{{ old('comment') }}</textarea>
                                @error('comment')
                                    <div class="invalid-feedback">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary btn-sm px-3 fw-medium shadow-sm">
                                    <i class="bi bi-send me-1"></i> Kirim Catatan
                                </button>
                            </div>
                        </form>

                        <div class="d-flex flex-column gap-3">
                            @forelse($task->comments->sortByDesc('created_at') as $comment)
                                <div class="p-3 rounded-3 bg-light border border-light shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary text-white rounded-circle small d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                {{ strtoupper(substr($comment->user->name, 0, 2)) }}
                                            </div>
                                            <span class="fw-bold text-dark small">{{ $comment->user->name }}</span>
                                            <span class="badge bg-secondary text-white" style="font-size: 0.65rem;">{{ $comment->user->role }}</span>
                                        </div>
                                        <small class="text-muted font-monospace" style="font-size: 0.7rem;">
                                            <i class="bi bi-clock me-1"></i>{{ $comment->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <p class="text-secondary small m-0 text-wrap" style="white-space: pre-line; line-height: 1.5;">
                                        {{ $comment->comment }}
                                    </p>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted bg-light rounded-3 border border-dashed">
                                    <i class="bi bi-chat-square-dots opacity-25 d-block mb-1 fs-4"></i>
                                    <small style="font-size: 0.75rem;">Belum ada riwayat catatan progress pada task ini.</small>
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold text-dark m-0">Atribut Kontrol Kartu</h6>
                </div>
                <div class="card-body p-4">

                    <form action="{{ route('tasks.update-status', $task->id) }}" method="POST" class="mb-4">
                        @csrf
                        @method('PATCH')
                        <label for="status" class="form-label fw-semibold small text-secondary">Update Progress Status</label>
                        <div class="d-flex gap-2">
                            <select name="status" id="status" class="form-select fw-bold text-dark">
                                <option value="Todo" {{ $task->status === 'Todo' ? 'selected' : '' }}>Todo</option>
                                <option value="In Progress" {{ $task->status === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="Review" {{ $task->status === 'Review' ? 'selected' : '' }}>Review</option>
                                <option value="Done" {{ $task->status === 'Done' ? 'selected' : '' }}>Done</option>
                            </select>
                            <button type="submit" class="btn btn-dark fw-medium px-3">Update</button>
                        </div>
                    </form>

                    <div class="mb-3">
                        <small class="text-muted d-block font-monospace mb-1" style="font-size: 0.7rem;">URGENSI PRIORITAS:</small>
                        @if($task->priority === 'High')
                            <span class="badge bg-danger fs-6 px-3 py-1.5 w-100 rounded-2">High Priority</span>
                        @elseif($task->priority === 'Medium')
                            <span class="badge bg-warning text-dark fs-6 px-3 py-1.5 w-100 rounded-2">Medium Priority</span>
                        @else
                            <span class="badge bg-success fs-6 px-3 py-1.5 w-100 rounded-2">Low Priority</span>
                        @endif
                    </div>

                    <div class="mb-3 border-top pt-3">
                        <small class="text-muted d-block font-monospace" style="font-size: 0.7rem;">DIDELEGASIKAN KEPADA:</small>
                        <span class="fw-bold text-dark d-block mt-1">
                            <i class="bi bi-person-circle text-primary me-2"></i>{{ $task->assignee ? $task->assignee->name : 'Unassigned' }}
                        </span>
                    </div>

                    <div class="mb-4 border-top pt-3">
                        <small class="text-muted d-block font-monospace" style="font-size: 0.7rem;">BATAS WAKTU (DEADLINE):</small>
                        <span class="fw-bold text-danger d-block mt-1">
                            <i class="bi bi-calendar-x me-2"></i>{{ $task->deadline }}
                        </span>
                    </div>

                    <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kartu tugas ini dari papan board?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100 fw-medium">
                            <i class="bi bi-trash3-fill me-1"></i> Delete Task Card
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection
