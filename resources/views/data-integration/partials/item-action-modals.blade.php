{{-- Modal Rename/Move/Delete file dan folder. Pakai: @include('data-integration.partials.item-action-modals', ['moveGroups' => $moveGroups, 'moveRootLabel' => '...'])
     Pemicu: openRenameModal(url, nama, field, extKunci, tipe), openMoveModal(url, bolehRoot, tipe), openDeleteModal(url, nama, tipe); grup 'warn' = peringatan ruang bersama. --}}
<div class="modal fade" id="dynamicRenameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="dynamicRenameForm" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('PATCH')
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold" id="dynamicRenameTitle">Ganti Nama</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body py-4">
                <div class="mb-3">
                    <label class="form-label fw-medium text-secondary" for="dynamicRenameInput">Nama</label>
                    <input type="text" class="form-control" id="dynamicRenameInput" name="" required maxlength="255">
                    <div class="form-text small" id="dynamicRenameExtHint"></div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary px-4">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="dynamicMoveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="dynamicMoveForm" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('PATCH')
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold" id="dynamicMoveTitle">Pindahkan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body py-4">
                <div class="mb-3">
                    <label for="dynamicMoveSelect" class="form-label fw-medium text-secondary">Pilih Folder Tujuan</label>
                    {{-- Opsi pertama (placeholder) dinonaktifkan; opsi "tingkat atas" bernilai kosong dan
                         diletakkan SETELAHNYA agar lolos atribut required. --}}
                    <select class="form-select" id="dynamicMoveSelect" name="target_folder_id" required>
                        <option value="" disabled selected>-- Pilih Folder Tujuan --</option>
                        <option value="" id="dynamicMoveRootOption">{{ $moveRootLabel ?? 'Tingkat atas' }}</option>
                        @foreach($moveGroups as $group)
                            @if(!empty($group['options']))
                                <optgroup label="{{ $group['label'] }}" data-space="{{ !empty($group['warn']) ? 'shared' : 'own' }}">
                                    @foreach($group['options'] as $optionId => $optionPath)
                                        <option value="{{ $optionId }}" data-warn="{{ !empty($group['warn']) ? '1' : '0' }}">{{ $optionPath }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                    <div class="form-text text-muted mt-2 small d-none" id="dynamicMoveWarn">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Memindahkan ke ruang bersama membuat file ini dapat dilihat oleh rekan kerja yang memiliki akses.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary px-4">Pindahkan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="dynamicDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="dynamicDeleteForm" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('DELETE')
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold text-danger">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-3" id="dynamicDeleteMessage">Apakah Anda yakin ingin menghapus item ini?</p>
                <div class="p-2 bg-light rounded border text-truncate">
                    <strong class="text-secondary small" id="dynamicDeleteLabel">Nama: </strong>
                    <span id="dynamicDeleteNameText" class="fw-medium text-dark"></span>
                </div>
                <div class="form-text small mt-2">Item dipindahkan ke Sampah dan masih dapat dipulihkan.</div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger px-4">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script src="{{ \App\Support\AppAsset::url('js/data-integration/partials/item-action-modals.js') }}"></script>
