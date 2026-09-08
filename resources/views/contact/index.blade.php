@extends('layouts.app')

@section('title', 'Kontak')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="fw-bold mb-1">
                Kontak
            </h3>

            <p class="text-muted mb-0">
                Buku alamat client yang pernah memakai jasa.
            </p>
        </div>

        <a
            href="{{ route('contacts.create') }}"
            class="btn btn-primary"
        >
            <i class="bi bi-person-plus me-2"></i>
            Tambah Kontak
        </a>

    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Kartu Statistik --}}
    <div class="row g-3 mb-4">

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Total Kontak</div>
                        <div class="fw-bold fs-4">{{ number_format($stats['total']) }}</div>
                    </div>
                    <i class="bi bi-people fs-3 text-primary opacity-50"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Punya WhatsApp</div>
                        <div class="fw-bold fs-4">{{ number_format($stats['with_whatsapp']) }}</div>
                    </div>
                    <i class="bi bi-whatsapp fs-3 text-success opacity-50"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Kontak Baru Bulan Ini</div>
                        <div class="fw-bold fs-4">{{ number_format($stats['new_this_month']) }}</div>
                    </div>
                    <i class="bi bi-person-plus fs-3 text-info opacity-50"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Total Pendapatan</div>
                        <div class="fw-bold fs-5 text-success">{{ \App\Support\Money::formatRupiah($stats['total_revenue']) }}</div>
                    </div>
                    <i class="bi bi-cash-coin fs-3 text-success opacity-50"></i>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h6 class="fw-bold mb-0">Kontak Terbaru</h6>

                {{-- Filter huruf awal nama (A-Z) --}}
                <div class="d-flex flex-wrap gap-1">
                    @if(!empty($filters['letter']))
                        <a href="{{ route('contacts.index', array_merge($filters, ['letter' => null])) }}" class="btn btn-sm btn-outline-secondary">Semua</a>
                    @endif
                    @foreach(range('A', 'Z') as $letter)
                        <a
                            href="{{ route('contacts.index', array_merge($filters, ['letter' => $letter])) }}"
                            class="btn btn-sm {{ ($filters['letter'] ?? '') === $letter ? 'btn-primary' : 'btn-outline-secondary' }}"
                            style="min-width: 32px;"
                        >{{ $letter }}</a>
                    @endforeach
                </div>
            </div>

            <form method="GET" class="row g-2 mb-3">
                @if(!empty($filters['letter']))
                    <input type="hidden" name="letter" value="{{ $filters['letter'] }}">
                @endif
                <div class="col-md-4">
                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        placeholder="Cari nama, perusahaan, No. HP/WA, atau email..."
                        value="{{ $filters['search'] ?? '' }}"
                    >
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                </div>
                @if(!empty($filters['search']))
                    <div class="col-md-2">
                        <a href="{{ route('contacts.index', array_filter(['letter' => $filters['letter'] ?? null])) }}" class="btn btn-sm btn-outline-secondary w-100">
                            Reset Pencarian
                        </a>
                    </div>
                @endif
            </form>

            <div class="row g-3">

                @forelse($contacts as $contact)

                    <div class="col-md-6 col-lg-4">
                        <div
                            class="card border-0 shadow-sm rounded-3 h-100 contact-card"
                            style="cursor: pointer;"
                            onclick="window.location='{{ route('contacts.show', $contact) }}'"
                        >
                            <div class="card-body">

                                <div class="d-flex align-items-start gap-2 mb-2">
                                    <div class="rounded-circle bg-{{ $contact->avatar_color }}-subtle text-{{ $contact->avatar_color }} d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 42px; height: 42px; font-size: 0.85rem;">
                                        {{ $contact->initials }}
                                    </div>
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <h6 class="fw-bold text-dark mb-0 text-truncate">
                                            {{ $contact->name }}
                                        </h6>
                                        <div class="text-muted small text-truncate">
                                            {{ $contact->company ?? 'Perorangan' }}
                                        </div>
                                    </div>
                                </div>

                                @if($contact->phone)
                                    <div class="d-flex align-items-center gap-2 text-secondary small mb-1">
                                        <i class="bi bi-telephone"></i>
                                        <span>{{ $contact->phone }}</span>
                                    </div>
                                @endif

                                @if($contact->email)
                                    <div class="d-flex align-items-center gap-2 text-secondary small mb-1">
                                        <i class="bi bi-envelope"></i>
                                        <span class="text-truncate">{{ $contact->email }}</span>
                                    </div>
                                @endif

                                <div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top">
                                    <span class="fw-bold text-success small">
                                        {{ \App\Support\Money::formatRupiah($contact->total_income) }}
                                    </span>

                                    <div class="d-flex gap-1" onclick="event.stopPropagation();">
                                        @if($contact->phone)
                                            <a href="tel:{{ $contact->phone }}" class="btn btn-sm btn-outline-primary" title="Telepon">
                                                <i class="bi bi-telephone-fill"></i>
                                            </a>
                                        @endif
                                        @if($contact->phone && $contact->has_whatsapp)
                                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $contact->phone)) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success" title="WhatsApp">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        @endif
                                        @if($contact->email)
                                            <a href="mailto:{{ $contact->email }}" class="btn btn-sm btn-outline-secondary" title="Email">
                                                <i class="bi bi-envelope-fill"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                @empty

                    <div class="col-12">
                        <div class="text-center text-muted py-5">
                            @if(!empty($filters['search']) || !empty($filters['letter']))
                                Tidak ada kontak yang cocok dengan filter saat ini.
                            @else
                                Belum ada data kontak.
                            @endif
                        </div>
                    </div>

                @endforelse

            </div>

        </div>

        @if($contacts->hasPages())
            <div class="card-footer bg-white">
                {{ $contacts->links() }}
            </div>
        @endif

    </div>

</div>

@endsection
