<ol class="stepper">
    @foreach($steps as $i => $step)
        <li class="stepper-step is-{{ $step['state'] }}" @if($step['state'] === 'current') aria-current="step" @endif>
            <span class="stepper-dot">
                @if($step['state'] === 'done')
                    <i class="bi bi-check-lg"></i>
                @elseif($step['state'] === 'stop')
                    <i class="bi bi-x-lg"></i>
                @else
                    {{ $i + 1 }}
                @endif
            </span>
            <span class="stepper-text">
                <span class="stepper-label">{{ $step['label'] }}</span>
                @if(!empty($step['hint']))
                    <span class="stepper-hint">{{ $step['hint'] }}</span>
                @endif
            </span>
        </li>
    @endforeach
</ol>
