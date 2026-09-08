@extends('layouts.app')

@section('title', 'Detail Kontak')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">

        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-{{ $contact->avatar_color }}-subtle text-{{ $contact->avatar_color }} d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 56px; height: 56px; font-size: 1.1rem;">
                {{ $contact->initials }}
            </div>
            <div>
                <h3 class="fw-bold mb-0">{{ $contact->name }}</h3>
                <p class="text-muted mb-0">{{ $contact->company ?? 'Perorangan' }}</p>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <a href="{{ route('contacts.edit', $contact) }}" class="btn btn-warning">
                <i class="bi bi-pencil-square me-1"></i> Edit
            </a>
            <form
                action="{{ route('contacts.destroy', $contact) }}"
                method="POST"
                onsubmit="return confirm('Yakin ingin menghapus kontak ini?');"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i> Hapus
                </button>
            </form>
        </div>

    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">

        {{-- Kolom kiri: Info Kontak & Catatan --}}
        <div class="col-lg-4">

            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Informasi Kontak</h6>

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-telephone text-secondary"></i>
                        @if($contact->phone)
                            <a href="tel:{{ $contact->phone }}" class="text-decoration-none">{{ $contact->phone }}</a>
                        @else
                            <span class="text-muted">Belum diisi</span>
                        @endif
                    </div>

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-whatsapp text-secondary"></i>
                        @if($contact->phone && $contact->has_whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $contact->phone)) }}" target="_blank" rel="noopener" class="text-decoration-none">
                                Chat WhatsApp
                            </a>
                        @else
                            <span class="text-muted">Tidak tersedia</span>
                        @endif
                    </div>

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-envelope text-secondary"></i>
                        @if($contact->email)
                            <a href="mailto:{{ $contact->email }}" class="text-decoration-none">{{ $contact->email }}</a>
                        @else
                            <span class="text-muted">Belum diisi</span>
                        @endif
                    </div>

                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-geo-alt text-secondary mt-1"></i>
                        <span>{{ $contact->address ?? 'Belum diisi' }}</span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Catatan</h6>
                    <p class="mb-0 {{ $contact->notes ? '' : 'text-muted' }}">
                        {{ $contact->notes ?? 'Belum ada catatan untuk kontak ini.' }}
                    </p>
                </div>
            </div>

        </div>

        {{-- Kolom kanan: Ringkasan & Riwayat Project --}}
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Total Pendapatan dari Kontak Ini</div>
                        <div class="fw-bold fs-4 text-success">
                            {{ \App\Support\Money::formatRupiah($matchedProjects->sum(fn($p) => $p->total_income)) }}
                        </div>
                    </div>
                    <i class="bi bi-cash-coin fs-1 text-success opacity-25"></i>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        Riwayat Project
                        <span class="badge bg-light text-dark border fw-normal ms-1">{{ $matchedProjects->count() }}</span>
                    </h6>

                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Dicocokkan otomatis dari nama client di Project - hasil bisa tidak lengkap kalau
                        penulisan nama client di Project berbeda dengan nama Kontak ini.
                    </p>

                    @forelse($matchedProjects as $project)

                        <a href="{{ route('projects.show', $project) }}" class="text-decoration-none text-dark">
                            <div class="d-flex justify-content-between align-items-center p-3 rounded-3 border mb-2 contact-project-row">
                                <div>
                                    <div class="fw-semibold">{{ $project->name }}</div>
                                    <div class="text-muted small">
                                        <span class="badge bg-light text-dark border">
                                            {{ \App\Models\Project::STATUS_LABELS[$project->status] ?? $project->status }}
                                        </span>
                                        @if($project->event_date)
                                            <i class="bi bi-calendar-event ms-2 me-1"></i>{{ $project->event_date->format('d/m/Y') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="fw-bold text-success">
                                    {{ \App\Support\Money::formatRupiah($project->total_income) }}
                                </div>
                            </div>
                        </a>

                    @empty

                        <div class="text-center text-muted py-4">
                            Belum ada Project yang cocok dengan nama Kontak ini.
                        </div>

                    @endforelse

                </div>
            </div>

        </div>

    </div>

</div>

@endsection
