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

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <form method="GET" class="row g-2 mb-3">
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
                        <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-secondary w-100">
                            Reset
                        </a>
                    </div>
                @endif
            </form>

            <div class="row g-3">

                @forelse($contacts as $contact)

                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body">

                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold text-dark mb-0">
                                        {{ $contact->name }}
                                    </h6>

                                    <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                        <a
                                            href="{{ route('contacts.edit', $contact) }}"
                                            class="btn btn-sm btn-warning"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form
                                            action="{{ route('contacts.destroy', $contact) }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Yakin ingin menghapus kontak ini?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                @if($contact->company)
                                    <div class="d-flex align-items-center gap-2 text-secondary small mb-2">
                                        <i class="bi bi-building"></i>
                                        <span>{{ $contact->company }}</span>
                                    </div>
                                @endif

                                <div class="d-flex align-items-center gap-2 small mb-1">
                                    <i class="bi bi-whatsapp text-success"></i>
                                    <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $contact->phone)) }}" target="_blank" rel="noopener" class="text-decoration-none">
                                        {{ $contact->phone }}
                                    </a>
                                </div>

                                @if($contact->email)
                                    <div class="d-flex align-items-center gap-2 text-secondary small mb-1">
                                        <i class="bi bi-envelope"></i>
                                        <span>{{ $contact->email }}</span>
                                    </div>
                                @endif

                                @if($contact->address)
                                    <div class="d-flex align-items-start gap-2 text-secondary small mt-2 pt-2 border-top">
                                        <i class="bi bi-geo-alt mt-1"></i>
                                        <span>{{ $contact->address }}</span>
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>

                @empty

                    <div class="col-12">
                        <div class="text-center text-muted py-5">
                            @if(!empty($filters['search']))
                                Tidak ada kontak yang cocok dengan pencarian "{{ $filters['search'] }}".
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
