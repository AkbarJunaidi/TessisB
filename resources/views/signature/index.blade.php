@extends('layouts.app')

@section('title', 'Tanda Tangan Saya')

@section('content')
<div class="container-fluid p-0">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-3">
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Tanda Tangan Saya</h3>
        <p class="text-muted mb-0">Dipakai untuk mengisi tanda tangan otomatis di dokumen seperti Kwitansi.</p>
    </div>

    <div class="row g-3">

        {{-- ===== Panel kiri: tambah tanda tangan baru ===== --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">

                    <ul class="nav nav-tabs mb-3" id="signatureSourceTabs">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#src-draw" type="button">Gambar Langsung</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#src-upload" type="button">Upload File</button></li>
                    </ul>

                    <form action="{{ route('signature.store') }}" method="POST" enctype="multipart/form-data" id="signatureForm">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label small">Nama Tanda Tangan</label>
                            <input type="text" name="label" class="form-control" placeholder="Contoh: Tanda Tangan Utama" required maxlength="50">
                        </div>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="src-draw">
                                <label class="form-label small">Gambar di sini pakai mouse / jari</label>
                                <canvas id="signatureCanvas" width="500" height="180" class="border rounded-3 w-100" style="touch-action: none; cursor: crosshair;"></canvas>
                                <input type="hidden" name="canvas_data" id="signatureCanvasData">
                                <button type="button" id="signatureClearBtn" class="btn btn-sm btn-outline-secondary mt-2">
                                    <i class="bi bi-eraser"></i> Hapus Coretan
                                </button>
                            </div>
                            <div class="tab-pane fade" id="src-upload">
                                <label class="form-label small">Pilih file gambar (PNG/JPG, background transparan lebih baik)</label>
                                <input type="file" name="file" id="signatureFileInput" class="form-control" accept="image/png,image/jpeg,image/webp">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">
                            <i class="bi bi-save"></i> Simpan Tanda Tangan
                        </button>
                    </form>

                </div>
            </div>
        </div>

        {{-- ===== Panel kanan: daftar tanda tangan tersimpan ===== --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Tanda Tangan Tersimpan</h6>

                    @forelse($signatures as $sig)
                        <div class="d-flex align-items-center justify-content-between border rounded-3 p-2 mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ asset('storage/' . $sig->file_path) }}" alt="{{ $sig->label }}" style="height: 40px; max-width: 120px; object-fit: contain; background: #fff;">
                                <div>
                                    <div class="fw-semibold small">{{ $sig->label }}</div>
                                    @if($sig->is_default)
                                        <span class="badge bg-success">Default</span>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                @unless($sig->is_default)
                                    <form action="{{ route('signature.set-default', $sig) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Jadikan Default">
                                            <i class="bi bi-star"></i>
                                        </button>
                                    </form>
                                @endunless
                                <form action="{{ route('signature.destroy', $sig) }}" method="POST" onsubmit="return confirm('Hapus tanda tangan ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small text-center py-4 mb-0">Belum ada tanda tangan tersimpan.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Kanvas gambar tanda tangan - vanilla JS, tanpa library eksternal.
     Mendukung mouse & touch. Saat submit, canvas dikonversi ke PNG
     base64 dan dikirim lewat input hidden #signatureCanvasData. --}}
<script>
    (function () {
        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        const clearBtn = document.getElementById('signatureClearBtn');
        const hiddenInput = document.getElementById('signatureCanvasData');
        const form = document.getElementById('signatureForm');
        const fileInput = document.getElementById('signatureFileInput');

        let isDrawing = false;
        let hasDrawn = false;

        function resetCanvas() {
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.strokeStyle = '#1a1a1a';
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            hasDrawn = false;
        }
        resetCanvas();

        function getPoint(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY,
            };
        }

        function startDraw(e) {
            e.preventDefault();
            isDrawing = true;
            hasDrawn = true;
            const p = getPoint(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
        }

        function moveDraw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const p = getPoint(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
        }

        function endDraw() {
            isDrawing = false;
        }

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', moveDraw);
        canvas.addEventListener('mouseup', endDraw);
        canvas.addEventListener('mouseleave', endDraw);
        canvas.addEventListener('touchstart', startDraw);
        canvas.addEventListener('touchmove', moveDraw);
        canvas.addEventListener('touchend', endDraw);

        clearBtn.addEventListener('click', resetCanvas);

        form.addEventListener('submit', function () {
            const drawTabActive = document.getElementById('src-draw').classList.contains('active');

            if (drawTabActive && hasDrawn) {
                hiddenInput.value = canvas.toDataURL('image/png');
                fileInput.value = '';
            } else {
                hiddenInput.value = '';
            }
        });
    })();
</script>

@endsection
