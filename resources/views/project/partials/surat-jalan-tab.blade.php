{{-- Partial: Tab Surat Jalan
     Variabel yang dibutuhkan saat di-include: $project (dengan relasi suratJalans.items.inventory sudah di-load) --}}

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-3">Surat Jalan Project Ini</h6>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th style="width:30px;"></th>
                        <th>Nomor</th>
                        <th>Keperluan</th>
                        <th>Tanggal Terbit</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->suratJalans as $sj)
                        <tr class="border-top">
                            <td class="text-muted">
                                <button type="button" class="btn btn-sm btn-link text-muted p-0" data-bs-toggle="collapse" data-bs-target="#sjDetail{{ $sj->id }}">
                                    <i class="bi bi-chevron-down small"></i>
                                </button>
                            </td>
                            <td class="fw-semibold">{{ $sj->nomor }}</td>
                            <td>{{ $sj->keperluan ?: '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($sj->tanggal_terbit)->translatedFormat('d M Y') }}</td>
                            <td>
                                <span class="badge {{ $sj->status === 'Selesai' ? 'bg-secondary' : 'bg-success' }}">{{ $sj->status }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('surat-jalan.preview', $sj) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Preview
                                </a>
                                <a href="{{ route('surat-jalan.download', $sj) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" class="p-0 border-0">
                                <div class="collapse" id="sjDetail{{ $sj->id }}">
                                    <div class="bg-light p-3">
                                        <table class="table table-sm bg-white mb-0 rounded overflow-hidden">
                                            <thead>
                                                <tr class="text-muted small">
                                                    <th>Barang</th>
                                                    <th class="text-center">Qty Dipakai</th>
                                                    <th class="text-center">Dikembalikan</th>
                                                    <th class="text-center">Sisa</th>
                                                    <th class="text-end">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sj->items as $item)
                                                    @php $sisa = $item->qty_dipakai - $item->qty_dikembalikan; @endphp
                                                    <tr>
                                                        <td>{{ $item->inventory->name ?? '-' }}</td>
                                                        <td class="text-center">{{ $item->qty_dipakai }}</td>
                                                        <td class="text-center">{{ $item->qty_dikembalikan }}</td>
                                                        <td class="text-center">
                                                            <span class="badge {{ $sisa > 0 ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">{{ $sisa }}</span>
                                                        </td>
                                                        <td class="text-end">
                                                            @if($sisa > 0 && auth()->user()->hasPermission('surat_jalan', 'create'))
                                                                <form action="{{ route('surat-jalan.items.return', $item) }}" method="POST" class="d-inline-flex gap-1 justify-content-end">
                                                                    @csrf
                                                                    <input type="number" name="qty" min="1" max="{{ $sisa }}" value="{{ $sisa }}" class="form-control form-control-sm" style="width:70px;">
                                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Kembalikan</button>
                                                                </form>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada Surat Jalan untuk project ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Putar ikon chevron saat baris Surat Jalan di-expand/collapse
    document.querySelectorAll('[data-bs-target^="#sjDetail"]').forEach(function (row) {
        const targetId = row.getAttribute('data-bs-target');
        const collapseEl = document.querySelector(targetId);
        const chevron = row.querySelector('.bi-chevron-down');

        if (!collapseEl || !chevron) return;

        collapseEl.addEventListener('show.bs.collapse', () => chevron.classList.add('rotate-180'));
        collapseEl.addEventListener('hide.bs.collapse', () => chevron.classList.remove('rotate-180'));
    });
</script>

<style>
    .rotate-180 { transform: rotate(180deg); transition: transform .2s ease; display: inline-block; }
</style>
