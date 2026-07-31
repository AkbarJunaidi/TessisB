@extends('layouts.app')

@section('title', 'Detail Surat Jalan')

@section('content')
<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Surat Jalan {{ $suratJalan->nomor }}</h3>
            <p class="text-muted small m-0">
                Project: <a href="{{ route('projects.show', $suratJalan->project) }}">{{ $suratJalan->project->name }}</a>
                &middot; Status:
                <span class="badge {{ $suratJalan->status === 'Selesai' ? 'bg-secondary' : 'bg-success' }}">{{ $suratJalan->status }}</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('surat-jalan.preview', $suratJalan) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye"></i> Preview PDF
            </a>
            <a href="{{ route('surat-jalan.download', $suratJalan) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-download"></i> Download
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-1">Daftar Barang</h6>
            @if($suratJalan->keperluan)
                <p class="text-muted small mb-3">Keperluan: <span class="fw-semibold text-dark">{{ $suratJalan->keperluan }}</span></p>
            @endif
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr class="text-muted small">
                            <th>Barang</th>
                            <th class="text-center">Dipakai</th>
                            <th class="text-center">Dikembalikan</th>
                            <th class="text-center">Sisa</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suratJalan->items as $item)
                            @php $sisa = $item->qty_dipakai - $item->qty_dikembalikan; @endphp
                            <tr>
                                <td>{{ $item->inventory->name }}</td>
                                <td class="text-center">{{ $item->qty_dipakai }}</td>
                                <td class="text-center">{{ $item->qty_dikembalikan }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $sisa > 0 ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">{{ $sisa }}</span>
                                </td>
                                <td class="text-end">
                                    @if($sisa > 0 && auth()->user()->hasPermission('surat_jalan', 'create'))
                                        <form action="{{ route('surat-jalan.items.return', $item) }}" method="POST" class="d-inline-flex gap-1">
                                            @csrf
                                            <input type="number" name="qty" min="1" max="{{ $sisa }}" value="{{ $sisa }}" class="form-control form-control-sm" style="width:70px;">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Kembalikan</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
