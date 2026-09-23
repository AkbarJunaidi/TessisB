{{-- Partial: 1 akun (peminjaman langsung lewat Scan, TANPA Project) pada
     halaman Barang Pinjaman - kembaran project-card.blade.php, struktur &
     class SAMA PERSIS (termasuk class "project-card" - dipakai query umum
     "kartu apa saja yang masih ada" di JS) supaya JS yang sudah ada jalan
     tanpa perlu tahu bedanya. Variabel: $user, $units --}}

<div class="card border-0 shadow-sm rounded-3 mb-2 project-card" data-user-id="{{ $user->id }}">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">

        <div class="d-flex align-items-center gap-2 flex-grow-1" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#borrowedUser{{ $user->id }}">
            <i class="bi bi-chevron-down text-muted"></i>
            <div>
                <div class="fw-bold">Dipinjam oleh: {{ $user->name }}</div>
                <div class="small text-muted">
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Peminjaman Langsung</span>
                    &middot; <span class="borrowed-count-badge">{{ $units->count() }} unit dipinjam</span>
                </div>
            </div>
        </div>

        @if(auth()->user()->hasPermission('borrowed_items', 'process_return'))
            <button type="button" class="btn btn-sm btn-primary btn-konfirmasi flex-shrink-0" data-user-id="{{ $user->id }}">
                Konfirmasi
            </button>
        @endif
    </div>

    <div class="collapse" id="borrowedUser{{ $user->id }}">
        <div class="card-body pt-0">
            <div class="row g-2 unit-cards" data-user-id="{{ $user->id }}">
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
