@extends('layouts.app')

@section('title', 'Scan Barang')

@section('content')
<div class="container-fluid p-0">

    <div class="page-heading">
        <h3>Scan Barang</h3>
    </div>

    <div class="app-panel mx-auto u-maxw-560px">
        <div class="p-3 p-md-4">

            <div id="scanLocationBar" class="mb-3"></div>

            {{-- Langkah 1: pilih aksi --}}
            <div id="scanModePicker">
                <div class="scan-mode-grid">
                    <button type="button" class="btn btn-outline-primary scan-mode-btn" data-mode="pinjam">
                        <i class="bi bi-box-arrow-right"></i> Pinjam
                    </button>
                    <button type="button" class="btn btn-outline-primary scan-mode-btn" data-mode="kembalikan">
                        <i class="bi bi-box-arrow-in-left"></i> Kembalikan
                    </button>
                    <button type="button" class="btn btn-outline-danger scan-mode-btn" data-mode="rusak">
                        <i class="bi bi-exclamation-triangle"></i> Rusak
                    </button>
                    <button type="button" class="btn btn-outline-danger scan-mode-btn" data-mode="hilang">
                        <i class="bi bi-question-circle"></i> Hilang
                    </button>
                </div>
            </div>

            {{-- Langkah 2: scan dan hasil, sama untuk 4 aksi --}}
            <div id="scanInputArea" class="d-none">
                <button type="button" id="btnBackToModePicker" class="btn btn-sm btn-link text-decoration-none ps-0 mb-2">
                    <i class="bi bi-arrow-left"></i> Ganti aksi
                </button>

                <button type="button" id="btnOpenCamera" class="btn btn-primary w-100 mb-3">
                    <i class="bi bi-camera me-1"></i> Buka kamera
                </button>

                <div id="scanCameraWrapper" class="d-none mb-3">
                    <div class="btn-group w-100 mb-2" role="group" aria-label="Pilih kamera">
                        <button type="button" class="btn btn-sm btn-primary" data-facing="environment">Kamera belakang</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-facing="user">Kamera depan</button>
                    </div>
                    <div class="u-w-100pct" id="scanCameraReader"></div>
                    <button type="button" id="btnCloseCamera" class="btn btn-sm btn-outline-danger w-100 mt-2">
                        <i class="bi bi-x-circle me-1"></i> Tutup kamera
                    </button>
                </div>

                <label for="scanSerialInput" class="form-label">Serial number</label>
                <div class="input-group mb-3">
                    <input type="text" id="scanSerialInput" class="form-control"
                           placeholder="Scan atau ketik serial number" autocomplete="off" enterkeyhint="search">
                    <button type="button" id="btnScanLookup" class="btn btn-outline-primary" aria-label="Cari">
                        <i class="bi bi-search"></i>
                    </button>
                </div>

                <div id="scanResultArea"></div>
            </div>

        </div>
    </div>
</div>

{{-- Library kamera QR (html5-qrcode 2.3.8): lokal bila sudah diunduh, selain itu CDN. --}}
{{ \App\Support\VendorAsset::script('html5-qrcode') }}

