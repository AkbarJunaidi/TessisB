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
                Buku alamat client &amp; vendor.
            </p>
        </div>

        <a
            href="{{ route('contacts.create', array_filter(['type' => $filters['type'] ?? null])) }}"
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

    @php
        $typeTabs = [
            ''       => ['Semua', $stats['total']],
            'client' => ['Client', $stats['clients']],
            'vendor' => ['Vendor', $stats['vendors']],
        ];
        $hasFilter = filled($filters['search'] ?? null) || filled($filters['letter'] ?? null) || filled($filters['type'] ?? null);
        $currentLetter = preg_match('/^[A-Za-z]$/', $filters['letter'] ?? '') ? strtoupper($filters['letter']) : '';
        $letterOptions = collect($letters)
            ->when($currentLetter !== '' && !in_array($currentLetter, $letters, true), fn ($c) => $c->push($currentLetter)->sort())
            ->values();
    @endphp

    {{-- Kartu filter terpisah: tipe (dengan jumlah), pencarian, dan huruf awal nama. --}}
    <div class="card shadow-sm mb-4 border-0 rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-funnel me-2"></i>Filter Kontak</h6>
        </div>
        <div class="card-body bg-light bg-opacity-25">

            <ul class="nav nav-pills mb-3">
                @foreach($typeTabs as $typeKey => $typeTab)
                    <li class="nav-item">
                        <a
                            class="nav-link py-1 {{ ($filters['type'] ?? '') === (string) $typeKey ? 'active' : '' }}"
                            href="{{ route('contacts.index', array_merge($filters, ['type' => $typeKey ?: null])) }}"
                        >
                            {{ $typeTab[0] }}
                            <span class="badge bg-light text-dark border ms-1">{{ number_format($typeTab[1]) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <form method="GET" action="{{ route('contacts.index') }}">
                @if(!empty($filters['type']))
                    <input type="hidden" name="type" value="{{ $filters['type'] }}">
                @endif

                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="contactSearch" class="form-label small fw-semibold text-muted">Cari Kontak</label>
                        <input
                            type="text"
                            id="contactSearch"
                            name="search"
                            class="form-control form-control-sm"
                            placeholder="Nama, perusahaan, No. HP/WA, atau email..."
                            value="{{ $filters['search'] ?? '' }}"
                        >
                    </div>

                    <div class="col-md-3">
                        <label for="contactLetter" class="form-label small fw-semibold text-muted">Huruf Awal Nama</label>
                        <select id="contactLetter" name="letter" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Huruf</option>
                            @foreach($letterOptions as $letter)
                                <option value="{{ $letter }}" @selected($currentLetter === $letter)>{{ $letter }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 d-flex justify-content-md-end gap-2">
                        @if($hasFilter)
                            <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-secondary px-3 fw-medium">Reset</a>
                        @endif
                        <button type="submit" class="btn btn-sm btn-primary px-3 fw-medium">
                            <i class="bi bi-search me-1"></i>Cari
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark">{{ $hasFilter ? 'Hasil Filter' : 'Kontak Terbaru' }}</h6>
            <span class="badge bg-secondary text-white fw-medium rounded-pill px-3">{{ number_format($contacts->total()) }} kontak</span>
        </div>

        <div class="card-body">

            <div class="row g-3">

                @forelse($contacts as $contact)

                    <div class="col-md-6 col-lg-4">
                        <div
                            class="card border-0 shadow-sm rounded-3 h-100 contact-card u-cur-pointer"
                           
                            onclick="window.location='{{ route('contacts.show', $contact) }}'"
                        >
                            <div class="card-body">

                                <div class="d-flex align-items-start gap-2 mb-2">
                                    <div class="rounded-circle bg-{{ $contact->avatar_color }}-subtle text-{{ $contact->avatar_color }} d-flex align-items-center justify-content-center fw-bold flex-shrink-0 u-w-42px u-h-42px u-fs-0p85rem">
                                        {{ $contact->initials }}
                                    </div>
                                    <div class="flex-grow-1 u-minw-0">
                                        <h6 class="fw-bold text-dark mb-0 text-truncate">
                                            {{ $contact->name }}
                                        </h6>
                                        <div class="text-muted small text-truncate">
                                            {{ $contact->company ?? 'Perorangan' }}
                                        </div>
                                        <div class="mt-1">
                                            @if($contact->is_client)
                                                <span class="badge bg-primary-subtle text-primary-emphasis">Client</span>
                                            @endif
                                            @if($contact->is_vendor)
                                                <span class="badge bg-warning-subtle text-warning-emphasis">Vendor</span>
                                            @endif
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
                                    <div class="small">
                                        @if($contact->is_client)
                                            <div class="fw-bold text-success">{{ \App\Support\Money::formatRupiah($contact->total_income) }}</div>
                                        @endif
                                        @if($contact->is_vendor && auth()->user()->hasPermission('purchase', 'view'))
                                            <div class="fw-bold text-warning-emphasis">Beli: {{ \App\Support\Money::formatRupiah($contact->purchase_total ?? 0) }}</div>
                                        @endif
                                    </div>

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
