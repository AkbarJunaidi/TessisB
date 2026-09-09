@extends('layouts.app')

@section('title', 'Daftar Inventory')

@section('content')

<div class="page-heading">
    <div>
        <h3>Inventory List</h3>
        <p>Kelola dan pantau seluruh data aset barang fisik perusahaan.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        {{-- FITUR: Scan Barcode - cari barang lewat serial number hasil scan
             (kamera atau scanner fisik), lalu kembalikan barang yang sedang
             dipinjam tanpa perlu cari project-nya dulu di halaman Barang
             Pinjaman. --}}
        <button
            type="button"
            id="btnScanBarcode"
            class="btn btn-outline-success d-flex align-items-center gap-2"
            data-bs-toggle="modal"
            data-bs-target="#scanBarcodeModal"
        >
            <i class="bi bi-upc-scan"></i> <span class="d-none d-sm-inline">Scan Barcode</span>
        </button>
        {{-- FITUR: Proses Laporan Massal (Semua Inventaris) bertahap via AJAX, notifikasi saat siap --}}
        <button
            type="button"
            id="btnGenerateAllReport"
            class="btn btn-outline-danger d-flex align-items-center gap-2"
        >
            <i class="bi bi-file-earmark-pdf-fill"></i> <span class="d-none d-sm-inline">Proses Laporan Semua Inventory</span>
        </button>
        <a href="{{ route('inventory.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle"></i> Tambah Inventory
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 rounded-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Form Search & Filter Status --}}
<div class="app-panel mb-4">
    <div class="p-3 p-md-4">
        <form action="{{ route('inventory.index') }}" method="GET" class="row g-3 align-items-center">
            <div class="col-12 col-md-6 col-lg-7">
                <label for="search" class="visually-hidden">Cari inventory</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="search" name="search"
                           class="form-control bg-light border-start-0 ps-0"
                           placeholder="Cari berdasarkan nama barang atau brand..."
                           value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-12 col-md-4 col-lg-3">
                <label for="status" class="visually-hidden">Filter status</label>
                <select name="status" id="status" class="form-select bg-light" onchange="this.form.submit()">
                    <option value="Semua Status" {{ request('status') == 'Semua Status' || !request('status') ? 'selected' : '' }}>Semua Status</option>
                    <option value="Tersedia" {{ request('status') == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                    <option value="Dipinjam" {{ request('status') == 'Dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                    <option value="Perbaikan" {{ request('status') == 'Perbaikan' ? 'selected' : '' }}>Perbaikan</option>
                    <option value="Rusak" {{ request('status') == 'Rusak' ? 'selected' : '' }}>Rusak</option>
                    <option value="Hilang" {{ request('status') == 'Hilang' ? 'selected' : '' }}>Hilang</option>
                </select>
            </div>

            <div class="col-12 col-md-2 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
                @if(request('search') || (request('status') && request('status') !== 'Semua Status'))
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary" title="Reset Filter" aria-label="Reset filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Table Inventory List --}}
<div class="app-panel overflow-hidden">
    <div class="px-3 px-md-4 pt-3 pt-md-4">
        <h6 class="fw-bold mb-3">Semua Barang ({{ $inventories->total() }})</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-modern table-stack align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 10%;">Foto</th>
                    <th style="width: 25%;">Nama Barang</th>
                    <th style="width: 20%;">Serial Number</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 15%;">Tanggal Input</th>
                    <th class="text-center pe-4" style="width: 15%;">Aksi</th>
                </tr>
            </thead>
            <tbody class="small">
                @forelse($inventories as $index => $item)
                    <tr>
                        <td class="ps-4 py-3" data-label="Foto">
                            @if($item->image)
                                <img src="{{ asset('storage/' . $item->image) }}"
                                     alt="Foto {{ $item->name }}"
                                     class="rounded-3 border"
                                     style="width: 56px; height: 46px; object-fit: cover;">
                            @else
                                <div class="bg-light rounded-3 d-flex align-items-center justify-content-center text-muted border"
                                     style="width: 56px; height: 46px; font-size: 0.7rem;">
                                    <i class="bi bi-image opacity-50"></i>
                                </div>
                            @endif
                        </td>

                        <td class="py-3 fw-bold text-dark" data-label="Nama Barang">{{ $item->name }}</td>

                        <td class="py-3 text-secondary" data-label="Serial Number">
                            <span class="badge bg-light text-dark border px-2 py-1 font-monospace fw-medium">
                                {{ $item->serial_number }}
                            </span>
                        </td>

                        {{-- Status Barang - otomatis "Tersedia" jika masih ada unit available (logika tidak diubah) --}}
                        <td class="py-3" data-label="Status">
                            @switch($item->display_status)
                                @case('Tersedia')
                                    <span class="badge-soft-success">Tersedia</span>
                                    @break
                                @case('Dipinjam')
                                    <span class="badge-soft-primary">Dipinjam</span>
                                    @break
                                @case('Perbaikan')
                                    <span class="badge-soft-warning">Perbaikan</span>
                                    @break
                                @case('Rusak')
                                    <span class="badge-soft-danger">Rusak</span>
                                    @break
                                @case('Hilang')
                                    <span class="badge-soft-secondary">Hilang</span>
                                    @break
                                @default
                                    <span class="badge-soft-success">{{ $item->status ?? 'Tersedia' }}</span>
                            @endswitch
                            <div class="small text-muted mt-1">{{ $item->qty_available }}/{{ $item->quantity_total }} unit tersedia</div>
                        </td>

                        <td class="py-3 text-secondary" data-label="Tanggal Input">{{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}</td>

                        <td class="py-3 text-center pe-4 cell-block" data-label="Aksi">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('inventory.show', $item->id) }}"
                                   class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                   title="Lihat Detail">
                                    <i class="bi bi-eye"></i> <span class="d-none d-xl-inline">View</span>
                                </a>
                                <a href="{{ route('inventory.download-pdf', $item->id) }}"
                                   class="btn btn-sm btn-outline-danger"
                                   title="Download Report PDF" aria-label="Download report PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteInventoryModal"
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->name }}"
                                        data-sn="{{ $item->serial_number }}"
                                        title="Hapus" aria-label="Hapus item">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-box-seam"></i></div>
                                <p class="mb-1 fw-bold text-dark">Belum Ada Data Inventory</p>
                                <p class="text-muted small mb-0">Klik tombol "Tambah Inventory" di atas untuk menambahkan barang pertama Anda.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
    <div class="text-muted small">
        Menampilkan {{ $inventories->firstItem() ?? 0 }} - {{ $inventories->lastItem() ?? 0 }} dari {{ $inventories->total() }} inventaris
    </div>
    <div>
        {{ $inventories->links('pagination::bootstrap-5') }}
    </div>
