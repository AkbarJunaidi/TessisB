{{-- Satu baris catatan project. Variabel: $note (relasi user sudah di-load). Dipakai tab Catatan dan respons AJAX. --}}
<div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom note-row">
    <div>
        <div class="fw-semibold small">{{ $note->user->name }}</div>
        <div class="small text-muted mb-1">{{ $note->created_at->translatedFormat('d M Y, H:i') }} WIB</div>
        <div class="small">{{ $note->note }}</div>
    </div>
    @if($note->user_id === auth()->id() || auth()->user()->isSuperAdmin())
        <form action="{{ route('projects.notes.destroy', $note) }}" method="POST" data-ajax data-ajax-remove-closest=".note-row" data-confirm="Hapus catatan ini?" data-confirm-label="Hapus" data-confirm-danger>
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    @endif
</div>
