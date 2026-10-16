{{-- Tab Kondisi barang: satu baris per barang, rincian per surat dimuat saat baris dibuka. --}}
@php
    $flagLabel = ['rawan' => 'Rawan rusak', 'sulit' => 'Sulit diperbaiki', 'mahal' => 'Mahal diperbaiki'];
    $fmtMoney  = fn ($v) => \App\Support\Money::formatRupiah($v);
@endphp
<div class="table-responsive">
    <table class="table table-hover table-modern table-stack align-middle mb-0">
        <thead>
            <tr>
                <th class="ps-4 py-3 u-w-22pct">Barang</th>
                <th class="text-end u-w-7pct">Unit</th>
                <th class="text-end u-w-8pct" title="Unit yang dikirim servis dan sudah selesai; unit yang sama dihitung lagi tiap kali dikirim">Diservis</th>
                <th class="text-end u-w-8pct">Berhasil</th>
                <th class="text-end u-w-7pct">Gagal</th>
                <th class="text-end u-w-9pct">%&nbsp;Berhasil</th>
                <th class="text-end u-w-8pct">%&nbsp;Gagal</th>
                <th class="u-w-12pct">Terakhir</th>
                <th class="u-w-11pct">Penanda</th>
                <th class="pe-4 text-center u-w-8pct">Rincian</th>
            </tr>
        </thead>
        <tbody class="small">
            @forelse($conditions as $row)
                @php $inventory = $row->inventory; @endphp
                <tr>
                    <td class="ps-4 py-3 u-minw-200px" data-label="Barang">
                        <a href="{{ route('inventory.show', $inventory) }}" class="text-decoration-none fw-semibold">{{ $inventory->name }}</a>
                        <div class="text-muted small">{{ $inventory->brand ?: '' }} SN {{ $inventory->serial_number }}</div>
                    </td>
                    <td class="text-end text-nowrap" data-label="Unit">{{ $row->units }}</td>
                    <td class="text-end text-nowrap" data-label="Diservis">
                        {{ $row->diservis }}
                        @if($row->aktif > 0)
                            <div class="text-muted small">+{{ $row->aktif }} sedang diservis</div>
                        @endif
                    </td>
                    <td class="text-end text-nowrap" data-label="Berhasil">{{ $row->berhasil }}</td>
                    <td class="{{ $row->gagal > 0 ? 'text-danger fw-semibold' : '' }} text-end text-nowrap" data-label="Gagal">{{ $row->gagal }}</td>
                    <td class="text-end text-nowrap" data-label="% Berhasil">{{ $row->persen_berhasil !== null ? $row->persen_berhasil . '%' : '-' }}</td>
                    <td class="text-end text-nowrap" data-label="% Gagal">{{ $row->persen_gagal !== null ? $row->persen_gagal . '%' : '-' }}</td>
                    <td class="text-nowrap" data-label="Terakhir">{{ $row->terakhir ? $row->terakhir->translatedFormat('d M Y') : '-' }}</td>
                    <td class="" data-label="Penanda">
                        @if($row->diservis === 0)
                            <span class="text-muted">-</span>
                        @elseif($row->flags === [])
                            <span class="badge-soft-success px-2 py-1 rounded-pill text-nowrap">Aman</span>
                        @else
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($row->flags as $flag)
                                    @php
                                        $hint = match ($flag) {
                                            'rawan' => collect([
                                                $row->periode >= \App\Services\Inventory\RepairService::rawanTimes()
                                                    ? $row->periode . ' servis dalam ' . \App\Services\Inventory\RepairService::rawanDays() . ' hari terakhir (batas ' . \App\Services\Inventory\RepairService::rawanTimes() . ' kali)' : null,
                                                $row->periode / max($row->units, 1) >= \App\Services\Inventory\RepairService::rawanRatio()
                                                    ? $row->periode . ' servis dari ' . $row->units . ' unit (' . (int) round($row->periode / max($row->units, 1) * 100) . '%, batas ' . \App\Models\AppSetting::int('repair_rawan_percent') . '%) dalam ' . \App\Services\Inventory\RepairService::rawanDays() . ' hari terakhir' : null,
                                            ])->filter()->implode('; '),
                                            'sulit' => $row->gagal . ' gagal dari ' . $row->diservis . ' servis (' . $row->persen_gagal . '%, batas ' . \App\Models\AppSetting::int('repair_gagal_percent') . '%)',
                                            default => 'Unit #' . $row->cost['worst_unit']['number'] . ' diservis ' . $fmtMoney($row->cost['worst_unit']['total'])
                                                . ' (' . (int) round($row->ratio * 100) . '% dari harga beli, ambang ' . (int) round($row->cost['warn_ratio'] * 100) . '%)',
                                        };
                                    @endphp
                                    <span class="badge-soft-{{ $flag === 'mahal' ? 'warning' : 'danger' }} px-2 py-1 rounded-pill text-nowrap"
                                          title="{{ $hint }}">{{ $flagLabel[$flag] }}</span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="pe-4 cell-block text-center" data-label="Rincian">
                        <button type="button" class="btn btn-sm btn-outline-primary js-kondisi-toggle"
                                data-url="{{ route('inventory.repairs.kondisi', $inventory) }}"
                                data-autoopen="{{ !empty($filters['inventory_id']) ? '1' : '0' }}"
                                aria-expanded="false" aria-label="Rincian servis {{ $inventory->name }}" title="Rincian servis">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </td>
                </tr>
                <tr class="d-none kondisi-detail">
                    <td colspan="10" class="bg-light px-3 px-md-4 py-3">
                        <div class="kondisi-panel"></div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
                        <i class="bi bi-tools fs-3 d-block mb-2 opacity-50"></i>
                        Belum ada barang yang cocok.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-3 px-md-4 py-3 border-top small text-muted">
    <div class="fw-semibold text-dark mb-1">Cara membaca</div>
    <div>Diservis: unit yang dikirim servis dan sudah selesai. Unit yang sama dihitung lagi setiap kali dikirim.</div>
    <div>Rawan rusak: servis dalam {{ \App\Services\Inventory\RepairService::rawanDays() }} hari terakhir mencapai {{ \App\Services\Inventory\RepairService::rawanTimes() }} kali, atau {{ \App\Models\AppSetting::int('repair_rawan_percent') }}% dari jumlah unit (minimal 2 servis).</div>
    <div>Sulit diperbaiki: unit gagal mencapai {{ \App\Models\AppSetting::int('repair_gagal_percent') }}% dari seluruh servis selesai (minimal 2 servis).</div>
    @if($canPrice)
        <div>Mahal diperbaiki: biaya servis satu unit mencapai ambang ganti dari harga belinya. Ambang bisa diubah per barang di <a href="{{ route('inventory.prices.index') }}" class="text-decoration-none">Daftar Harga Barang</a>.</div>
    @endif
    @if(auth()->user()->isSuperAdmin())
        <div class="mt-1">Batas lainnya diatur di <a href="{{ route('settings.index') }}" class="text-decoration-none">Pengaturan</a>.</div>
    @endif
</div>
