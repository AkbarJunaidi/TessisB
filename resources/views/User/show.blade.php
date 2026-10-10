@extends('layouts.app')

@section('title', 'Detail User')

@section('content')

<div class="container-fluid">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>

            <h3 class="fw-bold mb-1">
                Detail User
            </h3>

            <p class="d-none d-md-block text-muted mb-0">
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

    @if($pendingPasswordReset)

        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">

            <div>
                <i class="bi bi-key-fill me-2"></i>
                <strong>User ini mengajukan Lupa Password</strong>
                <div class="small text-muted mt-1">
                    Diajukan {{ $pendingPasswordReset->created_at->format('d M Y H:i') }}.
                    Reset password di bawah ini untuk menandai permintaan selesai.
                </div>
            </div>

            @if(auth()->user()->isSuperAdmin())
                <button
                    type="button"
                    class="btn btn-sm btn-warning"
                    data-bs-toggle="modal"
                    data-bs-target="#resetPasswordModal"
                >
                    <i class="bi bi-arrow-repeat me-1"></i>
                    Reset Password
                </button>
            @endif

        </div>

    @endif

    @if(auth()->user()->isSuperAdmin())

        <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('users.reset-password', $user) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="modal-header">
                            <h6 class="modal-title fw-bold">Reset Password - {{ $user->name }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">

                            <div class="mb-3">
                                <label for="password" class="form-label">Password Baru</label>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    minlength="8"
                                    required
                                >
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Minimal 8 karakter.</div>
                            </div>

                            <div class="mb-2">
                                <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    id="password_confirmation"
                                    class="form-control"
                                    minlength="8"
                                    required
                                >
                            </div>

                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-arrow-repeat me-1"></i>
                                Reset Password
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

    @endif

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

            @if(auth()->user()->isSuperAdmin())

                @php($currentPassword = $user->currentPassword())

                <hr>

                <div class="row mb-3 align-items-center">

                    <div class="col-md-3 fw-semibold">
                        Password Saat Ini
                    </div>

                    <div class="col-md-9">

                        @if($currentPassword !== null)

                            <div class="input-group user-password-group">
                                <input
                                    type="password"
                                    id="currentPasswordField"
                                    class="form-control"
                                    value="{{ $currentPassword }}"
                                    readonly
                                    autocomplete="off"
                                >
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    id="currentPasswordToggle"
                                    aria-label="Tampilkan password"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>

                        @else

                            <span class="text-muted">Belum tercatat</span>

                        @endif

                    </div>

                </div>

            @endif

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

@if(auth()->user()->isSuperAdmin())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                @if($errors->has('password'))
                    var resetModalEl = document.getElementById('resetPasswordModal');
                    if (resetModalEl) {
                        new bootstrap.Modal(resetModalEl).show();
                    }
                @endif

                // Tampilkan atau sembunyikan password saat ini.
                var field = document.getElementById('currentPasswordField');
                var toggle = document.getElementById('currentPasswordToggle');
                if (field && toggle) {
                    toggle.addEventListener('click', function () {
                        var show = field.type === 'password';
                        field.type = show ? 'text' : 'password';
                        toggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
                        toggle.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                    });
                }
            });
        </script>
    @endpush
@endif

@endsection

