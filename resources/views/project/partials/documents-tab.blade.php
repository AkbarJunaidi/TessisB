{{-- Partial: Tab Dokumen
     Variabel yang dibutuhkan saat di-include: $project (dengan relasi folder.files.user sudah di-load), $allFolders (koleksi seluruh Folder untuk dropdown pilihan) --}}

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold m-0">Dokumen Project</h6>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                <i class="bi bi-upload"></i> Upload Dokumen
            </button>
        </div>

        @if($project->folder)
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr class="text-muted small">
                            <th>Nama File</th>
                            <th>Ukuran</th>
                            <th>Diunggah Oleh</th>
                            <th>Tanggal</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($project->folder->files as $file)
                            <tr>
                                <td><i class="bi bi-file-earmark-text me-1 text-secondary"></i> {{ $file->file_name }}</td>
                                <td>{{ $file->readable_size }}</td>
                                <td>{{ $file->user->name ?? '-' }}</td>
                                <td>{{ $file->created_at->translatedFormat('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('files.download', $file) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted small text-center py-3">Belum ada dokumen pada project ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted small m-0">Folder project belum tersedia.</p>
        @endif

    </div>
</div>

<div class="modal fade" id="uploadDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Upload Dokumen</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Simpan ke Folder</label>
                        <select name="folder_id" class="form-select">
                            {{-- Default terpilih: folder project ini, tapi tetap bisa diganti sebelum submit --}}
                            @foreach($allFolders as $folder)
                                <option value="{{ $folder->id }}" @selected(optional($project->folder)->id === $folder->id)>
                                    {{ $folder->parent ? $folder->parent->name.' / ' : '' }}{{ $folder->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-secondary">Pilih File</label>
                        <input type="file" name="file" class="form-control" required>
                        <div class="form-text">Maksimal 10 MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