</div>

{{-- Delete Modal Confirmation --}}
<div class="modal fade" id="deleteInventoryModal" tabindex="-1" aria-labelledby="deleteInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="deleteInventoryModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="deleteInventoryForm" method="POST">
                @csrf
                @method('DELETE')

                <div class="modal-body p-4">
                    <p class="text-dark fw-medium mb-3">Apakah Anda yakin ingin menghapus data inventory ini?</p>

                    <div class="bg-light p-3 rounded-3 border">
                        <div class="mb-2">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Nama Barang</small>
                            <span id="modal-inventory-name" class="fw-bold text-dark fs-6">-</span>
                        </div>
                        <div>
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Serial Number</small>
                            <span id="modal-inventory-sn" class="font-monospace fw-semibold text-secondary">-</span>
                        </div>
                    </div>

                    <small class="text-danger d-block mt-3">
                        <i class="bi bi-info-circle me-1"></i>Catatan: Data ini akan dipindahkan ke sistem arsip (Soft Delete).
                    </small>
                </div>

                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const deleteModal = document.getElementById('deleteInventoryModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const sn = button.getAttribute('data-sn');

                document.getElementById('modal-inventory-name').textContent = name;
                document.getElementById('modal-inventory-sn').textContent = sn;
                document.getElementById('deleteInventoryForm').action = `/inventory/${id}`;
            });
        }
    });
</script>

