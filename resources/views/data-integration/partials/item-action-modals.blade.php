{{-- Modal Rename/Move/Delete file dan folder. Pakai: @include('data-integration.partials.item-action-modals', ['moveGroups' => $moveGroups, 'moveRootLabel' => '...'])
     Pemicu: openRenameModal(url, nama, field, extKunci, tipe), openMoveModal(url, bolehRoot, tipe), openDeleteModal(url, nama, tipe); grup 'warn' = peringatan ruang bersama. --}}
<div class="modal fade" id="dynamicRenameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="dynamicRenameForm" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('PATCH')
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold" id="dynamicRenameTitle">Rename</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="mb-3">
                    <label class="form-label fw-medium text-secondary" for="dynamicRenameInput">Nama</label>
                    <input type="text" class="form-control" id="dynamicRenameInput" name="" required maxlength="255">
                    <div class="form-text small" id="dynamicRenameExtHint"></div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Save</button>
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
                <h5 class="modal-title fw-semibold" id="dynamicMoveTitle">Move</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Move</button>
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
                <h5 class="modal-title fw-semibold text-danger">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-3" id="dynamicDeleteMessage">Apakah Anda yakin ingin menghapus item ini?</p>
                <div class="p-2 bg-light rounded border text-truncate">
                    <strong class="text-secondary small" id="dynamicDeleteLabel">Nama: </strong>
                    <span id="dynamicDeleteNameText" class="fw-medium text-dark"></span>
                </div>
                <div class="form-text small mt-2">Item dipindahkan ke Trash dan masih dapat dipulihkan.</div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger px-4">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        'use strict';

        const kindLabel = { file: 'File', folder: 'Folder' };

        /** Ganti nama. currentName sudah TANPA ekstensi; server menambahkan lockedExt kembali (File::applyLockedExtension). */
        window.openRenameModal = function (actionUrl, currentName, inputFieldName, lockedExt, kind) {
            const input = document.getElementById('dynamicRenameInput');

            document.getElementById('dynamicRenameForm').action = actionUrl;
            document.getElementById('dynamicRenameTitle').textContent = 'Rename ' + (kindLabel[kind] || '');
            input.name  = inputFieldName;
            input.value = currentName;
            document.getElementById('dynamicRenameExtHint').textContent =
                lockedExt ? 'Ekstensi .' + lockedExt + ' dipertahankan otomatis.' : '';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('dynamicRenameModal')).show();
        };

        /** Pindah. allowRoot=false menyembunyikan opsi "tingkat atas" (mis. file di ruang bersama wajib berada di folder). */
        window.openMoveModal = function (actionUrl, allowRoot, kind) {
            const select = document.getElementById('dynamicMoveSelect');
            const root   = document.getElementById('dynamicMoveRootOption');

            document.getElementById('dynamicMoveForm').action = actionUrl;
            document.getElementById('dynamicMoveTitle').textContent = 'Move ' + (kindLabel[kind] || '');

            root.hidden   = !allowRoot;
            root.disabled = !allowRoot;

            // Folder pribadi tidak boleh dipindah ke ruang bersama: sembunyikan grup itu untuk folder.
            select.querySelectorAll('optgroup[data-space="shared"]').forEach(function (group) {
                group.hidden   = (kind === 'folder');
                group.disabled = (kind === 'folder');
            });

            select.selectedIndex = 0;                       // kembali ke placeholder setiap kali dibuka
            document.getElementById('dynamicMoveWarn').classList.add('d-none');

            bootstrap.Modal.getOrCreateInstance(document.getElementById('dynamicMoveModal')).show();
        };

        /** Konfirmasi hapus. */
        window.openDeleteModal = function (actionUrl, itemName, kind) {
            document.getElementById('dynamicDeleteForm').action = actionUrl;
            document.getElementById('dynamicDeleteNameText').textContent = itemName;
            document.getElementById('dynamicDeleteLabel').textContent = 'Nama ' + (kindLabel[kind] || '') + ': ';
            document.getElementById('dynamicDeleteMessage').textContent = kind === 'folder'
                ? 'Apakah Anda yakin ingin menghapus folder ini beserta isinya?'
                : 'Apakah Anda yakin ingin menghapus file ini?';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('dynamicDeleteModal')).show();
        };

        // Peringatan visibilitas muncul hanya bila tujuan dipilih dari grup bertanda warn (ruang bersama).
        document.getElementById('dynamicMoveSelect').addEventListener('change', function () {
            const option = this.options[this.selectedIndex];
            document.getElementById('dynamicMoveWarn')
                .classList.toggle('d-none', !(option && option.dataset.warn === '1'));
        });
    })();
</script>
