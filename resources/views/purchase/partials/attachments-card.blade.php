@php
    $canAddAttachment = auth()->user()->hasPermission('purchase', 'edit')
        && in_array($purchase->status, \App\Models\Purchase::COMMITTED, true);
@endphp

<div class="card border-0 shadow-sm rounded-3 mt-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Lampiran per Tahap</h6>

        @forelse($purchase->attachments as $file)
            <div class="d-flex justify-content-between align-items-start gap-2 small {{ $loop->last ? '' : 'mb-2' }}">
                <div>
                    <a href="{{ route('purchases.attachments.show', $file) }}" target="_blank" rel="noopener" class="text-decoration-none">
                        <i class="bi bi-paperclip"></i> {{ $file->original_name }}
                    </a>
                    <div class="text-muted">{{ $file->stage_label }} - {{ $file->created_at->translatedFormat('d M Y') }}@if($file->uploader) - {{ $file->uploader->name }}@endif</div>
                </div>
                @if($canAddAttachment && in_array($file->stage, \App\Models\PurchaseAttachment::MANUAL_STAGES, true))
                    <form action="{{ route('purchases.attachments.destroy', $file) }}" method="POST" data-confirm="Hapus lampiran ini?" data-confirm-label="Hapus" data-confirm-danger>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Hapus lampiran"><i class="bi bi-trash"></i></button>
                    </form>
                @endif
            </div>
        @empty
            <div class="small text-muted">Belum ada lampiran. Bukti pesanan, transfer dana, dan pembayaran ikut tersimpan di sini.</div>
        @endforelse

        @if($canAddAttachment)
            <form action="{{ route('purchases.attachments.store', $purchase) }}" method="POST" enctype="multipart/form-data" class="row g-2 mt-2 pt-3 border-top">
                @csrf
                <div class="col-md-4">
                    <select name="stage" class="form-select form-select-sm" aria-label="Jenis lampiran" required>
                        @foreach(\App\Models\PurchaseAttachment::MANUAL_STAGES as $stage)
                            <option value="{{ $stage }}">{{ \App\Models\PurchaseAttachment::STAGES[$stage] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="file" name="file" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,.pdf" aria-label="File lampiran" required>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-upload me-1"></i> Unggah</button>
                </div>
            </form>
        @endif
    </div>
</div>
