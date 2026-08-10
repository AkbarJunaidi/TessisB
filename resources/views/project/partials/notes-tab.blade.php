{{-- Partial: Tab Catatan (Project Notes)
     Variabel yang dibutuhkan saat di-include: $project (dengan relasi notes.user sudah di-load) --}}

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">

        <h6 class="fw-bold mb-3">Catatan</h6>

        <form action="{{ route('projects.notes.store') }}" method="POST" class="mb-4">
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

        @forelse($project->notes as $note)
            <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                <div>
                    <div class="fw-semibold small">{{ $note->user->name }}</div>
                    <div class="small text-muted mb-1">{{ $note->created_at->translatedFormat('d M Y, H:i') }} WIB</div>
                    <div class="small">{{ $note->note }}</div>
                </div>
                @if($note->user_id === auth()->id() || auth()->user()->isSuperAdmin())
                    <form action="{{ route('projects.notes.destroy', $note) }}" method="POST" onsubmit="return confirm('Hapus catatan ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <p class="text-muted small m-0">Belum ada catatan pada project ini.</p>
        @endforelse

    </div>
</div>
