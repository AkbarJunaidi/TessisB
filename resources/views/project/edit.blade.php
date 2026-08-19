@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Edit Project</h3>
            <p class="text-muted small m-0">Perbarui detail project "{{ $project->name }}".</p>
        </div>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 fw-medium">
            <i class="bi bi-arrow-left"></i> Kembali ke Detail Project
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-body p-4">

            <form action="{{ route('projects.update', $project) }}" method="POST">
                @csrf
                @method('PUT')
                @include('project.partials.form', ['project' => $project])

                <hr class="border-light my-4">

                <div class="d-flex justify-content-end gap-2 ap-form-actions">
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-light px-4 fw-medium">Batal</a>
                    <button type="submit" class="btn btn-primary px-4 fw-medium shadow-sm">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection
