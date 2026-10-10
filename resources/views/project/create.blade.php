@extends('layouts.app')

@section('title', 'Tambah Project')

@section('content')
<div class="container-fluid p-0">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Tambah Project</h3>
            <p class="d-none d-md-block text-muted small m-0">Isi detail project.</p>
        </div>
        <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 fw-medium">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-body p-4">

            <form action="{{ route('projects.store') }}" method="POST">
                @csrf
                @include('project.partials.form', ['project' => null])

                <hr class="border-light my-4">

                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-light px-4 fw-medium">Reset</button>
                    <button type="submit" class="btn btn-primary px-4 fw-medium shadow-sm">
                        <i class="bi bi-folder-plus me-1"></i> Simpan Project
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection
