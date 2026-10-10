{{-- Partial: Tab Catatan (Project Notes)
     Variabel yang dibutuhkan saat di-include: $project (dengan relasi notes.user sudah di-load) --}}

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">

        <h6 class="fw-bold mb-3">Catatan</h6>

        <form action="{{ route('projects.notes.store') }}" method="POST" class="mb-4" data-ajax data-ajax-prepend="#projectNotesList" data-ajax-reset>
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">
            <div class="mb-2">
                <textarea name="note" rows="3" class="form-control @error('note') is-invalid @enderror"
                          placeholder="Tulis catatan mengenai project ini...">{{ old('note') }}</textarea>
                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-send me-1"></i> Kirim Catatan
                </button>
            </div>
        </form>

        <hr class="border-light">

        {{-- Daftar catatan; teks kosong tampil lewat CSS (.notes-empty) saat tidak ada .note-row. --}}
        <div id="projectNotesList" class="notes-list">
            @foreach($project->notes as $note)
                @include('project.partials.note-item', ['note' => $note])
            @endforeach
        </div>
        <p class="notes-empty text-muted small m-0">Belum ada catatan pada project ini.</p>

    </div>
</div>
