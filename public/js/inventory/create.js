document.addEventListener("DOMContentLoaded", function() {
    const useToggle = document.getElementById('use_attr_toggle');
    const useHidden = document.getElementById('use_attributes_hidden');
    const useLabel = document.getElementById('use_attr_label');
    const container = document.getElementById('attributesContainer');
    const attributesBody = document.getElementById('attributesBody');
    const btnAdd = document.getElementById('btnAddAttribute');
    const limitWarning = document.getElementById('attrLimitWarning');
    const MAX_ATTR_ROWS = 7;

    // Toggle visibility container + sinkronkan hidden input & label berdasarkan switch
    function toggleContainer() {
        const isOn = useToggle.checked;
        useHidden.value = isOn ? '1' : '0';
        useLabel.textContent = isOn ? 'Ya' : 'Tidak';

        if (isOn) {
            container.classList.remove('d-none');
        } else {
            container.classList.add('d-none');
        }
    }

    useToggle.addEventListener('change', toggleContainer);

    // Menambah Baris Atribut Baru
    let rowIndex = attributesBody.querySelectorAll('tr').length;

    // Batasi jumlah baris agar tabel Informasi Tambahan tetap muat 1 halaman saat dicetak ke PDF
    function updateAddButtonState() {
        const currentRows = attributesBody.querySelectorAll('tr').length;
        const limitReached = currentRows >= MAX_ATTR_ROWS;
        btnAdd.disabled = limitReached;
        limitWarning.classList.toggle('d-none', !limitReached);
    }

    btnAdd.addEventListener('click', function() {
        if (attributesBody.querySelectorAll('tr').length >= MAX_ATTR_ROWS) {
            updateAddButtonState();
            return;
        }

        // maxlength mengikuti App\Support\TextLimits (ATTR_NAME 30, ATTR_VALUE 60).
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td class="cell-block" data-label="Nama Informasi">
                <input type="text" name="attributes[${rowIndex}][name]" class="form-control form-control-sm" maxlength="30" placeholder="Contoh: Processor / RAM / Panjang">
            </td>
            <td class="cell-block" data-label="Nilai">
                <input type="text" name="attributes[${rowIndex}][value]" class="form-control form-control-sm" maxlength="60" placeholder="Contoh: Ryzen 7 / 16 GB / 5 Meter">
            </td>
            <td class="text-center cell-block" data-label="Aksi">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Hapus Baris" aria-label="Hapus baris"><i class="bi bi-trash"></i><span class="d-md-none ms-1">Hapus</span></button>
            </td>
        `;
        attributesBody.appendChild(newRow);
        rowIndex++;
        updateAddButtonState();
    });

    // Hapus Baris Atribut (Event Delegation)
    attributesBody.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.btn-remove-row');
        if (removeBtn) {
            const tr = removeBtn.closest('tr');
            if (tr) {
                tr.remove();
            }
            updateAddButtonState();
        }
    });

    updateAddButtonState();

    // Pratinjau foto barang yang baru dipilih (tidak diunggah sampai form disimpan).
    const imageInput = document.getElementById('image');
    if (imageInput) {
        const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
        const previewImg = document.getElementById('imagePreviewImg');
        const previewEmpty = document.getElementById('imagePreviewEmpty');
        const imageInfo = document.getElementById('imageInfo');
        const imageName = document.getElementById('imageName');
        const imageSize = document.getElementById('imageSize');
        let previewUrl = null;

        function formatSize(bytes) {
            return bytes >= 1048576
                ? (bytes / 1048576).toFixed(1).replace('.', ',') + ' MB'
                : Math.max(1, Math.round(bytes / 1024)) + ' KB';
        }

        function resetPreview() {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = null;
            previewImg.removeAttribute('src');
            previewImg.classList.add('d-none');
            previewEmpty.classList.remove('d-none');
            imageInfo.classList.add('d-none');
        }

        imageInput.addEventListener('change', function() {
            const file = imageInput.files[0];
            resetPreview();
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                imageInput.value = '';
                if (window.AppUI) window.AppUI.toast('File harus berupa gambar (jpeg, png, jpg, webp).', 'danger');
                return;
            }

            previewUrl = URL.createObjectURL(file);
            previewImg.src = previewUrl;
            previewImg.classList.remove('d-none');
            previewEmpty.classList.add('d-none');

            const tooBig = file.size > MAX_IMAGE_BYTES;
            imageName.textContent = file.name;
            imageSize.textContent = formatSize(file.size) + (tooBig ? ' (melebihi 5 MB)' : '');
            imageSize.classList.toggle('text-danger', tooBig);
            imageSize.classList.toggle('text-muted', !tooBig);
            imageInfo.classList.remove('d-none');
        });

        document.getElementById('imageClear').addEventListener('click', function() {
            imageInput.value = '';
            resetPreview();
        });
    }

    const descriptionField = document.getElementById('description');
    const descriptionCounter = document.getElementById('descriptionCounter');
    if (descriptionField && descriptionCounter) {
        // Perbarui penghitung karakter deskripsi (maks 500).
        function updateDescriptionCounter() {
            descriptionCounter.textContent = descriptionField.value.length + '/500';
        }
        descriptionField.addEventListener('input', updateDescriptionCounter);
        updateDescriptionCounter();
    }
});
