{{-- Tab Kwitansi di Project Detail; isinya di project.partials.kwitansi-content (dipakai juga halaman Detail Keuangan). Variabel: $project. --}}

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        @include('project.partials.kwitansi-content', ['project' => $project])
    </div>
</div>