{{-- Modal progres Laporan Massal - lihat InventoryService::processAllReportBatch --}}
<div class="modal fade" id="generateReportModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h6 class="modal-title fw-bold">Memproses Laporan Semua Inventory</h6>
            </div>

            <div class="modal-body">
                <div class="progress mb-2" style="height: 20px;">
                    <div
                        id="generateReportProgressBar"
                        class="progress-bar progress-bar-striped progress-bar-animated"
                        role="progressbar"
                        style="width: 0%"
                    >0%</div>
                </div>
                <div id="generateReportStatusText" class="small text-muted">Memulai...</div>
            </div>

            <div class="modal-footer">
                <a
                    id="generateReportDownloadBtn"
                    href="#"
                    class="btn btn-success d-none"
                >
                    <i class="bi bi-download me-1"></i> Unduh Laporan
                </a>
                <button
                    type="button"
                    id="generateReportCancelBtn"
                    class="btn btn-secondary"
                >Batal</button>
                <button
                    type="button"
                    id="generateReportCloseBtn"
                    class="btn btn-secondary d-none"
                    data-bs-dismiss="modal"
                >Tutup</button>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const startBtn = document.getElementById('btnGenerateAllReport');
        const modalEl = document.getElementById('generateReportModal');
        const modal = new bootstrap.Modal(modalEl);
        const progressBar = document.getElementById('generateReportProgressBar');
        const statusText = document.getElementById('generateReportStatusText');
        const downloadBtn = document.getElementById('generateReportDownloadBtn');
        const cancelBtn = document.getElementById('generateReportCancelBtn');
        const closeBtn = document.getElementById('generateReportCloseBtn');

        // ID laporan yang sedang aktif diproses, dan penanda kalau user
        // menekan Batal - dipakai processBatch() untuk berhenti mengirim
        // request lanjutan begitu dibatalkan.
        let activeReportExportId = null;
        let cancelled = false;
        let activeAbortController = null;

        function setProgress(processed, total) {
            const percent = total > 0 ? Math.round((processed / total) * 100) : 100;
            progressBar.style.width = percent + '%';
            progressBar.textContent = percent + '%';
            statusText.textContent = `Memproses ${processed} dari ${total} barang...`;
        }

        function showFinishedState() {
            cancelBtn.classList.add('d-none');
            closeBtn.classList.remove('d-none');
        }

        function processBatch(reportExportId) {
            if (cancelled) {
                return;
            }

            activeAbortController = new AbortController();

            fetch(`/inventory/report/generate-all/${reportExportId}/batch`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                signal: activeAbortController.signal,
            })
                .then(response => response.json())
                .then(data => {
                    if (cancelled) {
                        return;
                    }

                    setProgress(data.processed, data.total);

                    if (!data.finished) {
                        processBatch(reportExportId);
                        return;
                    }

                    if (data.status === 'completed') {
                        statusText.textContent = 'Laporan selesai diproses.';
                        progressBar.classList.remove('progress-bar-animated');
                        downloadBtn.href = data.download_url;
                        downloadBtn.classList.remove('d-none');
                    } else {
                        statusText.textContent = 'Gagal memproses laporan: ' + (data.error || 'Terjadi kesalahan.');
                        progressBar.classList.remove('progress-bar-animated', 'bg-primary');
                        progressBar.classList.add('bg-danger');
                    }

                    showFinishedState();
                })
                .catch((error) => {
                    // Request dibatalkan lewat AbortController (tombol Batal) -
                    // bukan kegagalan koneksi, jadi tidak perlu dicoba ulang.
                    if (cancelled || error.name === 'AbortError') {
                        return;
                    }

                    statusText.textContent = 'Koneksi terputus, mencoba lagi...';
                    setTimeout(() => processBatch(reportExportId), 2000);
                });
        }

        function cancelActiveReport() {
            cancelled = true;

            if (activeAbortController) {
                activeAbortController.abort();
            }

            const idToCancel = activeReportExportId;
            activeReportExportId = null;

            if (!idToCancel) {
                modal.hide();
                return;
            }

            statusText.textContent = 'Membatalkan...';

            // Beri tahu server supaya file sementara & baris datanya benar-
            // benar dihapus, bukan cuma berhenti di sisi browser saja.
            fetch(`/inventory/report/generate-all/${idToCancel}/cancel`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            }).finally(() => {
                modal.hide();
            });
        }

        if (startBtn) {
            startBtn.addEventListener('click', function () {
                // Reset tampilan & status setiap kali modal dibuka ulang.
                cancelled = false;
                activeReportExportId = null;

                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                progressBar.classList.add('progress-bar-animated');
                progressBar.classList.remove('bg-danger');
                statusText.textContent = 'Memulai...';
                downloadBtn.classList.add('d-none');
                cancelBtn.classList.remove('d-none');
                closeBtn.classList.add('d-none');

                modal.show();

                fetch('/inventory/report/generate-all/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                })
                    .then(response => response.json())
                    .then(data => {
                        if (cancelled) {
                            return;
                        }

                        activeReportExportId = data.report_export_id;
                        setProgress(0, data.total);
                        processBatch(data.report_export_id);
                    })
                    .catch(() => {
                        statusText.textContent = 'Gagal memulai proses laporan.';
                        showFinishedState();
                    });
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', cancelActiveReport);
        }
    });
</script>

{{-- Modal Scan Barcode - cari barang via serial number (scanner fisik atau
     kamera), lalu kembalikan barang yang sedang dipinjam. Proses
     pengembalian sungguhan TETAP lewat endpoint borrowed-items.return yang
     sudah ada (dipanggil lewat fetch di bawah) - modal ini cuma
     antarmuka pencarian & konfirmasi, tidak ada logic bisnis baru. --}}
<div class="modal fade" id="scanBarcodeModal" tabindex="-1" aria-labelledby="scanBarcodeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="scanBarcodeModalLabel">
                    <i class="bi bi-upc-scan me-2"></i>Scan Barcode Barang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">

                <label for="scanSerialInput" class="form-label small fw-semibold text-muted">
                    Serial Number
                </label>
                <div class="input-group mb-2">
                    <input
                        type="text"
                        id="scanSerialInput"
                        class="form-control"
                        placeholder="Scan pakai alat, atau ketik manual lalu Enter"
                        autocomplete="off"
                    >
                    <button type="button" id="btnScanLookup" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                <small class="text-muted d-block mb-3">
                    Scanner barcode fisik otomatis mengetik ke kolom ini lalu Enter. Atau pakai kamera di bawah.
                </small>

                <button type="button" id="btnOpenCamera" class="btn btn-sm btn-outline-secondary w-100 mb-3">
                    <i class="bi bi-camera me-1"></i> Buka Kamera
                </button>

                <div id="scanCameraWrapper" class="d-none mb-3">
                    <div id="scanCameraReader" style="width: 100%;"></div>
                    <button type="button" id="btnCloseCamera" class="btn btn-sm btn-outline-danger w-100 mt-2">
                        <i class="bi bi-x-circle me-1"></i> Tutup Kamera
                    </button>
                </div>

                <div id="scanResultArea"></div>

            </div>
        </div>
    </div>
</div>

{{-- Library kamera QR Code (html5-qrcode v2.3.8, stable, dipakai 135+
     project - dimuat dari jsDelivr). Catatan: tidak diberi atribut
     integrity/SRI karena hash-nya tidak bisa diverifikasi dari sandbox
     tempat kode ini ditulis - kalau mau lebih aman, tambahkan SRI hash
     resminya setelah diverifikasi sendiri. --}}
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const returnUrlTemplate = @json(route('borrowed-items.return', ['project' => '__PROJECT_ID__']));

    const modalEl = document.getElementById('scanBarcodeModal');
    const serialInput = document.getElementById('scanSerialInput');
    const lookupBtn = document.getElementById('btnScanLookup');
    const resultArea = document.getElementById('scanResultArea');

    const openCameraBtn = document.getElementById('btnOpenCamera');
    const closeCameraBtn = document.getElementById('btnCloseCamera');
    const cameraWrapper = document.getElementById('scanCameraWrapper');
    let html5QrCode = null;

    // Reset modal setiap kali dibuka, supaya scan berikutnya mulai bersih.
    modalEl.addEventListener('show.bs.modal', function () {
        serialInput.value = '';
        resultArea.innerHTML = '';
        cameraWrapper.classList.add('d-none');
    });

    modalEl.addEventListener('hidden.bs.modal', stopCamera);

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

    function startCamera() {
        html5QrCode = new Html5Qrcode('scanCameraReader');

        html5QrCode.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 220, height: 220 } },
            function (decodedText) {
                serialInput.value = decodedText;
                stopCamera();
                cameraWrapper.classList.add('d-none');
                doLookup();
            },
            function () { /* frame tidak kebaca, biarkan - normal, terus coba tiap frame */ }
        ).catch(function () {
            resultArea.innerHTML = '<div class="alert alert-warning mb-0">Tidak bisa mengakses kamera. Pastikan browser diberi izin kamera.</div>';
            cameraWrapper.classList.add('d-none');
        });
    }

    function stopCamera() {
        if (html5QrCode) {
            html5QrCode.stop().catch(() => {});
            html5QrCode = null;
        }
    }

    function doLookup() {
        const serial = serialInput.value.trim();

        if (!serial) {
            return;
        }

        resultArea.innerHTML = '<div class="text-center text-muted py-2"><span class="spinner-border spinner-border-sm me-2"></span>Mencari...</div>';

        fetch(`{{ route('inventory.scan-lookup') }}?serial_number=${encodeURIComponent(serial)}`, {
            headers: { 'Accept': 'application/json' },
        })
            .then((res) => res.json())
            .then(renderResult)
            .catch(() => {
                resultArea.innerHTML = '<div class="alert alert-danger mb-0">Terjadi kesalahan, silakan coba lagi.</div>';
            });
    }

    function renderResult(data) {
        if (!data.found) {
            resultArea.innerHTML = `
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Barang dengan serial number ini tidak ditemukan.
                </div>`;
            return;
        }

        if (data.borrowed_units.length === 0) {
            resultArea.innerHTML = `
                <div class="alert alert-info mb-0">
                    <strong>${data.inventory.name}</strong> ditemukan, tapi barang ini sedang tidak dipinjam di project manapun.
                </div>`;
            return;
        }

        if (data.borrowed_units.length === 1) {
            const unit = data.borrowed_units[0];
            resultArea.innerHTML = renderConfirmCard(data.inventory.name, [unit]);
            attachConfirmHandler([unit]);
            return;
        }

        // Lebih dari 1 unit dipinjam bersamaan - user pilih manual yang mana.
        const optionsHtml = data.borrowed_units.map((unit, idx) => `
            <div class="form-check border rounded-3 p-2 mb-2">
                <input class="form-check-input" type="radio" name="scanUnitChoice" id="scanUnitChoice${idx}" value="${idx}" ${idx === 0 ? 'checked' : ''}>
                <label class="form-check-label d-block" for="scanUnitChoice${idx}">
                    <div class="fw-semibold">Unit #${unit.unit_number}</div>
                    <div class="small text-muted">${unit.project_name} - SJ ${unit.surat_jalan_nomor}</div>
                </label>
            </div>
        `).join('');

        resultArea.innerHTML = `
            <div class="alert alert-secondary">
                <strong>${data.inventory.name}</strong> sedang dipinjam di ${data.borrowed_units.length} project sekaligus. Pilih yang mana yang dikembalikan:
            </div>
            ${optionsHtml}
            <button type="button" id="btnConfirmScanReturn" class="btn btn-success w-100 mt-2">
                <i class="bi bi-check-circle me-1"></i> Konfirmasi Kembalikan
            </button>
        `;

        document.getElementById('btnConfirmScanReturn').addEventListener('click', function () {
            const chosenIdx = document.querySelector('input[name="scanUnitChoice"]:checked').value;
            submitReturn(data.borrowed_units[chosenIdx]);
        });
    }

    function renderConfirmCard(inventoryName, units) {
        const unit = units[0];
        return `
            <div class="alert alert-secondary">
                <div class="fw-bold mb-1">${inventoryName} - Unit #${unit.unit_number}</div>
                <div class="small text-muted">Dipinjam untuk project: ${unit.project_name}</div>
                <div class="small text-muted">No. Surat Jalan: ${unit.surat_jalan_nomor}</div>
            </div>
            <button type="button" id="btnConfirmScanReturn" class="btn btn-success w-100">
                <i class="bi bi-check-circle me-1"></i> Konfirmasi Kembalikan
            </button>
        `;
    }

    function attachConfirmHandler(units) {
        document.getElementById('btnConfirmScanReturn').addEventListener('click', function () {
            submitReturn(units[0]);
        });
    }

    function submitReturn(unit) {
        const btn = document.getElementById('btnConfirmScanReturn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

        fetch(returnUrlTemplate.replace('__PROJECT_ID__', unit.project_id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ unit_ids: [unit.unit_id] }),
        })
            .then(async (res) => {
                const resData = await res.json();
                if (!res.ok) throw new Error(resData.message || 'Gagal mengembalikan barang.');
                return resData;
            })
            .then(() => {
                resultArea.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle-fill me-1"></i> Barang berhasil dikembalikan. Silakan scan barang berikutnya.
                    </div>`;
                serialInput.value = '';
                serialInput.focus();
            })
            .catch((err) => {
                resultArea.innerHTML = `<div class="alert alert-danger mb-0">${err.message}</div>`;
            });
    }
});
</script>

@endsection
