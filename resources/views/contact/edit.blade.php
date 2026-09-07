@extends('layouts.app')

@section('title', 'Edit Kontak')

@section('content')

<div class="container-fluid">

    <div class="row mb-4">

        <div class="col">
            <h3 class="fw-bold">
                Edit Kontak
            </h3>

            <p class="text-muted mb-0">
                Perbarui informasi client "{{ $contact->name }}".
            </p>
        </div>

    </div>

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <form
                action="{{ route('contacts.update', $contact) }}"
                method="POST"
            >
                @method('PUT')

                @include('contact.partials.form')

            </form>

        </div>

    </div>

</div>

@endsection
