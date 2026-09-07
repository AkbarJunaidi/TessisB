@extends('layouts.app')

@section('title', 'Tambah Kontak')

@section('content')

<div class="container-fluid">

    <div class="row mb-4">

        <div class="col">
            <h3 class="fw-bold">
                Tambah Kontak
            </h3>

            <p class="text-muted mb-0">
                Simpan informasi client baru yang pernah/akan memakai jasa.
            </p>
        </div>

    </div>

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <form
                action="{{ route('contacts.store') }}"
                method="POST"
            >

                @include('contact.partials.form')

            </form>

        </div>

    </div>

</div>

@endsection
