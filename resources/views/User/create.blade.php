
@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')

<div class="container-fluid">

    <div class="row mb-4">

        <div class="col">
            <h3 class="fw-bold">
                Tambah User
            </h3>

            <p class="text-muted mb-0">
                Tambahkan akun pengguna baru ke dalam sistem.
            </p>
        </div>

    </div>

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <form
                action="{{ route('users.store') }}"
                method="POST"
            >

                @include('user.partials.form')

            </form>

        </div>

    </div>

</div>

@endsection

