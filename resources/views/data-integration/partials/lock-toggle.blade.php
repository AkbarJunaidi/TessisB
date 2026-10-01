{{--
    Partial: item menu "Kunci" / "Buka Kunci" (gembok) untuk file atau folder di ruang bersama.

    Cara pakai (di dalam <ul class="dropdown-menu">), hanya untuk user dengan permission `lock`:
      @include('data-integration.partials.lock-toggle', ['item' => $folder, 'kind' => 'folder'])   {{-- atau 'file' --}}

    Item harus punya method isLocked() (model File / Folder).
--}}
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
