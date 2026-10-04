{{-- Tab halaman Keuangan. Variabel: $active ('summary'|'projects'|'transactions') --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $active === 'summary' ? 'active' : '' }}" href="{{ route('finance.summary') }}">Ringkasan</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $active === 'projects' ? 'active' : '' }}" href="{{ route('kwitansi.index') }}">Per Project</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $active === 'transactions' ? 'active' : '' }}" href="{{ route('finance.transactions.index') }}">Transaksi</a>
    </li>
</ul>
