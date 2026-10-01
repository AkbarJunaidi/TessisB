{{--
    Modal & JS bersama untuk kelola file/folder (dipakai My Files dan Folder Management).
    Variabel: $moveSets (FolderService::moveSets). Tombol memanggil diOpenRename/diOpenMove/diOpenDelete.
--}}
<div class="modal fade" id="diRenameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="diRenameForm" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('PATCH')
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold" id="diRenameTitle">Rename</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <label for="diRenameInput" class="form-label fw-medium text-secondary">Nama</label>
                <input type="text" class="form-control" id="diRenameInput" maxlength="255" required>
                <div class="form-text small" id="diRenameHint"></div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="diMoveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="diMoveForm" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('PATCH')
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold" id="diMoveTitle">Move</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <label for="diMoveSelect" class="form-label fw-medium text-secondary">Folder Tujuan</label>
                <select class="form-select" id="diMoveSelect" name="target_folder_id" required></select>
                <div class="form-text small" id="diMoveHint"></div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Move</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="diDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="diDeleteForm" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('DELETE')
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold text-danger">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-3" id="diDeleteMessage"></p>
                <div class="p-2 bg-light rounded border text-truncate">
                    <span id="diDeleteName" class="fw-medium text-dark"></span>
                </div>
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

        const moveSets = {{ \Illuminate\Support\Js::from($moveSets) }};
        const show = function (id) { bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show(); };

        /**
         * Rename. name = nilai awal (tanpa ekstensi untuk file), field = nama input di request,
         * lockedExt = ekstensi yang ditambahkan ulang server (kosong untuk folder).
         */
        window.diOpenRename = function (url, name, field, title, lockedExt) {
            document.getElementById('diRenameForm').action = url;
            document.getElementById('diRenameTitle').textContent = title;
            const input = document.getElementById('diRenameInput');
            input.name = field;
            input.value = name;
            document.getElementById('diRenameHint').textContent = lockedExt ? 'Ekstensi .' + lockedExt + ' dipertahankan otomatis.' : '';
            show('diRenameModal');
        };

        /**
         * Move. setKey = kunci di moveSets; excludeId = id folder yang dipindah (opsi dirinya
         * dan turunannya dinonaktifkan; server tetap memvalidasi).
         */
        window.diOpenMove = function (url, setKey, title, excludeId) {
            const set = moveSets[setKey];
            const select = document.getElementById('diMoveSelect');
            document.getElementById('diMoveForm').action = url;
            document.getElementById('diMoveTitle').textContent = title;
            select.replaceChildren();

            const placeholder = new Option('-- Pilih Folder Tujuan --', '', true, true);
            placeholder.disabled = true;
            select.appendChild(placeholder);

            if (set.root) { select.appendChild(new Option(set.root, '')); }

            let total = 0;
            Object.keys(set.groups).forEach(function (label) {
                const group = document.createElement('optgroup');
                group.label = label;
                set.groups[label].forEach(function (item) {
                    const option = new Option(item.label, item.id);
                    if (excludeId && item.lineage.split(',').indexOf(String(excludeId)) !== -1) { option.disabled = true; }
                    group.appendChild(option);
                    total++;
                });
                select.appendChild(group);
            });

            document.getElementById('diMoveHint').textContent = (!set.root && total === 0) ? 'Belum ada folder tujuan.' : '';
            show('diMoveModal');
        };

        window.diOpenDelete = function (url, name, message) {
            document.getElementById('diDeleteForm').action = url;
            document.getElementById('diDeleteName').textContent = name;
            document.getElementById('diDeleteMessage').textContent = message;
            show('diDeleteModal');
        };
    })();
</script>
