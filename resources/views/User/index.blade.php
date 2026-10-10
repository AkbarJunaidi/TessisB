@extends('layouts.app')

@section('title', 'Kelola User')

@section('content')

<div class="container-fluid">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <h3 class="fw-bold mb-1">
                Kelola User
            </h3>

            <p class="d-none d-md-block text-muted mb-0">
                Kelola seluruh akun pengguna sistem.
            </p>
        </div>

        @if(auth()->user()->isSuperAdmin())
        <a
            href="{{ route('users.create') }}"
            class="btn btn-primary"
        >
            <i class="bi bi-person-plus me-2"></i>
            Tambah User
        </a>
        @endif

    </div>

    <div class="app-panel overflow-hidden">

        <div class="px-3 px-md-4 pt-3 pt-md-4">
            <h6 class="fw-bold mb-3">Semua User</h6>
        </div>

        <div class="table-responsive">

            <table class="table table-hover table-modern table-stack align-middle mb-0">

                <thead>

                    <tr>

                        <th width="60" class="ps-4">#</th>

                        <th>Nama</th>

                        <th>Email</th>

                        <th>Role</th>

                        <th>Status</th>

                        <th>Login Terakhir</th>

                        <th>Info</th>

                        <th class="text-center pe-4">
                            Aksi
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($users as $user)

                        <tr>

                            <td class="ps-4" data-label="#">
                                {{ $loop->iteration + ($users->firstItem() - 1) }}
                            </td>

                            <td data-label="Nama">

                                <strong>
                                    {{ $user->name }}
                                </strong>

                            </td>

                            <td data-label="Email">

                                {{ $user->email }}

                            </td>

                            <td data-label="Role">

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

                            <td data-label="Status">

                                @if($user->status == 'active')

                                    <span class="badge bg-success">
                                        Aktif
                                    </span>

                                @else

                                    <span class="badge bg-danger">
                                        Nonaktif
                                    </span>

                                @endif

                            </td>

                            <td data-label="Login Terakhir">

                                {{ $user->last_login_at?->format('d M Y H:i') ?? '-' }}

                            </td>

                            <td data-label="Info">

                                @if(in_array($user->id, $pendingPasswordResetUserIds, true))
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-key me-1"></i>
                                        Lupa Password
                                    </span>
                                @endif

                            </td>

                            <td class="text-center pe-4 cell-block" data-label="Aksi">

                                <div class="d-flex justify-content-center gap-2">

                                    <a
                                        href="{{ route('users.show', $user) }}"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Lihat Detail" aria-label="Lihat detail {{ $user->name }}"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if(auth()->user()->hasPermission('user_management', 'edit_user'))
                                    <a
                                        href="{{ route('users.edit', $user) }}"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Ubah" aria-label="Ubah user {{ $user->name }}"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endif

                                    @if(auth()->user()->hasPermission('user_management', 'delete_user'))
                                    <form
                                        action="{{ route('users.destroy', $user) }}"
                                        method="POST"
                                        class="d-inline"
                                        data-confirm="Yakin ingin menghapus user ini?" data-confirm-label="Hapus" data-confirm-danger
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Hapus" aria-label="Hapus user {{ $user->name }}"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>

                                    </form>
                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="8"
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
        <div class="mt-3">{{ $users->links('pagination.app') }}</div>
    @endif

</div>

@endsection

