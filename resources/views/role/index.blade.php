@extends('layouts.app')

@section('title', 'Role & Hak Akses')

@section('content')
<div class="container-fluid">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Role &amp; Hak Akses</h3>
            <p class="d-none d-md-block text-muted mb-0">Buat role sendiri, mis. Finance atau Gudang, lalu pilih hak aksesnya.</p>
        </div>

        <a href="{{ route('roles.create') }}" class="btn btn-primary d-flex align-items-center gap-2 fw-medium">
            <i class="bi bi-plus-lg"></i> Tambah Role
        </a>
    </div>

    <div class="alert alert-info small d-flex gap-2 mb-4">
        <i class="bi bi-info-circle fs-5"></i>
        <div>
            Role buatan sendiri dipilih di halaman <strong>Tambah User</strong> atau <strong>Edit User</strong>, di kolom Role.
            Role bawaan (Super Admin, Admin, Employee) tidak bisa diubah.
        </div>
    </div>

    <div class="app-panel overflow-hidden mb-4">
        <div class="px-3 px-md-4 pt-3 pt-md-4">
            <h6 class="fw-bold mb-3">Role Buatan Sendiri</h6>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-modern table-stack align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Nama Role</th>
                        <th>Tingkat Dasar</th>
                        <th>Izin Aktif</th>
                        <th>Pengguna</th>
                        <th class="pe-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td class="ps-4" data-label="Nama Role">
                                <div class="fw-semibold">{{ $role->name }}</div>
                                @if($role->description)
                                    <div class="small text-muted">{{ $role->description }}</div>
                                @endif
                            </td>
                            <td data-label="Tingkat Dasar">
                                <span class="badge {{ $role->base_role === 'admin' ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ \App\Models\CustomRole::BASE_ROLES[$role->base_role] ?? $role->base_role }}
                                </span>
                            </td>
                            <td data-label="Izin Aktif">{{ $role->activePermissionCount() }}</td>
                            <td data-label="Pengguna">{{ $role->users_count }} user</td>
                            <td class="pe-4" data-label="Aksi">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary"
                                       title="Ubah" aria-label="Ubah role {{ $role->name }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    @if($role->users_count > 0)
                                        <button type="button" class="btn btn-sm btn-outline-danger" disabled
                                                title="Masih dipakai {{ $role->users_count }} user" aria-label="Role {{ $role->name }} tidak bisa dihapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @else
                                        <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline"
                                              data-confirm="Yakin ingin menghapus role ini?" data-confirm-label="Hapus" data-confirm-danger>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Hapus" aria-label="Hapus role {{ $role->name }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                Belum ada role buatan sendiri. Klik <strong>Tambah Role</strong> untuk membuat yang pertama.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="app-panel overflow-hidden">
        <div class="px-3 px-md-4 pt-3 pt-md-4">
            <h6 class="fw-bold mb-3">Role Bawaan</h6>
        </div>

        <div class="table-responsive">
            <table class="table table-modern table-stack align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Nama Role</th>
                        <th>Keterangan</th>
                        <th class="pe-4">Pengguna</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        'super_admin' => ['Super Admin', 'bg-danger', 'Akses penuh ke seluruh sistem.'],
                        'admin'       => ['Admin', 'bg-primary', 'Akses luas sesuai izin bawaan.'],
                        'employee'    => ['Employee', 'bg-secondary', 'Akses terbatas sesuai izin bawaan.'],
                    ] as $key => [$label, $badge, $hint])
                        <tr>
                            <td class="ps-4" data-label="Nama Role"><span class="badge {{ $badge }}">{{ $label }}</span></td>
                            <td data-label="Keterangan" class="text-muted">{{ $hint }}</td>
                            <td class="pe-4" data-label="Pengguna">{{ $builtinCounts[$key] ?? 0 }} user</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
