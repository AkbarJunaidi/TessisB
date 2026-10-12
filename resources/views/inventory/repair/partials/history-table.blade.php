{{-- Riwayat servis: 1 baris = 1 unit yang pernah dikirim ke tempat servis. --}}
<div class="table-responsive">
    <table class="table table-hover table-modern table-stack align-middle mb-0">
        <thead>
            <tr>
                <th class="ps-4 py-3">Barang</th>
                <th class="py-3">Unit</th>
                <th class="py-3">Kode</th>
                <th class="py-3">Tempat Servis</th>
                <th class="py-3">Masuk</th>
                <th class="py-3">Selesai</th>
                <th class="py-3">Keluhan</th>
                <th class="py-3">Hasil</th>
                <th class="py-3 text-end pe-4">Biaya</th>
            </tr>
        </thead>
        <tbody class="small">
            @forelse($history as $item)
                @php $repair = $item->repair; @endphp
                <tr>
                    <td class="ps-4 py-3 u-minw-160px" data-label="Barang">
                        @if($item->inventory)
                            <a href="{{ route('inventory.show', $item->inventory) }}" class="text-decoration-none fw-semibold">{{ $item->inventory->name }}</a>
                        @else
                            -
                        @endif
                    </td>
                    <td data-label="Unit">#{{ $item->unit->unit_number ?? '?' }}</td>
                    <td data-label="Kode">
                        <a href="{{ route('inventory.repairs.show', $repair) }}" class="text-decoration-none">{{ $repair->code }}</a>
                    </td>
                    <td class="u-minw-160px" data-label="Tempat Servis">{{ $repair->tempat_nama }}</td>
                    <td data-label="Masuk">{{ $repair->tanggal_masuk->format('d M Y') }}</td>
                    <td data-label="Selesai">{{ $repair->tanggal_selesai?->format('d M Y') ?? '-' }}</td>
                    <td class="u-minw-180px" data-label="Keluhan">{{ $repair->keluhan ? \Illuminate\Support\Str::limit($repair->keluhan, 60) : '-' }}</td>
                    <td data-label="Hasil">
                        @if($item->hasil === 'Tersedia')
                            <span class="badge-soft-success px-2 py-1 rounded-pill text-nowrap">Kembali tersedia</span>
                        @elseif($item->hasil === 'Rusak')
                            <span class="badge-soft-danger px-2 py-1 rounded-pill text-nowrap">Rusak</span>
                        @else
                            <span class="badge-soft-warning px-2 py-1 rounded-pill text-nowrap">Sedang diservis</span>
                        @endif
                    </td>
                    <td class="text-end pe-4" data-label="Biaya">
                        @if($item->biaya !== null)
                            {{ \App\Support\Money::formatRupiah($item->biaya) }}
                            @if($item->biaya_estimasi)
                                <div class="text-muted small">perkiraan</div>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-tools fs-3 d-block mb-2 opacity-50"></i>
                        Belum ada riwayat servis yang cocok.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
