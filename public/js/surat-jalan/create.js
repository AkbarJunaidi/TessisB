(function () {
    let rowIndex = 0;
    const rowsWrapper = document.getElementById('itemRows');
    const noItemMsg = document.getElementById('noItemMsg');

    // Opsi barang; stok habis dinonaktifkan.
    function buildOptions(selectedId) {
        return INVENTORY_OPTIONS.map(inv => {
            const disabled = inv.available <= 0 ? 'disabled' : '';
            const selected = String(inv.id) === String(selectedId) ? 'selected' : '';
            return `<option value="${inv.id}" data-available="${inv.available}" ${disabled} ${selected}>${inv.name} (tersedia: ${inv.available})</option>`;
        }).join('');
    }

    // Tambah satu baris barang pada form Surat Jalan.
    function addRow() {
        const html = `
            <div class="row g-2 mb-2 align-items-center item-row" data-index="${rowIndex}">
                <div class="col-md-7">
                    <select name="items[${rowIndex}][inventory_id]" class="form-select form-select-sm item-select" required>
                        <option value="">Pilih Barang</option>
                        ${buildOptions(null)}
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" min="1" name="items[${rowIndex}][qty]" class="form-control form-control-sm item-qty" placeholder="Qty" required>
                </div>
                <div class="col-md-1">
                    <span class="badge bg-light text-secondary border item-max-info">-</span>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-row"><i class="bi bi-x"></i></button>
                </div>
            </div>`;
        rowsWrapper.insertAdjacentHTML('beforeend', html);
        rowIndex++;
        toggleEmptyMsg();
    }

    // Tampilkan pesan kosong bila belum ada baris barang.
    function toggleEmptyMsg() {
        noItemMsg.style.display = rowsWrapper.children.length ? 'none' : 'block';
    }

    document.getElementById('addItemRow').addEventListener('click', addRow);

    rowsWrapper.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-item-row');
        if (btn) {
            btn.closest('.item-row').remove();
            toggleEmptyMsg();
        }
    });

    // Validasi kecil di sisi klien: qty tidak boleh melebihi stok tersedia (keputusan akhir tetap di server)
    rowsWrapper.addEventListener('change', function (e) {
        const row = e.target.closest('.item-row');
        if (!row) return;

        const select = row.querySelector('.item-select');
        const qtyInput = row.querySelector('.item-qty');
        const info = row.querySelector('.item-max-info');
        const opt = select.options[select.selectedIndex];
        const max = opt ? (opt.dataset.available || 0) : 0;

        qtyInput.max = max;
        info.textContent = max;
    });

    const suratJalanForm = document.getElementById('suratJalanForm');
    let servisChecked = false;

    suratJalanForm.addEventListener('submit', function (e) {
        if (rowsWrapper.children.length === 0) {
            e.preventDefault();
            AppUI.toast('Tambahkan minimal 1 barang sebelum menyimpan Surat Jalan.');
            return;
        }

        if (servisChecked) return;

        // Peringatan servis: hanya info, user tetap boleh melanjutkan.
        e.preventDefault();
        const items = Array.from(rowsWrapper.querySelectorAll('.item-row')).map(function (row) {
            return {
                inventory_id: parseInt(row.querySelector('.item-select').value, 10),
                qty: parseInt(row.querySelector('.item-qty').value, 10),
            };
        }).filter(function (it) { return it.inventory_id && it.qty > 0; });

        ServisWarning.confirm(items).then(function (ok) {
            if (!ok) return;
            servisChecked = true;
            suratJalanForm.requestSubmit();
        });
    });

    // Baris pertama otomatis ditambahkan agar form tidak kosong
    addRow();
})();
