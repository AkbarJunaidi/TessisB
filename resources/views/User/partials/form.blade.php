@csrf

<div class="row">

    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">
            Nama
        </label>

        <input
            type="text"
            name="name"
            id="name"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $user->name ?? '') }}"
            required
        >

        @error('name')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">
            Email
        </label>

        <input
            type="email"
            name="email"
            id="email"
            class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $user->email ?? '') }}"
            required
        >

        @error('email')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

</div>

<div class="row">

    <div class="col-md-6 mb-3">
        <label for="password" class="form-label">
            Password
        </label>

        <input
            type="password"
            name="password"
            id="password"
            class="form-control @error('password') is-invalid @enderror"
            {{ isset($user) ? '' : 'required' }}
        >

        @if(isset($user))
            <small class="text-muted">
                Kosongkan apabila password tidak ingin diubah.
            </small>
        @endif

        @error('password')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="password_confirmation" class="form-label">
            Konfirmasi Password
        </label>

        <input
            type="password"
            name="password_confirmation"
            id="password_confirmation"
            class="form-control"
        >
    </div>

</div>

<div class="row">

    <div class="col-md-6 mb-3">
        <label for="role" class="form-label">
            Role
        </label>

        @if(auth()->user()->isSuperAdmin())
        <select
            name="role"
            id="role"
            class="form-select @error('role') is-invalid @enderror"
            required
        >
            <option value="">-- Pilih Role --</option>

            <option
                value="super_admin"
                @selected(old('role', $user->role ?? '') === 'super_admin')
            >
                Super Admin
            </option>

            <option
                value="admin"
                @selected(old('role', $user->role ?? '') === 'admin')
            >
                Admin
            </option>

            <option
                value="employee"
                @selected(old('role', $user->role ?? '') === 'employee')
            >
                Employee
            </option>

        </select>

        @error('role')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
        @else
        {{-- Hanya Super Admin yang boleh mengubah role user. Field tetap
             dikirim (readonly) supaya validasi 'role required' tetap lolos,
             tapi nilainya tidak akan diproses jika bukan Super Admin yang
             mengirim (lihat UserService::updateUser). --}}
        <input type="text" class="form-control" value="{{ ucwords(str_replace('_', ' ', $user->role ?? '')) }}" disabled>
        <input type="hidden" name="role" value="{{ $user->role ?? '' }}">
        <small class="text-muted">Hanya Super Admin yang dapat mengubah role.</small>
        @endif
    </div>

    <div class="col-md-6 mb-3">
        <label for="status" class="form-label">
            Status
        </label>

        <select
            name="status"
            id="status"
            class="form-select @error('status') is-invalid @enderror"
            required
        >
            <option
                value="active"
                @selected(old('status', $user->status ?? 'active') === 'active')
            >
                Active
            </option>

            <option
                value="inactive"
                @selected(old('status', $user->status ?? '') === 'inactive')
            >
                Inactive
            </option>

        </select>

        @error('status')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

</div>

@if(auth()->user()->isSuperAdmin())
<div class="card border-0 shadow-sm mt-3 mb-3">
    <div class="card-body">
        @include('user.partials.permission-card')
    </div>
</div>
@else
<div class="alert alert-secondary mt-3 mb-3 small">
    <i class="bi bi-lock me-1"></i> Hanya Super Admin yang dapat mengubah Hak Akses Pengguna.
</div>
@endif

<div class="d-flex justify-content-end gap-2 mt-4 ap-form-actions">

    <a
        href="{{ route('users.index') }}"
        class="btn btn-secondary"
    >
        Batal
    </a>

    <button
        type="submit"
        class="btn btn-primary"
    >
        Simpan
    </button>

</div>

