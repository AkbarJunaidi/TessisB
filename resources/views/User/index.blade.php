@extends('layouts.app')

@section('title', 'User Management')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="fw-bold mb-1">
                User Management
            </h3>

            <p class="text-muted mb-0">
                Kelola seluruh akun pengguna sistem.
            </p>
        </div>

        <a
            href="{{ route('users.create') }}"
            class="btn btn-primary"
        >
            <i class="bi bi-person-plus me-2"></i>
            Add User
        </a>

    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">

            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>
    @endif

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">

                        <tr>

                            <th width="60">#</th>

                            <th>Nama</th>

                            <th>Email</th>

                            <th>Role</th>

                            <th>Status</th>

                            <th>Last Login</th>

                            <th width="220" class="text-center">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($users as $user)

                            <tr>

                                <td>
                                    {{ $loop->iteration + ($users->firstItem() - 1) }}
                                </td>

                                <td>

                                    <strong>
                                        {{ $user->name }}
                                    </strong>

                                </td>

                                <td>

                                    {{ $user->email }}

                                </td>

                                <td>

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

                                </td>

                                <td>

                                    @if($user->status == 'active')

                                        <span class="badge bg-success">
                                            Active
                                        </span>

                                    @else

                                        <span class="badge bg-danger">
                                            Inactive
                                        </span>

                                    @endif

                                </td>

                                <td>

                                    {{ $user->last_login_at?->format('d M Y H:i') ?? '-' }}

                                </td>

                                <td class="text-center">

                                    <a
                                        href="{{ route('users.show', $user) }}"
                                        class="btn btn-sm btn-info"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a
                                        href="{{ route('users.edit', $user) }}"
                                        class="btn btn-sm btn-warning"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form
                                        action="{{ route('users.destroy', $user) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Yakin ingin menghapus user ini?');"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="7"
                                    class="text-center text-muted py-5"
                                >

                                    Belum ada data user.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        @if($users->hasPages())

            <div class="card-footer bg-white">

                {{ $users->links() }}

            </div>

        @endif

    </div>

</div>

@endsection

