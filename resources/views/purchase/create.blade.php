@extends('layouts.app')

@section('title', 'Buat Pembelian')

@section('content')
<div class="container-fluid p-0">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Buat Pembelian</h3>
        <p class="d-none d-md-block text-muted mb-0">Disimpan sebagai Draft dulu, lalu diajukan untuk approval.</p>
    </div>

    <form action="{{ route('purchases.store') }}" method="POST" enctype="multipart/form-data">
        @include('purchase.partials.form')
    </form>

</div>
@endsection
