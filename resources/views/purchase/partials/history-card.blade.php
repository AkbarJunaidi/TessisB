<div class="card border-0 shadow-sm rounded-3 mt-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Riwayat Status</h6>

        @forelse($purchase->histories->reverse() as $history)
            <div class="d-flex justify-content-between gap-3 small py-2 {{ $loop->first ? '' : 'border-top' }}">
                <div>
                    <div class="fw-semibold">{{ $history->label }}</div>
                    @if($history->user)
                        <div class="text-muted">{{ $history->user->name }}</div>
                    @endif
                    @if($history->note)
                        <div>{{ $history->note }}</div>
                    @endif
                </div>
                <div class="text-muted text-nowrap text-end">{{ $history->created_at->translatedFormat('d M Y H:i') }}</div>
            </div>
        @empty
            <div class="small text-muted">Belum ada riwayat.</div>
        @endforelse
    </div>
</div>
