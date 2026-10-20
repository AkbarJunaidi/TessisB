@extends('layouts.app')

@section('title', 'Perbaikan ' . $repair->code)

@section('content')
@php
    $canContact = auth()->user()->hasPermission('kontak', 'view');
    $statusClass = ['Diservis' => 'warning', 'Selesai' => 'success', 'Dibatalkan' => 'secondary'];
    $color = $statusClass[$repair->status] ?? 'secondary';
    $fmtDate = fn ($d) => $d ? $d->translatedFormat('d M Y') : '-';
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
                    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}" class="text-decoration-none">Inventaris</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory.repairs.index') }}" class="text-decoration-none">Perbaikan Barang</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $repair->code }}</li>
                </ol>
            </nav>
        </div>

        @if($canManage && $repair->isActive())
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-danger d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#cancelRepairModal">
                    <i class="bi bi-x-circle"></i> Batalkan Catatan
                </button>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2 fw-medium" data-bs-toggle="modal" data-bs-target="#completeRepairModal">
                    <i class="bi bi-check2-circle"></i> Selesaikan Perbaikan
                </button>
            </div>
        @endif
    </div>

    @if($errors->any())
        <div class="alert alert-danger small">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if($repair->isOverdue())
        <div class="alert alert-danger small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Estimasi selesai {{ $fmtDate($repair->estimasi_selesai) }} sudah lewat dan barang belum diambil dari {{ $repair->tempat_nama }}.
        </div>
    @endif

    @php
        $repairAt = $repair->status === \App\Models\InventoryRepair::STATUS_DONE ? 2 : 1;
        $repairStopped = $repair->status === \App\Models\InventoryRepair::STATUS_CANCELLED;
        $repairText = match ($repair->status) {
            \App\Models\InventoryRepair::STATUS_ACTIVE => 'Barang sedang diservis di ' . $repair->tempat_nama . '. Setelah barang diambil, tekan Selesaikan Perbaikan lalu isi biayanya.',
            \App\Models\InventoryRepair::STATUS_DONE   => 'Perbaikan selesai dan barang kembali tersedia.',
            default                                       => 'Catatan perbaikan ini dibatalkan.',
        };
    @endphp
    @include('partials.flow-card', [
        'flowTitle' => 'Tahapan Perbaikan',
        'tracks'    => [[
            'badge'  => $repairStopped ? 'Dibatalkan' : null,
            'steps'  => \App\Support\Stepper::steps(
                ['Dicatat', 'Diservis', 'Selesai'],
                $repairAt,
                $repair->status === \App\Models\InventoryRepair::STATUS_DONE,
                $repairStopped,
                [$fmtDate($repair->created_at), $repair->estimasi_selesai ? 'Estimasi ' . $fmtDate($repair->estimasi_selesai) : null, $fmtDate($repair->tanggal_selesai)]
            ),
            'text'   => $repairText,
            'mine'   => $canManage && $repair->isActive(),
        ]],
    ])

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
                        <div class="small u-ws-pre-line">{{ $repair->keluhan }}</div>
                    @endif

                    @if($repair->catatan_hasil)
                        <hr>
                        <div class="small text-muted mb-1">Catatan Hasil</div>
                        <div class="small u-ws-pre-line">{{ $repair->catatan_hasil }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Unit ({{ $repair->items->count() }})</h6>
                    @foreach($repair->items->groupBy('inventory_id') as $group)
                        <div class="{{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                            <div class="mb-2">
                                @if($group->first()->inventory)
                                    <a href="{{ route('inventory.show', $group->first()->inventory) }}" class="text-decoration-none fw-semibold">{{ $group->first()->inventory->name }}</a>
                                @else
                                    <span class="fw-semibold">Barang</span>
                                @endif
                            </div>
                            @include('inventory.repair.partials.unit-groups', ['items' => $group, 'repair' => $repair])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@if($canManage && $repair->isActive())
    {{-- Modal Selesaikan Perbaikan --}}
    <div class="modal fade" id="completeRepairModal" tabindex="-1" aria-labelledby="completeRepairTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <form method="POST" action="{{ route('inventory.repairs.complete', $repair) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="completeRepairTitle">Selesaikan Perbaikan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
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
                            <label for="biayaTotal" class="form-label fw-medium text-secondary">Total Biaya (Rp)</label>
                            <input type="number" id="biayaTotal" class="form-control" min="0" max="{{ config('limits.money_max') }}"
                                   placeholder="Isi total untuk dibagi rata ke semua unit">
                            <div class="form-text">Biaya tiap unit bisa diubah di bawah, total ikut menyesuaikan. Kosongkan bila belum diketahui.</div>
                        </div>

                        <div class="col-12">
                            <div class="form-label fw-medium text-secondary mb-2">Hasil dan Biaya per Unit</div>
                            @foreach($repair->items as $item)
                                <div class="border rounded-3 px-3 py-2 mb-2">
                                    <div class="small mb-2">
                                        <span class="fw-semibold">{{ $item->inventory->name ?? 'Barang' }}</span>
                                        #{{ $item->unit->unit_number ?? '?' }}
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12 col-sm-7">
                                            <select name="hasil[{{ $item->id }}]" class="form-select form-select-sm repair-hasil"
                                                    data-inventory="{{ $item->inventory_id }}" aria-label="Hasil unit">
                                                <option value="Tersedia" @selected(old('hasil.' . $item->id, 'Tersedia') === 'Tersedia')>Kembali Tersedia</option>
                                                <option value="Rusak" @selected(old('hasil.' . $item->id) === 'Rusak')>Rusak (tidak bisa diperbaiki)</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-sm-5">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" name="biaya_item[{{ $item->id }}]" class="form-control repair-unit-biaya"
                                                       min="0" max="{{ config('limits.money_max') }}" value="{{ old('biaya_item.' . $item->id) }}" placeholder="0"
                                                       aria-label="Biaya unit">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            <div class="form-text">Unit yang kembali Tersedia otomatis menghitung ulang jadwal servisnya.</div>
                        </div>

                        <div class="col-12">
                            <div class="form-label fw-medium text-secondary mb-2">Catatan per Kelompok</div>
                            @foreach($repair->items->groupBy('inventory_id') as $inventoryId => $group)
                                @php $name = $group->first()->inventory->name ?? 'Barang'; @endphp
                                <div class="repair-note d-none mb-2" data-inventory="{{ $inventoryId }}" data-hasil="Tersedia">
                                    <label for="catatan_{{ $inventoryId }}_Tersedia" class="form-label small mb-1">{{ $name }}: unit berhasil</label>
                                    <textarea name="catatan[{{ $inventoryId }}][Tersedia]" id="catatan_{{ $inventoryId }}_Tersedia" rows="2" maxlength="500"
                                              class="form-control form-control-sm @error('catatan.' . $inventoryId . '.Tersedia') is-invalid @enderror"
                                              placeholder="Apa yang dikerjakan, garansi, dan sebagainya" disabled>{{ old('catatan.' . $inventoryId . '.Tersedia') }}</textarea>
                                    @error('catatan.' . $inventoryId . '.Tersedia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="repair-note d-none mb-2" data-inventory="{{ $inventoryId }}" data-hasil="Rusak">
                                    <label for="catatan_{{ $inventoryId }}_Rusak" class="form-label small mb-1">{{ $name }}: unit gagal <span class="text-danger">*</span></label>
                                    <textarea name="catatan[{{ $inventoryId }}][Rusak]" id="catatan_{{ $inventoryId }}_Rusak" rows="2" maxlength="500"
                                              class="form-control form-control-sm @error('catatan.' . $inventoryId . '.Rusak') is-invalid @enderror"
                                              placeholder="Penyebab dan alasan tidak bisa diperbaiki" disabled>{{ old('catatan.' . $inventoryId . '.Rusak') }}</textarea>
                                    @error('catatan.' . $inventoryId . '.Rusak')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="col-12">
                            <label for="catatan_hasil" class="form-label fw-medium text-secondary">Catatan Umum</label>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Batalkan Catatan --}}
    <div class="modal fade" id="cancelRepairModal" tabindex="-1" aria-labelledby="cancelRepairTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('inventory.repairs.cancel', $repair) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelRepairTitle">Batalkan Catatan Perbaikan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body small">
                    Gunakan ini bila catatan salah input. Semua unit dikembalikan ke status sebelum dikirim servis,
                    dan catatan tetap tersimpan dengan status Dibatalkan. Untuk perbaikan yang benar-benar terjadi, pakai Selesaikan Perbaikan.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kembali</button>
                    <button type="submit" class="btn btn-danger">Batalkan Catatan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ \App\Support\AppAsset::url('js/inventory/repair-biaya.js') }}"></script>
@endif
@endsection
