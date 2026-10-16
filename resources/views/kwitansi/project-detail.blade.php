@extends('layouts.app')

@section('title', 'Detail Keuangan - ' . $project->short_name)

@section('content')
<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <a data-back href="{{ route('kwitansi.index') }}" class="page-back"><i class="bi bi-arrow-left"></i> Keuangan</a>
            <h3 class="fw-bold mb-1">Detail Keuangan - {{ $project->short_name }}</h3>
            <p class="text-muted mb-0">{{ $project->client ?: $project->company ?: '-' }}</p>
        </div>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-primary d-flex align-items-center gap-2 fw-medium">
            <i class="bi bi-kanban"></i> Buka Detail Project
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            @include('project.partials.kwitansi-content', ['project' => $project])
        </div>
    </div>

</div>
@endsection
