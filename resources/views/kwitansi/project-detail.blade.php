@extends('layouts.app')

@section('title', 'Detail Keuangan - ' . $project->name)

@section('content')
<div class="container-fluid p-0">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <a href="{{ route('kwitansi.index') }}" class="text-decoration-none small text-muted d-inline-block mb-1">
                <i class="bi bi-arrow-left"></i> Kembali ke Keuangan
            </a>
            <h3 class="fw-bold mb-1">Detail Keuangan - {{ $project->name }}</h3>
            <p class="text-muted mb-0">{{ $project->client ?: $project->company ?: '-' }}</p>
        </div>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-kanban"></i> Buka Project Detail
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            @include('project.partials.kwitansi-content', ['project' => $project])
        </div>
    </div>

</div>
@endsection
