{{-- Kartu alur: stepper per jalur, siapa yang bertindak, dan satu tombol langkah berikutnya (opsional). --}}
<div class="card border-0 shadow-sm rounded-3 mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-signpost-split me-2"></i>{{ $flowTitle ?? 'Tahapan' }}</h6>

        @foreach($tracks as $track)
            <div class="{{ $loop->last ? '' : 'mb-4 pb-3 border-bottom' }}">
                @if(!empty($track['label']))
                    <div class="small text-muted mb-2">
                        @if(!empty($track['icon']))
                            <i class="bi {{ $track['icon'] }} me-1"></i>
                        @endif
                        {{ $track['label'] }}
                        @if(!empty($track['badge']))
                            <span class="badge bg-danger ms-1">{{ $track['badge'] }}</span>
                        @endif
                    </div>
                @endif

                @include('partials.stepper', ['steps' => $track['steps']])

                @if(!empty($track['text']))
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 small">
                        <div>
                            @if(!empty($track['owner']))
                                <span class="fw-semibold">{{ $track['owner'] }}:</span>
                            @endif
                            {{ $track['text'] }}
                            @if(!empty($track['mine']))
                                <span class="badge bg-primary ms-1">Giliran Anda</span>
                            @endif
                        </div>

                        @if(!empty($track['action']))
                            <form action="{{ $track['action']['url'] }}" method="POST" class="ms-md-auto"
                                  data-confirm="{{ $track['action']['confirm'] }}" data-confirm-label="{{ $track['action']['confirm_label'] }}">
                                @csrf
                                @method($track['action']['method'] ?? 'POST')
                                @foreach(($track['action']['fields'] ?? []) as $name => $value)
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endforeach
                                <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2 fw-medium">
                                    <i class="bi {{ $track['action']['icon'] ?? 'bi-arrow-right' }}"></i> {{ $track['action']['label'] }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
