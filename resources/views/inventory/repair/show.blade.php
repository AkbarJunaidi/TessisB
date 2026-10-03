@extends('layouts.app')

@section('title', 'Perbaikan ' . $repair->code)

@section('content')
@php
    $canContact = auth()->user()->hasPermission('kontak', 'view');
    $statusClass = ['Diservis' => 'warning', 'Selesai' => 'success', 'Dibatalkan' => 'secondary'];
    $color = $statusClass[$repair->status] ?? 'secondary';
    $fmtDate = fn ($d) => $d ? $d->format('d M Y') : '-';
@endphp

<div class="container-fluid px-4 py-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">
                {{ $repair->code }}
                <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} border border-{{ $color }}-subtle px-2 py-2 fw-medium fs-6 align-middle">{{ $repair->status }}</span>
            </h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}" class="text-decoration-none">Inventory</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory.repairs.index') }}" class="text-decoration-none">Perbaikan Barang</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $repair->code }}</li>
                </ol>
            </nav>
        </div>

        @if($canManage && $repair->isActive())
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-danger px-3" data-bs-toggle="modal" data-bs-target="#cancelRepairModal">
                    <i class="bi bi-x-circle me-1"></i> Batalkan Catatan
                </button>
                <button type="button" class="btn btn-success px-3" data-bs-toggle="modal" data-bs-target="#completeRepairModal">
                    <i class="bi bi-check2-circle me-1"></i> Selesaikan Perbaikan
                </button>
            </div>
        @endif
    </div>

    @if($repair->isOverdue())
        <div class="alert alert-danger small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Estimasi selesai {{ $fmtDate($repair->estimasi_selesai) }} sudah lewat dan barang belum diambil dari {{ $repair->tempat_nama }}.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Detail</h6>
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted fw-normal">Tempat Servis</dt>
                        <dd class="col-7 fw-semibold">{{ $repair->tempat_nama }}</dd>

                        @if($repair->vendor)
                            <dt class="col-5 text-muted fw-normal">Kontak Vendor</dt>
                            <dd class="col-7">
                                @if($canContact)
                                    <a href="{{ route('contacts.show', $repair->vendor) }}" class="text-decoration-none">{{ $repair->vendor->name }}</a>
                                @else
                                    {{ $repair->vendor->name }}
                                @endif
                                @if($repair->vendor->phone)
                                    <div class="text-muted">{{ $repair->vendor->phone }}</div>
                                @endif
                            </dd>
                        @endif

                        <dt class="col-5 text-muted fw-normal">Alamat</dt>
                        <dd class="col-7">{{ $repair->tempat_alamat ?: '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Tanggal Diantar</dt>
                        <dd class="col-7">{{ $fmtDate($repair->tanggal_masuk) }}</dd>

                        <dt class="col-5 text-muted fw-normal">Diantar Oleh</dt>
                        <dd class="col-7">{{ $repair->diantar_oleh ?: '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Estimasi Selesai</dt>
                        <dd class="col-7">{{ $fmtDate($repair->estimasi_selesai) }}</dd>

                        <dt class="col-5 text-muted fw-normal">Tanggal Diambil</dt>
                        <dd class="col-7">{{ $fmtDate($repair->tanggal_selesai) }}</dd>

                        <dt class="col-5 text-muted fw-normal">Diambil Oleh</dt>
                        <dd class="col-7">{{ $repair->diambil_oleh ?: '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Biaya</dt>
                        <dd class="col-7 fw-semibold">{{ $repair->biaya !== null ? \App\Support\Money::formatRupiah($repair->biaya) : '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Dicatat Oleh</dt>
                        <dd class="col-7">{{ $repair->creator->name ?? '-' }}</dd>

                        @if($repair->completer)
                            <dt class="col-5 text-muted fw-normal">Diselesaikan Oleh</dt>
                            <dd class="col-7">{{ $repair->completer->name }}</dd>
                        @endif
                    </dl>

                    @if($repair->keluhan)
                        <hr>
                        <div class="small text-muted mb-1">Keluhan / Kerusakan</div>
                        <div class="small" style="white-space: pre-line;">{{ $repair->keluhan }}</div>
                    @endif

                    @if($repair->catatan_hasil)
                        <hr>
                        <div class="small text-muted mb-1">Catatan Hasil</div>
                        <div class="small" style="white-space: pre-line;">{{ $repair->catatan_hasil }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Unit ({{ $repair->items->count() }})</h6>
                    <div class="table-responsive">
                        <table class="table table-stack align-middle mb-0">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th>Barang</th>
                                    <th>Unit</th>
                                    <th>Status Sebelumnya</th>
                                    <th>Hasil</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @foreach($repair->items as $item)
                                    <tr>
                                        <td data-label="Barang">
                                            @if($item->inventory)
                                                <a href="{{ route('inventory.show', $item->inventory) }}" class="text-decoration-none fw-semibold">{{ $item->inventory->name }}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td data-label="Unit">#{{ $item->unit->unit_number ?? '?' }}</td>
                                        <td data-label="Status Sebelumnya">{{ $item->status_sebelum }}</td>
                                        <td data-label="Hasil">
                                            @if($item->hasil === 'Tersedia')
                                                <span class="badge-soft-success px-2 py-1 rounded-pill">Kembali Tersedia</span>
                                            @elseif($item->hasil === 'Rusak')
                                                <span class="badge-soft-danger px-2 py-1 rounded-pill">Rusak</span>
                                            @elseif($repair->status === 'Dibatalkan')
                                                <span class="text-muted">Dikembalikan ke status sebelumnya</span>
                                            @else
                                                <span class="badge-soft-warning px-2 py-1 rounded-pill">Diperbaiki di {{ $repair->tempat_nama }}</span>
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
    </div>
</div>

@if($canManage && $repair->isActive())
    {{-- Modal Selesaikan Perbaikan --}}
    <div class="modal fade" id="completeRepairModal" tabindex="-1" aria-labelledby="completeRepairTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <form method="POST" action="{{ route('inventory.repairs.complete', $repair) }}" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 bg-light py-3">
                    <h5 class="modal-title fw-semibold" id="completeRepairTitle">Selesaikan Perbaikan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="tanggal_selesai" class="form-label fw-medium text-secondary">Tanggal Diambil <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control"
                                   value="{{ old('tanggal_selesai', now()->format('Y-m-d')) }}"
                                   min="{{ $repair->tanggal_masuk->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="diambil_oleh" class="form-label fw-medium text-secondary">Diambil Oleh</label>
                            <input type="text" name="diambil_oleh" id="diambil_oleh" class="form-control" maxlength="150"
                                   list="peopleList" value="{{ old('diambil_oleh') }}" placeholder="Pilih akun atau ketik nama">
                        </div>
                        <div class="col-12">
                            <label for="biaya" class="form-label fw-medium text-secondary">Biaya (Rp)</label>
                            <input type="number" name="biaya" id="biaya" class="form-control" min="0" step="1"
                                   value="{{ old('biaya') }}" placeholder="Kosongkan bila belum diketahui">
                        </div>

                        <div class="col-12">
                            <div class="form-label fw-medium text-secondary mb-2">Hasil per Unit</div>
                            @foreach($repair->items as $item)
                                <div class="d-flex justify-content-between align-items-center gap-2 border rounded-3 px-3 py-2 mb-2">
                                    <span class="small">
                                        <span class="fw-semibold">{{ $item->inventory->name ?? 'Barang' }}</span>
                                        #{{ $item->unit->unit_number ?? '?' }}
                                    </span>
                                    <select name="hasil[{{ $item->id }}]" class="form-select form-select-sm w-auto" aria-label="Hasil unit">
                                        <option value="Tersedia" @selected(old('hasil.' . $item->id, 'Tersedia') === 'Tersedia')>Kembali Tersedia</option>
                                        <option value="Rusak" @selected(old('hasil.' . $item->id) === 'Rusak')>Rusak (tidak bisa diperbaiki)</option>
                                    </select>
                                </div>
                            @endforeach
                            <div class="form-text">Unit yang kembali Tersedia otomatis menghitung ulang jadwal servisnya.</div>
                        </div>

                        <div class="col-12">
                            <label for="catatan_hasil" class="form-label fw-medium text-secondary">Catatan Hasil</label>
                            <textarea name="catatan_hasil" id="catatan_hasil" rows="2" class="form-control" maxlength="1000"
                                      placeholder="Apa yang dikerjakan, garansi, dan sebagainya">{{ old('catatan_hasil') }}</textarea>
                        </div>
                    </div>
                    <datalist id="peopleList">
                        @foreach($people as $person)
                            <option value="{{ $person }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Batalkan Catatan --}}
    <div class="modal fade" id="cancelRepairModal" tabindex="-1" aria-labelledby="cancelRepairTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('inventory.repairs.cancel', $repair) }}" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 bg-light py-3">
                    <h5 class="modal-title fw-semibold" id="cancelRepairTitle">Batalkan Catatan Perbaikan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4 small">
                    Gunakan ini bila catatan salah input. Semua unit dikembalikan ke status sebelum dikirim servis,
                    dan catatan tetap tersimpan dengan status Dibatalkan. Untuk perbaikan yang benar-benar terjadi, pakai Selesaikan Perbaikan.
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Kembali</button>
                    <button type="submit" class="btn btn-danger px-4">Batalkan Catatan</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
