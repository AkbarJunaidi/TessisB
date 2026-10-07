{{-- Item menu Kunci/Buka Kunci (gembok) untuk file atau folder di ruang bersama; hanya untuk user dengan permission `lock`, di dalam <ul class="dropdown-menu">.
     Pakai: @include('data-integration.partials.lock-toggle', ['item' => $folder, 'kind' => 'folder' atau 'file']); item harus punya isLocked(). --}}
@php
    $lockBase   = $kind === 'folder' ? 'folders' : 'files';
    $itemLocked = $item->isLocked();
@endphp
<li>
    <form method="POST" action="{{ route($lockBase . ($itemLocked ? '.unlock' : '.lock'), $item) }}">
        @csrf
        <button type="submit" class="dropdown-item small py-2">
            <i class="bi {{ $itemLocked ? 'bi-unlock' : 'bi-lock' }} me-2 text-muted"></i> {{ $itemLocked ? 'Buka Kunci' : 'Kunci' }}
        </button>
    </form>
</li>
