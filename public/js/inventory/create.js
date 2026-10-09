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

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <input type="text" name="attributes[${rowIndex}][name]" class="form-control form-control-sm" maxlength="40" placeholder="Contoh: Processor / RAM / Panjang">
            </td>
            <td>
                <input type="text" name="attributes[${rowIndex}][value]" class="form-control form-control-sm" maxlength="100" placeholder="Contoh: Ryzen 7 / 16 GB / 5 Meter">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Hapus Baris">
                    <i class="bi bi-trash"></i> Hapus
                </button>
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
