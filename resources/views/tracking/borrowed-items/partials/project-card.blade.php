{{-- Partial: 1 project pada halaman Barang Pinjaman
     Variabel yang dibutuhkan: $project, $units (Collection InventoryUnit milik project ini) --}}

<div class="card border-0 shadow-sm rounded-3 mb-2 project-card" data-project-id="{{ $project->id }}">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">

        {{-- Zona toggle accordion - TERPISAH dari tombol Konfirmasi supaya tidak bentrok --}}
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#borrowedProject{{ $project->id }}">
            <i class="bi bi-chevron-down text-muted"></i>
            <div>
                <div class="fw-bold">{{ $project->name }}</div>
                <div class="small text-muted">
                    {{ $project->category }}
                    &middot; {{ optional($project->event_date)->translatedFormat('d M Y') }}
                    &middot; <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $project->status }}</span>
                    &middot; <span class="borrowed-count-badge">{{ $units->count() }} unit dipinjam</span>
                </div>
            </div>
        </div>

        @if(auth()->user()->hasPermission('borrowed_items', 'process_return'))
            <button type="button" class="btn btn-sm btn-primary btn-konfirmasi flex-shrink-0" data-project-id="{{ $project->id }}">
                Konfirmasi
            </button>
        @endif
    </div>

    <div class="collapse" id="borrowedProject{{ $project->id }}">
        <div class="card-body pt-0">
            <div class="row g-2 unit-cards" data-project-id="{{ $project->id }}">
                @foreach($units as $unit)
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="border rounded-3 p-2 text-center unit-card" data-unit-id="{{ $unit->id }}" data-unit-label="{{ $unit->inventory->name ?? '-' }} #{{ $unit->unit_number }}">
                            <div class="fw-semibold small">{{ $unit->inventory->name ?? '-' }} #{{ $unit->unit_number }}</div>
                            <div class="small unit-status-label">Dipinjam</div>
                            <div class="small text-muted">{{ $unit->suratJalanItem->suratJalan->nomor ?? '-' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
