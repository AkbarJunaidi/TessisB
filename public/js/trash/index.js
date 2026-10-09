document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const tableBody = document.getElementById('trashTableBody');
    const totalBadge = document.getElementById('trashTotalBadge');
    const alertPlaceholder = document.getElementById('trashAlertPlaceholder');

    const restoreModalEl = document.getElementById('restoreTrashModal');
    const restoreModal = new bootstrap.Modal(restoreModalEl);
    const forceDeleteModalEl = document.getElementById('forceDeleteTrashModal');
    const forceDeleteModal = new bootstrap.Modal(forceDeleteModalEl);

    let activeType = null;
    let activeId = null;
    let activeRow = null;

    // Tampilkan alert Bootstrap di atas halaman.
    function showAlert(type, message) {
        const alertEl = document.createElement('div');
        alertEl.className = `alert alert-${type} alert-dismissible fade show mb-4`;
        alertEl.role = 'alert';
        alertEl.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        alertPlaceholder.innerHTML = '';
        alertPlaceholder.appendChild(alertEl);
    }

    // Kurangi badge total setelah satu item diproses.
    function decrementTotalBadge() {
        if (!totalBadge) return;
        const current = parseInt(totalBadge.textContent, 10) || 0;
        const next = Math.max(0, current - 1);
        totalBadge.textContent = `${next} Total Data`;
    }

    // Hapus baris dari tabel; tampilkan pesan kosong bila tabel habis.
    function removeRowAndCheckEmpty(row) {
        if (row) row.remove();

        if (!tableBody.querySelector('tr[data-trash-row]')) {
            tableBody.innerHTML = `
                <tr id="trashEmptyRow">
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-check2-circle fs-2 d-block mb-2 text-secondary opacity-50"></i>
                        Trash kosong. Tidak ada data yang cocok dengan kriteria filter Anda.
                    </td>
                </tr>`;
        }
    }

    // Buka modal Pulihkan.
    restoreModalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        activeType = button.getAttribute('data-type');
        activeId = button.getAttribute('data-id');
        activeRow = button.closest('tr[data-trash-row]');

        document.getElementById('restore-item-name').textContent = button.getAttribute('data-name');
        document.getElementById('restore-item-type').textContent = button.getAttribute('data-type-label');
    });

    // Buka modal Hapus Permanen.
    forceDeleteModalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        activeType = button.getAttribute('data-type');
        activeId = button.getAttribute('data-id');
        activeRow = button.closest('tr[data-trash-row]');

        document.getElementById('force-delete-item-name').textContent = button.getAttribute('data-name');
        document.getElementById('force-delete-item-type').textContent = button.getAttribute('data-type-label');
    });

    // Kirim aksi pulihkan/hapus permanen lewat fetch lalu perbarui UI tanpa reload.
    function submitAction(url, method, submitBtn, modal, successPrefix) {
        const btnText = submitBtn.querySelector('.btn-text');
        const spinner = submitBtn.querySelector('.spinner-border');

        submitBtn.disabled = true;
        btnText.classList.add('d-none');
        spinner.classList.remove('d-none');

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        })
            .then(async (response) => {
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Terjadi kesalahan, silakan coba lagi.');
                return data;
            })
            .then(function (data) {
                modal.hide();
                showAlert('success', data.message || successPrefix);
                removeRowAndCheckEmpty(activeRow);
                decrementTotalBadge();
                activeType = null;
                activeId = null;
                activeRow = null;
            })
            .catch(function (err) {
                modal.hide();
                showAlert('danger', err.message);
            })
            .finally(function () {
                submitBtn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
            });
    }

    document.getElementById('restoreTrashSubmit').addEventListener('click', function () {
        if (!activeType || !activeId) return;
        submitAction(
            `/trash/${activeType}/${activeId}/restore`,
            'PATCH',
            this,
            restoreModal,
            'Data berhasil dipulihkan.'
        );
    });

    document.getElementById('forceDeleteTrashSubmit').addEventListener('click', function () {
        if (!activeType || !activeId) return;
        submitAction(
            `/trash/${activeType}/${activeId}`,
            'DELETE',
            this,
            forceDeleteModal,
            'Data berhasil dihapus permanen.'
        );
    });
});
