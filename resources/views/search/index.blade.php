@extends('layouts.app')

@section('title', 'Hasil Pencarian')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Hasil Pencarian</h3>
        <p class="text-muted mb-0">
            @if($keyword !== '')
                Menampilkan hasil untuk "<span class="fw-semibold text-dark">{{ $keyword }}</span>"
            @else
                Ketik kata kunci di kolom pencarian navbar untuk memulai.
            @endif
        </p>
    </div>

    @if($keyword === '')

        <div class="text-center text-muted py-5">
            <i class="bi bi-search" style="font-size: 2.5rem;"></i>
            <p class="mt-3 mb-0">Belum ada kata kunci yang dicari.</p>
        </div>

    @elseif(empty($results))

        <div class="text-center text-muted py-5">
            <i class="bi bi-search" style="font-size: 2.5rem;"></i>
            <p class="mt-3 mb-0">Tidak ditemukan hasil untuk "{{ $keyword }}".</p>
        </div>

    @else

        @foreach($results as $group)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex align-items-center gap-2 py-3">
                    <i class="bi {{ $group['icon'] }} text-primary"></i>
                    <span class="fw-semibold text-dark">{{ $group['label'] }}</span>
                    <span class="badge bg-light text-muted border ms-1">{{ $group['items']->count() }}</span>
                </div>
                <div class="list-group list-group-flush">

                    @if($group['label'] === 'Project')
                        @foreach($group['items'] as $item)
                            <a href="{{ route('projects.show', $item->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-semibold text-dark">{{ $item->name }}</div>
                                    <div class="text-muted small">{{ $item->client ?: $item->company ?: '-' }}</div>
                                </div>
                                <span class="badge bg-light text-dark border">{{ \App\Models\Project::STATUS_LABELS[$item->status] ?? $item->status }}</span>
                            </a>
                        @endforeach

                    @elseif($group['label'] === 'Inventory')
                        @foreach($group['items'] as $item)
                            <a href="{{ route('inventory.show', $item->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-semibold text-dark">{{ $item->name }}</div>
                                    <div class="text-muted small">{{ $item->serial_number ?: '-' }}{{ $item->brand ? ' &middot; '.$item->brand : '' }}</div>
                                </div>
                                <span class="badge bg-light text-dark border">{{ $item->status }}</span>
                            </a>
                        @endforeach

                    @elseif($group['label'] === 'Kontak')
                        @foreach($group['items'] as $item)
                            <a href="{{ route('contacts.show', $item->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-semibold text-dark">{{ $item->name }}</div>
                                    <div class="text-muted small">{{ $item->company ?: $item->phone ?: $item->email ?: '-' }}</div>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                        @endforeach

                    @elseif($group['label'] === 'Surat Jalan')
                        @foreach($group['items'] as $item)
                            <a href="{{ route('surat-jalan.show', $item->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-semibold text-dark">{{ $item->nomor }}</div>
                                    <div class="text-muted small">{{ $item->project?->name ?? $item->kepada }}</div>
                                </div>
                                <span class="text-muted small">{{ $item->tanggal_terbit?->format('d/m/Y') }}</span>
                            </a>
                        @endforeach
                    @endif

                </div>
            </div>
        @endforeach

    @endif

</div>

@endsection
