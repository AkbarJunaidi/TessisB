{{-- Partial: Tab Kwitansi (di Project Detail) - isinya dipindah ke
     project.partials.kwitansi-content supaya bisa dipakai bersama dengan
     halaman "Detail Keuangan" berdiri sendiri (kwitansi/project-detail.blade.php).
     Variabel yang dibutuhkan: $project (relasi financeItems & kwitansis sudah di-load) --}}

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        @include('project.partials.kwitansi-content', ['project' => $project])
    </div>
</div>
