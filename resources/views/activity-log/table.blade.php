<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-dark"><i class="fas fa-list me-2"></i>Data Log Aktivitas</h6>
        <span class="badge bg-secondary text-white">{{ $logs->total() }} Total Log</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 u-w-20pct">Waktu</th>
                        <th class="u-w-25pct">User</th>
                        <th class="u-w-30pct">Modul</th>
                        <th class="pe-4 u-w-25pct">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="ps-4">
                                <span class="small text-muted">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary-soft text-primary rounded-circle me-2 d-flex align-items-center justify-content-center u-w-28px u-h-28px u-fs-11px u-fw-bold u-bgc-e8f0fe">
                                        {{ strtoupper(substr($log->user->name ?? 'SYS', 0, 2)) }}
                                    </div>
                                    <span class="fw-semibold text-dark">
                                        {{ $log->user->name ?? 'Deleted User / System' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info-soft text-info rounded-pill px-3 py-1.5 border border-info-subtle u-bgc-e0f7fa u-fs-12px">
                                    {{-- DIPERBAIKI: Memotong FQDN App\Models\Inventory menjadi Inventory --}}
                                    {{ \App\Support\LogLabel::module($log->module) }}
                                </span>
                            </td>
                            <td class="pe-4">
                                {{-- DIPERBAIKI: Pengecekan Case-Insensitive untuk Warna Action --}}
                                @php $lowerAction = strtolower($log->action); @endphp

                                @if(str_contains($lowerAction, 'delete') || str_contains($lowerAction, 'logout'))
                                    <span class="text-danger fw-medium">
                                        <i class="fas fa-circle text-danger me-1 u-fs-7px"></i>{{ \App\Support\LogLabel::action($log->action) }}
                                    </span>
                                @elseif(str_contains($lowerAction, 'create') || str_contains($lowerAction, 'login') || str_contains($lowerAction, 'upload'))
                                    <span class="text-success fw-medium">
                                        <i class="fas fa-circle text-success me-1 u-fs-7px"></i>{{ \App\Support\LogLabel::action($log->action) }}
                                    </span>
                                @else
                                    <span class="text-warning fw-medium">
                                        <i class="fas fa-circle text-warning me-1 u-fs-7px"></i>{{ \App\Support\LogLabel::action($log->action) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-folder-open d-block mb-3 text-gray-400 u-fs-32px"></i>
                                Tidak ditemukan catatan jejak log aktivitas yang cocok dengan kriteria filter Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($logs->hasPages())
        <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
            {{ $logs->appends(request()->query())->links('pagination.app') }}
        </div>
    @endif
</div>
