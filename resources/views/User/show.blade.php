@extends('layouts.app')

@section('title', 'Detail User')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3 class="fw-bold mb-1">
                Detail User
            </h3>

            <p class="text-muted mb-0">
                Informasi lengkap akun pengguna.
            </p>

        </div>

        <a
            href="{{ route('users.index') }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-arrow-left me-2"></i>
            Kembali
        </a>

    </div>

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <div class="row mb-3">

                <div class="col-md-3 fw-semibold">
                    Nama
                </div>

                <div class="col-md-9">
                    {{ $user->name }}
                </div>

            </div>

            <hr>

            <div class="row mb-3">

                <div class="col-md-3 fw-semibold">
                    Email
                </div>

                <div class="col-md-9">
                    {{ $user->email }}
                </div>

            </div>

            <hr>

            <div class="row mb-3">

                <div class="col-md-3 fw-semibold">
                    Role
                </div>

                <div class="col-md-9">

                    @switch($user->role)

                        @case('super_admin')

                            <span class="badge bg-danger">
                                Super Admin
                            </span>

                            @break

                        @case('admin')

                            <span class="badge bg-primary">
                                Admin
                            </span>

                            @break

                        @default

                            <span class="badge bg-secondary">
                                Employee
                            </span>

                    @endswitch

                </div>

            </div>

            <hr>

            <div class="row mb-3">

                <div class="col-md-3 fw-semibold">
                    Status
                </div>

                <div class="col-md-9">

                    @if($user->status === 'active')

                        <span class="badge bg-success">
                            Active
                        </span>

                    @else

                        <span class="badge bg-danger">
                            Inactive
                        </span>

                    @endif

                </div>

            </div>

            <hr>

            <div class="row mb-3">

                <div class="col-md-3 fw-semibold">
                    Last Login
                </div>

                <div class="col-md-9">
                    {{ $user->last_login_at?->format('d M Y H:i:s') ?? '-' }}
                </div>

            </div>

            <hr>

            <div class="row mb-3">

                <div class="col-md-3 fw-semibold">
                    Dibuat
                </div>

                <div class="col-md-9">
                    {{ $user->created_at->format('d M Y H:i:s') }}
                </div>

            </div>

            <hr>

            <div class="row">

                <div class="col-md-3 fw-semibold">
                    Terakhir Diubah
                </div>

                <div class="col-md-9">
                    {{ $user->updated_at->format('d M Y H:i:s') }}
                </div>

            </div>

        </div>

    </div>

</div>

@endsection

