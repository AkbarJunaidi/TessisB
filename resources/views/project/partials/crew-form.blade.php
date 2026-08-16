{{-- Partial: Crew / Tim Project
     Variabel yang dibutuhkan saat di-include: $project
     Crew berupa nama teks bebas - TIDAK perlu punya akun user. --}}

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold m-0">Crew / Tim Project</h6>
            @if(auth()->user()->hasPermission('tracking_progress', 'edit_project'))
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#crewModal">
                    Kelola Crew
                </button>
            @endif
        </div>

        @forelse($project->crews as $crew)
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center"
                     style="width:32px;height:32px;font-size:.8rem;">
                    {{ strtoupper(substr($crew->name, 0, 1)) }}
                </div>
                <div>
                    <div class="small fw-semibold">{{ $crew->role_label }}</div>
                    <div class="small text-muted">{{ $crew->name }}</div>
                </div>
            </div>
        @empty
            <p class="text-muted small m-0">Belum ada crew yang ditugaskan pada project ini.</p>
        @endforelse
    </div>
</div>

@if(auth()->user()->hasPermission('tracking_progress', 'edit_project'))
<div class="modal fade" id="crewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('projects.crew.update', $project) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Kelola Crew / Tim Project</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="crewRows">
                        @forelse($project->crews as $index => $crew)
                            <div class="row g-2 mb-2 crew-row">
                                <div class="col-6">
                                    <input type="text" name="crew[{{ $index }}][name]" class="form-control form-control-sm"
                                           placeholder="Nama crew" value="{{ $crew->name }}" required>
                                </div>
                                <div class="col-5">
                                    <input type="text" name="crew[{{ $index }}][role_label]" class="form-control form-control-sm"
                                           placeholder="Role (contoh: Videographer)" value="{{ $crew->role_label }}" required>
                                </div>
                                <div class="col-1">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-crew-row"><i class="bi bi-x"></i></button>
                                </div>
                            </div>
                        @empty
                        @endforelse
                    </div>
                    <button type="button" id="addCrewRow" class="btn btn-sm btn-light w-100 mt-1">
                        <i class="bi bi-plus-lg"></i> Tambah Crew
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Crew</button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="crewRowTemplate">
    <div class="row g-2 mb-2 crew-row">
        <div class="col-6">
            <input type="text" name="crew[__INDEX__][name]" class="form-control form-control-sm"
                   placeholder="Nama crew" required>
        </div>
        <div class="col-5">
            <input type="text" name="crew[__INDEX__][role_label]" class="form-control form-control-sm"
                   placeholder="Role (contoh: Videographer)" required>
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-sm btn-outline-danger remove-crew-row"><i class="bi bi-x"></i></button>
        </div>
    </div>
</template>

<script>
(function () {
    let crewIndex = {{ $project->crews->count() }};
    const rowsWrapper = document.getElementById('crewRows');
    const template = document.getElementById('crewRowTemplate');

    document.getElementById('addCrewRow')?.addEventListener('click', function () {
        const html = template.innerHTML.replaceAll('__INDEX__', crewIndex);
        rowsWrapper.insertAdjacentHTML('beforeend', html);
        crewIndex++;
    });

    rowsWrapper?.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-crew-row');
        if (btn) {
            btn.closest('.crew-row').remove();
        }
    });
})();
</script>
@endif
