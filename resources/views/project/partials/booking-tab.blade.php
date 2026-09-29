{{-- Partial: Tab Booking Alat
     Variabel yang dibutuhkan saat di-include: $project (dengan relasi bookings.inventory sudah di-load),
     $bookableInventories --}}

@php
    $canBook = auth()->user()->hasPermission('inventory', 'booking');
@endphp

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h6 class="fw-bold mb-1">Booking Alat</h6>
                <p class="text-muted small mb-0">
                    Rencana pemakaian alat untuk project ini. Sifatnya rencana (soft) - begitu Surat Jalan
                    benar-benar dibuat untuk barang yang sama, booking di bawah otomatis jadi "Terpenuhi".
                </p>
            </div>
        </div>

        @if(!$project->event_date)
            <div class="alert alert-secondary border-0 small mb-3">
                Isi tanggal event project ini dulu supaya sistem bisa cek bentrok jadwal dengan project lain.
            </div>
        @endif

        <div class="table-responsive mb-3">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr class="text-muted small">
                        <th>Barang</th>
                        <th class="text-center">Jumlah</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->bookings as $booking)
                        <tr>
                            <td>{{ $booking->inventory->name ?? '(barang dihapus)' }}</td>
                            <td class="text-center">{{ $booking->qty }}</td>
                            <td>
                                <span class="badge {{ \App\Models\EquipmentBooking::STATUS_BADGES[$booking->status] ?? 'bg-secondary' }}">
                                    {{ $booking->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($booking->isEditable() && $canBook)
                                    <form action="{{ route('projects.bookings.destroy', [$project, $booking]) }}" method="POST"
                                          onsubmit="return confirm('Batalkan booking ini?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Belum ada booking alat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($canBook)
            <form action="{{ route('projects.bookings.store', $project) }}" method="POST" class="row g-2 align-items-end border-top pt-3">
                @csrf
                <div class="col-md-7">
                    <label class="form-label small mb-1">Barang</label>
                    <select name="inventory_id" class="form-select form-select-sm" required>
                        <option value="">- Pilih barang -</option>
                        @foreach($bookableInventories as $inventory)
                            <option value="{{ $inventory->id }}">{{ $inventory->name }} ({{ $inventory->serial_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Jumlah</label>
                    <input type="number" name="qty" class="form-control form-control-sm" min="1" value="1" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-plus-lg"></i> Booking
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
