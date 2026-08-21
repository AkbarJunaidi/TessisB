@php
    $deletedAt = \Illuminate\Support\Carbon::parse($item->deleted_at);
@endphp
<tr data-trash-row data-item-id="{{ $item->id }}" data-item-type="{{ $item->type }}">
    <td class="ps-4 py-3 text-secondary fw-medium" data-label="Dihapus Pada">
        <i class="bi bi-calendar-event me-2"></i>{{ $deletedAt->format('d/m/Y H:i') }}
    </td>
    <td data-label="Nama Data">
        <span class="fw-semibold text-dark">{{ $item->name }}</span>
    </td>
    <td data-label="Tipe">
        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-2 py-2 fw-medium" style="font-size: 0.8rem;">
            {{ $item->type_label }}
        </span>
    </td>
    <td data-label="Dihapus Oleh">
        @if($item->deleted_by_name)
            <div class="d-flex align-items-center gap-2">
                <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-semibold" style="width: 28px; height: 28px; font-size: 0.75rem; border: 1px solid #e2e8f0;">
                    {{ strtoupper(substr($item->deleted_by_name, 0, 2)) }}
                </div>
                <span class="fw-semibold">{{ $item->deleted_by_name }}</span>
            </div>
        @else
            <span class="text-muted fst-italic">System / User Dihapus</span>
        @endif
    </td>
    <td class="pe-4 text-center cell-block" data-label="Aksi">
        <div class="d-flex justify-content-center gap-2">
            {{-- Pulihkan --}}
            <button type="button"
                    class="btn btn-sm btn-outline-primary px-2 fw-medium rounded-2 d-flex align-items-center gap-1"
                    data-bs-toggle="modal"
                    data-bs-target="#restoreTrashModal"
                    data-id="{{ $item->id }}"
                    data-type="{{ $item->type }}"
                    data-type-label="{{ $item->type_label }}"
                    data-name="{{ $item->name }}"
                    title="Pulihkan Data">
                <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
            </button>

            {{-- Hapus Permanen: HANYA Super Admin --}}
            @if(auth()->user()->isSuperAdmin())
                <button type="button"
                        class="btn btn-sm btn-outline-danger px-2 fw-medium rounded-2 d-flex align-items-center gap-1"
                        data-bs-toggle="modal"
                        data-bs-target="#forceDeleteTrashModal"
                        data-id="{{ $item->id }}"
                        data-type="{{ $item->type }}"
                        data-type-label="{{ $item->type_label }}"
                        data-name="{{ $item->name }}"
                        title="Hapus Permanen">
                    <i class="bi bi-trash3"></i>
                </button>
            @endif
        </div>
    </td>
</tr>
