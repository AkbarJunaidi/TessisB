@extends('layouts.app')

@section('title', 'Edit User')

@section('content')

<div class="container-fluid">

    <div class="row mb-4">

        <div class="col">
            <h3 class="fw-bold">
                Edit User
            </h3>

            <p class="text-muted mb-0">
                Perbarui informasi akun pengguna.
            </p>
        </div>

    </div>

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <form
                action="{{ route('users.update', $user) }}"
                method="POST"
            >

                @csrf
                @method('PUT')

                @include('user.partials.form')

            </form>

        </div>

    </div>

</div>

@endsection

