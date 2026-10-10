(function () {
    'use strict';

    const kindLabel = { file: 'File', folder: 'Folder' };

    /** Ganti nama. currentName sudah TANPA ekstensi; server menambahkan lockedExt kembali (File::applyLockedExtension). */
    window.openRenameModal = function (actionUrl, currentName, inputFieldName, lockedExt, kind) {
        const input = document.getElementById('dynamicRenameInput');

        document.getElementById('dynamicRenameForm').action = actionUrl;
        document.getElementById('dynamicRenameTitle').textContent = 'Ganti Nama ' + (kindLabel[kind] || '');
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
        document.getElementById('dynamicMoveTitle').textContent = 'Pindahkan ' + (kindLabel[kind] || '');

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
