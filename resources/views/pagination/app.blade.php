{{-- Pagination seragam: Total, baris per halaman, 4 nomor halaman, dan "Ke halaman". Tampil bila data lebih dari 10.
     Dipakai lewat ->links('pagination.app'); perilaku select/input ada di public/js/layouts/pager.js. --}}
@if($paginator->total() > 10)
    @php
        $paginator->withQueryString();
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $sizes = collect(\App\Support\PerPage::OPTIONS)->push($paginator->perPage())->unique()->sort()->values();

        // Jendela 4 nomor halaman yang bergeser mengikuti halaman aktif; lompat jauh lewat "Ke halaman".
        $start = max(1, min($current - 1, $last - 3));
        $items = range($start, min($last, $start + 3));
    @endphp

    <nav class="app-pager" aria-label="Navigasi halaman">
        <div class="app-pager-info">
            <span class="app-pager-total">Total {{ number_format($paginator->total()) }}</span>
            <select class="form-select form-select-sm app-pager-size" data-pager-size aria-label="Jumlah baris per halaman">
                @foreach($sizes as $size)
                    <option value="{{ $size }}" @selected($paginator->perPage() === $size)>{{ $size }}/halaman</option>
                @endforeach
            </select>
        </div>

        <ul class="app-pager-pages">
            <li>
                @if($paginator->onFirstPage())
                    <span class="is-disabled" aria-disabled="true"><i class="bi bi-chevron-left"></i></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></a>
                @endif
            </li>

            @foreach($items as $item)
                <li>
                    @if($item === $current)
                        <span class="is-active" aria-current="page">{{ $item }}</span>
                    @else
                        <a href="{{ $paginator->url($item) }}" aria-label="Halaman {{ $item }}">{{ $item }}</a>
                    @endif
                </li>
            @endforeach

            <li>
                @if($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></a>
                @else
                    <span class="is-disabled" aria-disabled="true"><i class="bi bi-chevron-right"></i></span>
                @endif
            </li>
        </ul>

        <label class="app-pager-goto">
            <span>Ke halaman</span>
            <input type="number" class="form-control form-control-sm" min="1" max="{{ $last }}" value="{{ $current }}"
                   data-pager-goto data-pager-current="{{ $current }}" aria-label="Ke halaman nomor">
        </label>
    </nav>
@endif