@include('inventory.partials.location-picker')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const modePicker = document.getElementById('scanModePicker');
    const inputArea = document.getElementById('scanInputArea');
    const backBtn = document.getElementById('btnBackToModePicker');
    const serialInput = document.getElementById('scanSerialInput');
    const lookupBtn = document.getElementById('btnScanLookup');
    const resultArea = document.getElementById('scanResultArea');

    const openCameraBtn = document.getElementById('btnOpenCamera');
    const closeCameraBtn = document.getElementById('btnCloseCamera');
    const cameraWrapper = document.getElementById('scanCameraWrapper');
    let html5QrCode = null;
    const facingButtons = document.querySelectorAll('[data-facing]');
    // 'environment' = kamera belakang, 'user' = kamera depan; pilihan terakhir diingat.
    let cameraFacing = loadFacing();
    let currentMode = null;
    let pinjamData = null;
    let isSubmitting = false;

    const locationPicker = LocationPicker.mount(document.getElementById('scanLocationBar'), {
        detectUrl: '{{ route('inventory.locations.detect') }}',
        locations: {{ \Illuminate\Support\Js::from($storageLocations->map(fn ($l) => ['id' => $l->id, 'name' => $l->name])->values()) }},
    });
    locationPicker.onChange(syncActionState);

    // Halaman penuh: mulai dari pilihan mode dan cari lokasi saat halaman dibuka.
    resetToModePicker();
    locationPicker.start();
    setFacing(cameraFacing);
    window.addEventListener('pagehide', stopCamera);

    // Kembali ke pilihan aksi: bersihkan input, hasil, dan matikan kamera.
    function resetToModePicker() {
        currentMode = null;
        pinjamData = null;
        modePicker.classList.remove('d-none');
        inputArea.classList.add('d-none');
        serialInput.value = '';
        resultArea.innerHTML = '';
        cameraWrapper.classList.add('d-none');
        stopCamera();
    }

    document.querySelectorAll('.scan-mode-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            currentMode = this.dataset.mode;
            modePicker.classList.add('d-none');
            inputArea.classList.remove('d-none');
            serialInput.focus();
        });
    });

    backBtn.addEventListener('click', resetToModePicker);

    serialInput.addEventListener('keydown', function (e) {
        // Scanner barcode fisik berperilaku seperti keyboard: ketik teks
        // lalu otomatis tekan Enter - ini yang men-trigger lookup.
        if (e.key === 'Enter') {
            e.preventDefault();
            doLookup();
        }
    });

    lookupBtn.addEventListener('click', doLookup);

    openCameraBtn.addEventListener('click', function () {
        cameraWrapper.classList.remove('d-none');
        startCamera();
    });

    closeCameraBtn.addEventListener('click', function () {
        stopCamera();
        cameraWrapper.classList.add('d-none');
    });

    // Baca pilihan kamera tersimpan; mode privat atau storage dimatikan memakai kamera belakang.
    function loadFacing() {
        try { return localStorage.getItem('scanCameraFacing') === 'user' ? 'user' : 'environment'; } catch (e) { return 'environment'; }
    }

    // Tandai tombol kamera aktif dan simpan pilihannya.
    function setFacing(facing) {
        cameraFacing = facing;
        facingButtons.forEach(function (b) {
            const on = b.dataset.facing === facing;
            b.classList.toggle('btn-primary', on);
            b.classList.toggle('btn-outline-primary', !on);
        });
        try { localStorage.setItem('scanCameraFacing', facing); } catch (e) { /* tidak disimpan */ }
    }

    // Ganti kamera: hentikan stream lama dulu supaya perangkat tidak menolak stream baru.
    facingButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.dataset.facing === cameraFacing) return;
            setFacing(btn.dataset.facing);
            stopCamera().then(startCamera);
        });
    });

    // Nyalakan kamera sesuai pilihan; hasil scan masuk ke kolom serial lalu langsung dicari.
    function startCamera() {
        html5QrCode = new Html5Qrcode('scanCameraReader');

        html5QrCode.start(
            { facingMode: cameraFacing },
            { fps: 10, qrbox: { width: 220, height: 220 } },
            function (decodedText) {
                serialInput.value = decodedText;
                stopCamera();
                cameraWrapper.classList.add('d-none');
                doLookup();
            },
            function () { /* frame tanpa QR: normal */ }
        ).catch(function () {
            const frontFailed = cameraFacing === 'user';
            if (frontFailed) setFacing('environment');
            resultArea.innerHTML = '<div class="alert alert-warning mb-0">' +
                (frontFailed ? 'Kamera depan tidak tersedia.' : 'Tidak bisa mengakses kamera. Pastikan browser diberi izin kamera.') + '</div>';
            cameraWrapper.classList.add('d-none');
        });
    }

    // Hentikan kamera; mengembalikan Promise agar pemanggil bisa menunggu sampai benar-benar mati.
    function stopCamera() {
        const active = html5QrCode;
        html5QrCode = null;
        return active ? active.stop().catch(function () {}) : Promise.resolve();
    }

    // Cari barang berdasarkan serial number untuk mode aktif lalu tampilkan hasilnya.
    function doLookup() {
        const serial = serialInput.value.trim();

        if (!serial) {
            return;
        }

        resultArea.innerHTML = '<div class="text-center text-muted py-2"><span class="spinner-border spinner-border-sm me-2"></span>Mencari...</div>';

        const url = `{{ route('inventory.scan-lookup') }}?serial_number=${encodeURIComponent(serial)}&mode=${currentMode}`;

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then((res) => res.json())
            .then(renderResult)
            .catch(() => {
                resultArea.innerHTML = '<div class="alert alert-danger mb-0">Terjadi kesalahan, silakan coba lagi.</div>';
            });
    }

    // Escape teks agar aman disisipkan ke HTML.
    function esc(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    // Tampilkan peringatan di atas hasil.
    function showWarning(message) {
        resultArea.insertAdjacentHTML('afterbegin', '<div class="alert alert-warning">' + esc(message) + '</div>');
    }

    // Tombol konfirmasi aktif hanya setelah lokasi siap (terdeteksi atau dipilih manual).
    function syncActionState() {
        const ready = locationPicker.value() !== null;

        ['btnConfirmScanPinjam', 'btnConfirmScanReturn'].forEach(function (id) {
            const btn = document.getElementById(id);
            if (btn && !isSubmitting) { btn.disabled = !ready; }
        });

        renderLocationNote();
    }

    // Tampilkan lokasi yang akan dicatat untuk aksi ini.
    function renderLocationNote() {
        const noteEl = document.getElementById('scanActionLocNote');
        if (!noteEl) { return; }

        const name = locationPicker.name();
        let text = '';
        let warn = false;

        if (!name) {
            text = locationPicker.isBusy() ? 'Menunggu lokasi saat ini...' : 'Pilih lokasi terlebih dahulu untuk melanjutkan.';
        } else if (currentMode === 'pinjam' && pinjamData) {
            const qty = parseInt((document.getElementById('scanPinjamQty') || {}).value, 10) || 0;
            const here = (pinjamData.available_by_location || []).find(function (l) { return l.id === locationPicker.locationId(); });
            const atLocation = here ? here.qty : 0;

            if (qty > atLocation) {
                text = (qty - atLocation) + ' unit tercatat di lokasi lain dan lokasinya akan dikoreksi ke ' + name + '.';
                warn = true;
            } else {
                text = 'Unit diambil dari ' + name + '.';
            }
        } else if (currentMode === 'kembalikan') {
            text = 'Unit akan dikembalikan ke ' + name + '.';
        } else if (currentMode === 'rusak') {
            text = 'Unit ditandai Rusak dan tercatat berada di ' + name + '.';
        } else if (currentMode === 'hilang') {
            text = 'Lokasi unit tidak diubah; ' + name + ' dicatat sebagai tempat pelaporan.';
        }

        noteEl.textContent = text;
        noteEl.className = 'small mb-2 ' + (warn ? 'text-warning-emphasis' : 'text-muted');
    }

    // Render hasil lookup sesuai mode: pinjam atau kembalikan/rusak/hilang.
    function renderResult(data) {
        pinjamData = null;

        if (!data.found) {
            resultArea.innerHTML = `
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Barang dengan serial number ini tidak ditemukan.
                </div>`;
            return;
        }

        if (data.mode === 'pinjam') {
            renderPinjamForm(data);
            return;
        }

        renderUnitPicker(data.inventory.name, data.borrowed_units, data.mode);
    }

    // Mode Pinjam: qty dibatasi ke stok tersedia; stok 0 hanya menampilkan siapa yang sedang meminjam.
    function renderPinjamForm(data) {
        if (data.available_qty === 0) {
            const daftarPeminjam = data.borrowed_units.length
                ? data.borrowed_units.map((u) => `<li>Unit #${esc(u.unit_number)} - ${esc(u.referensi)}</li>`).join('')
                : '<li class="text-muted">Tidak ada info peminjam.</li>';

            resultArea.innerHTML = `
                <div class="alert alert-warning mb-2">
                    <strong>${esc(data.inventory.name)}</strong> - stok tersedia 0, sedang dipinjam semua.
                </div>
                <div class="small text-muted mb-1">Sedang dipinjam oleh:</div>
                <ul class="small ps-3 mb-0">${daftarPeminjam}</ul>
            `;
            return;
        }

        pinjamData = data;

        resultArea.innerHTML = `
            <div class="alert alert-secondary">
                <strong>${esc(data.inventory.name)}</strong> - stok tersedia: <strong>${esc(data.available_qty)}</strong> unit.
            </div>
            <label for="scanPinjamQty" class="form-label small fw-semibold text-muted">Jumlah mau dipinjam</label>
            <input type="number" id="scanPinjamQty" class="form-control mb-2" min="1" max="${Number(data.available_qty)}" value="1">
            <div id="scanActionLocNote" class="small text-muted mb-2"></div>
            <button type="button" id="btnConfirmScanPinjam" class="btn btn-primary w-100" disabled>
                <i class="bi bi-check-circle me-1"></i> Pinjam Sekarang
            </button>
        `;

        document.getElementById('scanPinjamQty').addEventListener('input', renderLocationNote);

        document.getElementById('btnConfirmScanPinjam').addEventListener('click', function () {
            const qtyInput = document.getElementById('scanPinjamQty');
            const qty = parseInt(qtyInput.value, 10);

            if (!qty || qty < 1 || qty > data.available_qty) {
                showWarning('Jumlah tidak valid.');
                return;
            }

            const btn = this;
            btn.disabled = true;
            ServisWarning.confirm([{ inventory_id: data.inventory.id, qty: qty }], locationPicker.locationId(), resultArea)
                .then(function (ok) {
                    btn.disabled = false;
                    if (ok) submitPinjam(data.inventory.id, qty, btn);
                });
        });

        syncActionState();
    }

    // POST JSON dengan CSRF; mengembalikan JSON respons.
    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        }).then(async (res) => {
            const resData = await res.json();
            if (!res.ok) throw new Error(resData.message || 'Gagal memproses.');
            return resData;
        });
    }

    // Kirim pinjam: wajib ada lokasi; servis terlambat dikonfirmasi lebih dulu.
    function submitPinjam(inventoryId, qty, btn) {
        const lokasi = locationPicker.value();

        if (!lokasi) {
            showWarning('Lokasi saat ini belum siap. Tunggu deteksi selesai atau pilih lokasi manual.');
            return;
        }

        isSubmitting = true;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

        postJson('{{ route('inventory.scan.pinjam') }}', Object.assign({ inventory_id: inventoryId, qty: qty }, lokasi))
            .then((resData) => {
                resultArea.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle-fill me-1"></i> Berhasil dipinjam (${esc(resData.nomor)}).
                    </div>`;
                serialInput.value = '';
                serialInput.focus();
            })
            .catch((err) => {
                resultArea.innerHTML = `<div class="alert alert-danger mb-0">${esc(err.message)}</div>`;
            })
            .finally(() => { isSubmitting = false; });
    }

    // Kembalikan/Rusak/Hilang memakai tampilan sama: checkbox per unit (default kosong),
    // beda di teks dan endpoint submit (lihat submitBatchAction).
    function renderUnitPicker(inventoryName, units, mode) {
        if (units.length === 0) {
            resultArea.innerHTML = `
                <div class="alert alert-info mb-0">
                    <strong>${esc(inventoryName)}</strong> ditemukan, tapi barang ini sedang tidak dipinjam di manapun.
                </div>`;
            return;
        }

        const modeText = {
            kembalikan: { info: 'dikembalikan', btn: 'Kembalikan Unit Terpilih' },
            rusak: { info: 'ditandai Rusak', btn: 'Tandai Rusak (Sekaligus Dikembalikan)' },
            hilang: { info: 'ditandai Hilang', btn: 'Tandai Hilang (Sekaligus Dikembalikan)' },
        }[mode];

        const optionsHtml = units.map((unit, idx) => `
            <div class="form-check border rounded-3 p-2 mb-2">
                <input class="form-check-input scan-unit-checkbox" type="checkbox" id="scanUnitChoice${idx}" value="${idx}">
                <label class="form-check-label d-block" for="scanUnitChoice${idx}">
                    <div class="fw-semibold">Unit #${esc(unit.unit_number)}</div>
                    <div class="small text-muted">${esc(unit.referensi)} - SJ ${esc(unit.surat_jalan_nomor)}</div>
                </label>
            </div>
        `).join('');

        // Toggle "Pilih Semua" cuma relevan kalau lebih dari 1 unit.
        const selectAllHtml = units.length > 1 ? `
            <div class="form-check mb-2 pb-2 border-bottom">
                <input class="form-check-input" type="checkbox" id="scanUnitSelectAll">
                <label class="form-check-label small fw-semibold" for="scanUnitSelectAll">Pilih Semua</label>
            </div>
        ` : '';

        resultArea.innerHTML = `
            <div class="alert alert-secondary">
                <strong>${esc(inventoryName)}</strong> sedang dipinjam di ${units.length} unit. Pilih yang mau ${modeText.info}:
            </div>
            ${selectAllHtml}
            ${optionsHtml}
            <div id="scanActionLocNote" class="small text-muted mb-2 mt-2"></div>
            <button type="button" id="btnConfirmScanReturn" class="btn btn-primary w-100" disabled>
                <i class="bi bi-check-circle me-1"></i> ${modeText.btn}
            </button>
        `;

        const selectAllCheckbox = document.getElementById('scanUnitSelectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                document.querySelectorAll('.scan-unit-checkbox').forEach((cb) => { cb.checked = this.checked; });
            });
        }

        document.getElementById('btnConfirmScanReturn').addEventListener('click', function () {
            const chosen = Array.from(document.querySelectorAll('.scan-unit-checkbox:checked'))
                .map((cb) => units[Number(cb.value)]);

            if (chosen.length === 0) {
                showWarning('Pilih minimal 1 unit.');
                return;
            }

            submitBatchAction(chosen, mode);
        });

        syncActionState();
    }

    // Endpoint beda per mode (inventory.scan.*) tetapi 1x POST; unit_ids boleh campuran project
    // (ditangani SuratJalanService).
    function submitBatchAction(units, mode) {
        const lokasi = locationPicker.value();

        if (!lokasi) {
            showWarning('Lokasi saat ini belum siap. Tunggu deteksi selesai atau pilih lokasi manual.');
            return;
        }

        const btn = document.getElementById('btnConfirmScanReturn');
        isSubmitting = true;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

        const unitIds = units.map((u) => u.unit_id);
        const isStatusMode = mode === 'rusak' || mode === 'hilang';
        const url = isStatusMode
            ? '{{ route('inventory.scan.status') }}'
            : '{{ route('inventory.scan.kembalikan') }}';
        const body = Object.assign(
            isStatusMode
                ? { unit_ids: unitIds, status: mode === 'rusak' ? 'Rusak' : 'Hilang' }
                : { unit_ids: unitIds },
            lokasi
        );

        postJson(url, body)
            .then(() => {
                resultArea.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle-fill me-1"></i> ${unitIds.length} unit berhasil diproses.
                    </div>`;
                serialInput.value = '';
                serialInput.focus();
            })
            .catch((err) => {
                resultArea.innerHTML = `<div class="alert alert-danger mb-0">${esc(err.message)}</div>`;
            })
            .finally(() => { isSubmitting = false; });
    }
});
</script>

@include('inventory.partials.servis-warning-modal')

@endsection
