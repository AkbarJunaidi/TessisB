@extends('layouts.app')

@section('title', 'Lokasi')

@section('content')
@php
    // Permission dihitung sekali, dipakai di tiap baris.
    $canManage  = auth()->user()->hasPermission('inventory', 'manage_locations');
    $jenisColor = ['kantor' => 'primary', 'gudang' => 'info', 'event' => 'warning', 'lainnya' => 'secondary'];

    $totalPerluKembali = collect($stats['per_location'])->sum('perlu_kembali');
@endphp

<div class="container-fluid px-4 py-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Lokasi</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}" class="text-decoration-none">Inventory</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Lokasi</li>
                </ol>
            </nav>
        </div>

        @if($canManage)
            <button type="button" class="btn btn-primary px-3" id="btnAddLocation">
                <i class="bi bi-plus-lg me-1"></i> Tambah Lokasi
            </button>
        @endif
    </div>

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Lokasi</div>
                    <div class="fs-4 fw-bold">{{ $locations->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Unit di Lapangan</div>
                    <div class="fs-4 fw-bold">{{ $stats['di_lapangan'] }}</div>
                    <div class="text-muted small">sedang dipinjam lewat Surat Jalan</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body">
                    <div class="text-muted small">Belum di Lokasi Utama</div>
                    <div class="fs-4 fw-bold">{{ $totalPerluKembali }}</div>
                    <div class="text-muted small">unit yang perlu dikembalikan ke lokasi utamanya</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm mb-4 border-0 rounded-3 bg-white">
        <div class="card-body">
            <form action="{{ route('inventory.locations.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label for="filterSearch" class="form-label small fw-semibold text-muted">Cari Lokasi</label>
                    <input type="text" class="form-control" id="filterSearch" name="search"
                           value="{{ $filters['search'] ?? '' }}" placeholder="Nama atau alamat...">
                </div>
                <div class="col-md-3">
                    <label for="filterJenis" class="form-label small fw-semibold text-muted">Jenis</label>
                    <select class="form-select" id="filterJenis" name="jenis">
                        <option value="">Semua jenis</option>
                        @foreach(\App\Models\Location::JENIS as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['jenis'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <a href="{{ route('inventory.locations.index') }}" class="btn btn-outline-secondary px-3">Reset</a>
                    <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search me-1"></i>Cari</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar --}}
    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-stack align-middle mb-0 text-nowrap">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Lokasi</th>
                            <th>Jenis</th>
                            <th>Alamat</th>
                            <th>Koordinat</th>
                            <th>Radius</th>
                            <th class="text-center">Unit di Lokasi</th>
                            <th class="text-center">Belum di Lokasi Utama</th>
                            <th>Status</th>
                            @if($canManage)
                                <th class="pe-4 text-end">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="small text-dark">
                        @forelse($locations as $loc)
                            @php
                                $st = $stats['per_location'][$loc->id] ?? ['di_lokasi' => 0, 'tersedia' => 0, 'utama' => 0, 'perlu_kembali' => 0];
                                $color = $jenisColor[$loc->jenis] ?? 'secondary';
                            @endphp
                            <tr>
                                <td class="ps-4 py-3 fw-semibold" data-label="Lokasi">
                                    <i class="bi bi-geo-alt me-1 text-secondary"></i>{{ $loc->name }}
                                    @if($loc->is_default)
                                        <span class="badge bg-light text-dark border ms-1 fw-normal">Lokasi awal</span>
                                    @endif
                                </td>
                                <td data-label="Jenis">
                                    <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} border border-{{ $color }}-subtle px-2 py-2 fw-medium">{{ $loc->jenis_label }}</span>
                                </td>
                                <td data-label="Alamat" class="text-wrap" style="min-width: 180px;">{{ $loc->address ?: '-' }}</td>
                                <td data-label="Koordinat">
                                    @if($loc->hasCoordinates())
                                        <a href="{{ $loc->mapsUrl() }}" target="_blank" rel="noopener" class="text-decoration-none">
                                            <i class="bi bi-map me-1"></i>{{ $loc->coordinateText() }}
                                        </a>
                                    @else
                                        <span class="text-muted">Belum diisi</span>
                                    @endif
                                </td>
                                <td data-label="Radius">{{ $loc->hasCoordinates() ? $loc->radius_m . ' m' : '-' }}</td>
                                <td class="text-center" data-label="Unit di Lokasi">
                                    @if($loc->can_store_units)
                                        {{ $st['di_lokasi'] }}
                                        <span class="text-muted">({{ $st['tersedia'] }} tersedia)</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center" data-label="Belum di Lokasi Utama">
                                    @if($loc->can_store_units && $st['perlu_kembali'] > 0)
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle px-2 py-2">{{ $st['perlu_kembali'] }} unit</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    @if($loc->is_active)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-2">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-2 py-2">Nonaktif</span>
                                    @endif
                                    @if($loc->can_store_units)
                                        <span class="badge bg-light text-dark border ms-1 fw-normal">Penyimpanan unit</span>
                                    @endif
                                </td>
                                @if($canManage)
                                    <td class="pe-4 text-end" data-label="Aksi">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-location"
                                                data-location="{{ json_encode($loc->toFormArray()) }}"
                                                title="Ubah" aria-label="Ubah lokasi">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @unless($loc->is_default)
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-location"
                                                    data-url="{{ route('inventory.locations.destroy', $loc) }}"
                                                    data-name="{{ $loc->name }}"
                                                    title="Hapus" aria-label="Hapus lokasi">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endunless
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 9 : 8 }}" class="text-center py-5 text-muted">
                                    <i class="bi bi-geo-alt fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    Belum ada lokasi yang cocok dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if($canManage)
    {{-- Modal Tambah / Ubah Lokasi (satu form, mode diatur JS) --}}
    <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down modal-dialog-scrollable">
            <form id="locationForm" method="POST" action="{{ route('inventory.locations.store') }}" class="modal-content border-0 shadow"
                  data-store-url="{{ route('inventory.locations.store') }}"
                  data-update-url="{{ route('inventory.locations.update', '__ID__') }}"
                  data-search-url="{{ route('inventory.locations.search') }}">
                @csrf
                <input type="hidden" name="_method" value="PATCH" id="locationMethod" disabled>
                <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}" id="locationEditId">

                <div class="modal-header border-0 bg-light py-3">
                    <h5 class="modal-title fw-semibold" id="locationModalTitle">Tambah Lokasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body py-4">
                    @if($errors->any())
                        <div class="alert alert-danger small py-2">{{ $errors->first() }}</div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-8 position-relative">
                            <label for="locName" class="form-label fw-medium text-secondary">Nama Lokasi</label>
                            <input type="text" class="form-control" id="locName" name="name" value="{{ old('name') }}"
                                   required maxlength="150" autocomplete="off" placeholder="Mis. Kantor A, Gedung Serbaguna X">
                            {{-- Saran lokasi yang sudah ada (pola sama dengan autocomplete Client) --}}
                            <div id="locSuggestions" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1056; max-height: 240px; overflow-y: auto;"></div>
                            <div class="form-text small d-none" id="locSuggestHint">Sudah ada lokasi serupa. Pilih salah satu untuk mengubahnya, atau lanjut mengetik untuk menambah lokasi baru.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="locJenis" class="form-label fw-medium text-secondary">Jenis</label>
                            <select class="form-select" id="locJenis" name="jenis" required>
                                @foreach(\App\Models\Location::JENIS as $value => $label)
                                    <option value="{{ $value }}" {{ old('jenis', 'kantor') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="locAddress" class="form-label fw-medium text-secondary">Alamat <span class="fw-normal text-muted">(opsional)</span></label>
                            <textarea class="form-control" id="locAddress" name="address" rows="2" maxlength="500">{{ old('address') }}</textarea>
                        </div>

                        <div class="col-md-8">
                            <label for="locKoordinat" class="form-label fw-medium text-secondary">Koordinat <span class="fw-normal text-muted">(opsional)</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="locKoordinat" name="koordinat" value="{{ old('koordinat') }}"
                                       placeholder="-7.2575, 112.7521" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" id="btnUseMyLocation">
                                    <i class="bi bi-crosshair me-1"></i> Pakai lokasi saya
                                </button>
                            </div>
                            <div class="form-text small" id="locGpsStatus">
                                Tempel koordinat atau tautan Google Maps, atau tekan tombol di atas saat Anda berada di lokasi.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="locRadius" class="form-label fw-medium text-secondary">Radius Deteksi (m)</label>
                            <input type="number" class="form-control" id="locRadius" name="radius_m" min="10" max="5000"
                                   value="{{ old('radius_m', \App\Models\Location::DEFAULT_RADIUS_M) }}">
                            <div class="form-text small">Default 100 m.</div>
                        </div>

                        <div class="col-12">
                            <label for="locNotes" class="form-label fw-medium text-secondary">Catatan <span class="fw-normal text-muted">(opsional)</span></label>
                            <textarea class="form-control" id="locNotes" name="notes" rows="2" maxlength="1000">{{ old('notes') }}</textarea>
                        </div>

                        <div class="col-12">
                            {{-- Input tersembunyi bernilai 0 di depan checkbox: kotak yang tidak dicentang tetap terkirim sebagai false. --}}
                            <input type="hidden" name="can_store_units" value="0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="can_store_units" value="1" id="locCanStore" {{ old('can_store_units', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="locCanStore">Dapat dipakai untuk menyimpan unit barang (kantor / gudang)</label>
                            </div>

                            <input type="hidden" name="is_default" value="0">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="locIsDefault" {{ old('is_default') ? 'checked' : '' }}>
                                <label class="form-check-label" for="locIsDefault">Jadikan lokasi awal untuk unit baru</label>
                            </div>

                            <input type="hidden" name="is_active" value="0">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="locIsActive" {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="locIsActive">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal konfirmasi hapus --}}
    <div class="modal fade" id="deleteLocationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="deleteLocationForm" method="POST" class="modal-content border-0 shadow">
                @csrf
                @method('DELETE')
                <div class="modal-header border-0 bg-light py-3">
                    <h5 class="modal-title fw-semibold text-danger">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="mb-3">Apakah Anda yakin ingin menghapus lokasi ini?</p>
                    <div class="p-2 bg-light rounded border text-truncate">
                        <strong class="text-secondary small">Nama Lokasi: </strong>
                        <span id="deleteLocationName" class="fw-medium text-dark"></span>
                    </div>
                    <div class="form-text small mt-2">Lokasi yang masih dipakai unit tidak dapat dihapus.</div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">Delete</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Dimuat lewat stack agar berjalan SETELAH Bootstrap JS (layout memuatnya di akhir body). --}}
    @push('scripts')
    <script>
        (function () {
            'use strict';

            const form       = document.getElementById('locationForm');
            const modalEl    = document.getElementById('locationModal');
            const titleEl    = document.getElementById('locationModalTitle');
            const methodEl   = document.getElementById('locationMethod');
            const editIdEl   = document.getElementById('locationEditId');
            const nameEl     = document.getElementById('locName');
            const jenisEl    = document.getElementById('locJenis');
            const addressEl  = document.getElementById('locAddress');
            const coordEl    = document.getElementById('locKoordinat');
            const radiusEl   = document.getElementById('locRadius');
            const notesEl    = document.getElementById('locNotes');
            const canStoreEl = document.getElementById('locCanStore');
            const defaultEl  = document.getElementById('locIsDefault');
            const activeEl   = document.getElementById('locIsActive');
            const gpsStatus  = document.getElementById('locGpsStatus');
            const suggestBox = document.getElementById('locSuggestions');
            const suggestHint = document.getElementById('locSuggestHint');

            const GPS_HELP = 'Tempel koordinat atau tautan Google Maps, atau tekan tombol di atas saat Anda berada di lokasi.';
            let mode = 'create';          // 'create' | 'edit'
            let storeTouched = false;     // user sudah mengubah kotak "menyimpan unit" secara manual
            let debounceTimer = null;

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            /** Teks aman (textContent) untuk menghindari XSS dari nama/alamat lokasi. */
            function el(tag, className, text) {
                const node = document.createElement(tag);
                if (className) node.className = className;
                if (text !== undefined) node.textContent = text;
                return node;
            }

            function hideSuggestions() {
                suggestBox.classList.add('d-none');
                suggestBox.replaceChildren();
                suggestHint.classList.add('d-none');
            }

            /** Jenis kantor/gudang menyimpan unit; event/lainnya tidak (selama user belum mengubahnya sendiri). */
            function applyJenisDefault() {
                if (storeTouched) return;
                canStoreEl.checked = (jenisEl.value === 'kantor' || jenisEl.value === 'gudang');
            }

            function fill(data) {
                nameEl.value     = data.name || '';
                jenisEl.value    = data.jenis || 'kantor';
                addressEl.value  = data.address || '';
                coordEl.value    = data.koordinat || '';
                radiusEl.value   = data.radius_m || 100;
                notesEl.value    = data.notes || '';
                canStoreEl.checked = !!data.can_store_units;
                defaultEl.checked  = !!data.is_default;
                activeEl.checked   = data.is_active !== false;
            }

            function openCreate() {
                mode = 'create';
                storeTouched = false;
                form.action = form.dataset.storeUrl;
                methodEl.disabled = true;
                editIdEl.value = '';
                titleEl.textContent = 'Tambah Lokasi';
                fill({ jenis: 'kantor', radius_m: 100, can_store_units: true, is_active: true });
                gpsStatus.textContent = GPS_HELP;
                hideSuggestions();
                modal.show();
            }

            function openEdit(data) {
                mode = 'edit';
                storeTouched = true;
                form.action = form.dataset.updateUrl.replace('__ID__', data.id);
                methodEl.disabled = false;
                editIdEl.value = data.id;
                titleEl.textContent = 'Ubah Lokasi';
                fill(data);
                gpsStatus.textContent = GPS_HELP;
                hideSuggestions();
                modal.show();
            }

            // ---- tombol Tambah / Ubah / Hapus ----
            const addBtn = document.getElementById('btnAddLocation');
            if (addBtn) addBtn.addEventListener('click', openCreate);

            document.querySelectorAll('.btn-edit-location').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openEdit(JSON.parse(btn.dataset.location));
                });
            });

            document.querySelectorAll('.btn-delete-location').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('deleteLocationForm').action = btn.dataset.url;
                    document.getElementById('deleteLocationName').textContent = btn.dataset.name;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteLocationModal')).show();
                });
            });

            // ---- jenis -> default kotak "menyimpan unit" ----
            jenisEl.addEventListener('change', applyJenisDefault);
            canStoreEl.addEventListener('change', function () { storeTouched = true; });

            // ---- autocomplete nama (mode Tambah): tampilkan lokasi yang sudah ada ----
            nameEl.addEventListener('input', function () {
                if (mode !== 'create') return;

                clearTimeout(debounceTimer);
                const keyword = nameEl.value.trim();

                if (keyword.length < 2) { hideSuggestions(); return; }

                debounceTimer = setTimeout(function () {
                    fetch(form.dataset.searchUrl + '?' + new URLSearchParams({ q: keyword }), {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    })
                        .then(function (res) { return res.ok ? res.json() : { locations: [] }; })
                        .then(function (data) {
                            if (mode !== 'create' || nameEl.value.trim() !== keyword) return; // hasil basi

                            const items = data.locations || [];
                            suggestBox.replaceChildren();

                            if (items.length === 0) { hideSuggestions(); return; }

                            items.forEach(function (loc) {
                                const btn = el('button', 'list-group-item list-group-item-action py-2');
                                btn.type = 'button';
                                btn.appendChild(el('div', 'fw-semibold', loc.name));
                                btn.appendChild(el('div', 'small text-muted',
                                    loc.jenis_label + (loc.address ? ' - ' + loc.address : '')));
                                btn.addEventListener('click', function () { openEdit(loc); });
                                suggestBox.appendChild(btn);
                            });

                            suggestBox.classList.remove('d-none');
                            suggestHint.classList.remove('d-none');
                        })
                        .catch(hideSuggestions);
                }, 250);
            });

            document.addEventListener('click', function (e) {
                if (!suggestBox.contains(e.target) && e.target !== nameEl) hideSuggestions();
            });

            // ---- tombol "Pakai lokasi saya" (GPS perangkat) ----
            document.getElementById('btnUseMyLocation').addEventListener('click', function () {
                if (!('geolocation' in navigator)) {
                    gpsStatus.textContent = 'GPS tidak tersedia di perangkat ini. Isi koordinat secara manual.';
                    return;
                }
                if (!window.isSecureContext) {
                    gpsStatus.textContent = 'GPS hanya bisa dipakai lewat HTTPS. Isi koordinat secara manual.';
                    return;
                }

                gpsStatus.textContent = 'Mengambil lokasi...';

                navigator.geolocation.getCurrentPosition(function (pos) {
                    const lat = pos.coords.latitude.toFixed(7).replace(/0+$/, '').replace(/\.$/, '');
                    const lng = pos.coords.longitude.toFixed(7).replace(/0+$/, '').replace(/\.$/, '');
                    coordEl.value = lat + ', ' + lng;
                    gpsStatus.textContent = 'Koordinat terisi (akurasi sekitar ' + Math.round(pos.coords.accuracy) + ' m).';
                }, function (err) {
                    gpsStatus.textContent = err.code === 1
                        ? 'Izin lokasi ditolak. Isi koordinat secara manual.'
                        : 'Lokasi tidak dapat dibaca. Isi koordinat secara manual.';
                }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
            });

            // ---- validasi server gagal: buka kembali modal dengan input sebelumnya ----
            @if($errors->any() || old('name') !== null)
                document.addEventListener('DOMContentLoaded', function () {
                    const editId = editIdEl.value;
                    mode = editId ? 'edit' : 'create';
                    storeTouched = true;
                    if (editId) {
                        form.action = form.dataset.updateUrl.replace('__ID__', editId);
                        methodEl.disabled = false;
                        titleEl.textContent = 'Ubah Lokasi';
                    }
                    modal.show();
                });
            @endif
        })();
    </script>
    @endpush
@endif
@endsection
