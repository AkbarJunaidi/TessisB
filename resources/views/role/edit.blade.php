@extends('layouts.app')

@section('title', 'Ubah Role')

@section('content')
<div class="container-fluid p-0">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a data-back href="{{ route('roles.index') }}" class="page-back"><i class="bi bi-arrow-left"></i> Daftar Role</a>
            <h3 class="fw-bold text-dark m-0">Ubah Role</h3>
            <p class="d-none d-md-block text-muted small m-0">Perubahan izin langsung berlaku untuk semua user yang memakai role ini.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-body p-4">
            <form action="{{ route('roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')
                @include('role.partials.form')

                <hr class="border-light my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('roles.index') }}" class="btn btn-light px-4 fw-medium">Batal</a>
                    <button type="submit" class="btn btn-primary px-4 fw-medium shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
