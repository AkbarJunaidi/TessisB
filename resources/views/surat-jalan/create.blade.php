@extends('layouts.app')

@section('title', 'Buat Surat Jalan')

@section('content')
<div class="container-fluid p-0">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Buat Surat Jalan</h3>
            <p class="text-muted small m-0">Project: <span class="fw-semibold" title="{{ $project->name }}">{{ $project->short_name }}</span></p>
        </div>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 fw-medium">
            <i class="bi bi-arrow-left"></i> Kembali ke Detail Project
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="m-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('surat-jalan.store', $project) }}" method="POST" id="suratJalanForm">
        @csrf

        <div class="card shadow-sm border-0 rounded-3 mb-3">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Informasi Surat Jalan</h6>

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small text-secondary">Kepada <span class="text-danger">*</span></label>
                        <input type="text" name="kepada" class="form-control" placeholder="Contoh: Bapak Arnold | GKI Sepanjang" value="{{ old('kepada', $project->client) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-secondary">Keperluan Barang <span class="text-danger">*</span></label>
                        <select name="keperluan" class="form-select" required>
                            <option value="">Pilih Keperluan</option>
                            @foreach(['Dokumentasi Video', 'Dokumentasi Foto', 'Dokumentasi Foto & Video', 'Live Streaming', 'Lainnya'] as $keperluanOption)
                                <option value="{{ $keperluanOption }}" @selected(old('keperluan') === $keperluanOption)>{{ $keperluanOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-secondary">PIC <span class="text-danger">*</span></label>
                        <input type="text" name="pic" class="form-control" value="{{ old('pic', $project->pic) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-secondary">Tanggal Terbit <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_terbit" class="form-control" value="{{ old('tanggal_terbit', now()->format('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-secondary">Tanggal Keberangkatan</label>
                        <input type="date" name="tanggal_keberangkatan" class="form-control" value="{{ old('tanggal_keberangkatan') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-secondary">Jam Berangkat</label>
                        <input type="time" name="jam_berangkat" class="form-control" value="{{ old('jam_berangkat') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-secondary">Tanggal Gladi Bersih</label>
                        <input type="date" name="tanggal_gladi_bersih" class="form-control" value="{{ old('tanggal_gladi_bersih') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-secondary">Waktu Gladi Bersih</label>
                        <input type="time" name="waktu_gladi_bersih" class="form-control" value="{{ old('waktu_gladi_bersih') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-secondary">Tanggal Acara Mulai</label>
                        <input type="date" name="tanggal_acara" class="form-control" value="{{ old('tanggal_acara', optional($project->event_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-secondary">Tanggal Acara Selesai</label>
                        <input type="date" name="tanggal_acara_selesai" class="form-control" value="{{ old('tanggal_acara_selesai') }}">
                        <div class="form-text">Kosongkan jika acara hanya 1 hari.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-secondary">Waktu Acara</label>
                        <input type="text" name="waktu_acara" class="form-control" placeholder="Contoh: 08.00 - selesai" value="{{ old('waktu_acara') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary">Lokasi Acara <span class="text-danger">*</span></label>
                        <textarea name="lokasi_acara" rows="2" class="form-control" placeholder="Nama venue + alamat lengkap" required>{{ old('lokasi_acara', trim(($project->location ?? '')."\n".($project->address ?? ''))) }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary">Catatan</label>
                        <textarea name="catatan" rows="2" class="form-control">{{ old('catatan') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3 mb-3">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold m-0">Barang yang Dibawa</h6>
                    <button type="button" id="addItemRow" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Tambah Barang
                    </button>
                </div>

                <div id="itemRows"></div>
                <p class="text-muted small m-0 mt-2" id="noItemMsg">Belum ada barang ditambahkan.</p>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('projects.show', $project) }}" class="btn btn-light px-4 fw-medium">Batal</a>
            <button type="submit" class="btn btn-primary px-4 fw-medium shadow-sm">
                <i class="bi bi-file-earmark-check me-1"></i> Simpan & Generate Surat Jalan
            </button>
        </div>
    </form>

</div>

@php
    $inventoryOptionsForJs = $inventories->map(fn ($inv) => [
        'id' => $inv->id,
        'name' => $inv->name,
        'available' => $inv->qty_available,
    ]);
@endphp
{{-- Data inventory tersedia untuk dipakai JS (name, qty_available) --}}
<script>
    const INVENTORY_OPTIONS = @json($inventoryOptionsForJs);
</script>
<script src="{{ \App\Support\AppAsset::url('js/surat-jalan/create.js') }}"></script>
@include('inventory.partials.servis-warning-modal')
@endsection
