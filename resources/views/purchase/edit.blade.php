@extends('layouts.app')

@section('title', 'Ubah Pembelian')

@section('content')
<div class="container-fluid p-0">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Ubah Pembelian {{ $purchase->code }}</h3>
        <p class="text-muted mb-0">Status saat ini: {{ $purchase->status }}.</p>
    </div>

    <form action="{{ route('purchases.update', $purchase) }}" method="POST" enctype="multipart/form-data">
        @method('PUT')
        @include('purchase.partials.form')
    </form>

</div>
@endsection
