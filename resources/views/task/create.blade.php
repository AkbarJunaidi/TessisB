@extends('layouts.app')

@section('title', 'Tambah Task Baru')

@section('content')
<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Add New Task</h3>
            <p class="text-muted small m-0">Tautkan kartu tugas baru ke dalam project: <strong>{{ $project->name }}</strong></p>
        </div>
        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-outline-secondary fw-medium">
            <i class="bi bi-arrow-left"></i> Kembali ke Board
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-body p-4">

            <form action="{{ route('tasks.store') }}" method="POST">
                @csrf

                <input type="hidden" name="project_id" value="{{ $project->id }}">

                <div class="mb-3">
                    <label for="title" class="form-label fw-semibold small text-secondary">Task Title (Judul Tugas) <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" placeholder="Contoh: Implementasi Form Validasi Input Gambar" value="{{ old('title') }}" required autofocus>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold small text-secondary">Description (Rincian Teknis Tugas)</label>
                    <textarea name="description" id="description" rows="4" class="form-control" placeholder="Tuliskan catatan instruksi kerja di sini...">{{ old('description') }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label fw-semibold small text-secondary">Priority (Tingkat Urgensi) <span class="text-danger">*</span></label>
                        <select name="priority" id="priority" class="form-select" required>
                            <option value="Low" {{ old('priority') == 'Low' ? 'selected' : '' }}>Low</option>
                            <option value="Medium" {{ old('priority', 'Medium') == 'Medium' ? 'selected' : '' }}>Medium</option>
                            <option value="High" {{ old('priority') == 'High' ? 'selected' : '' }}>High</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label fw-semibold small text-secondary">Initial Status (Status Awal) <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required>
                            @foreach($project->getBoardLists() as $list)
                                <option value="{{ $list['label'] }}" {{ old('status') == $list['label'] ? 'selected' : '' }}>{{ $list['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="deadline" class="form-label fw-semibold small text-secondary">Task Deadline <span class="text-danger">*</span></label>
                        <input type="date" name="deadline" id="deadline" class="form-control @error('deadline') is-invalid @enderror" value="{{ old('deadline', $project->deadline) }}" required>
                        @error('deadline')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4" style="max-width: 400px;">
                    <label for="assigned_to" class="form-label fw-semibold small text-secondary">Assigned User (Delegasikan Kepada)</label>
                    <select name="assigned_to" id="assigned_to" class="form-select">
                        <option value="">-- Pilih Anggota Tim (Unassigned) --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->role }})</option>
                        @endforeach
                    </select>
                </div>

                <hr class="border-light my-4">

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary px-4 fw-medium shadow-sm">
                        <i class="bi bi-plus-circle-fill me-1"></i> Submit Task
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection
