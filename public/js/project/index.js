document.addEventListener('DOMContentLoaded', function () {
    const deleteModal = document.getElementById('deleteProjectModal');
    if (deleteModal) {
        const loadingEl  = document.getElementById('return-status-loading');
        const okEl       = document.getElementById('return-status-ok');
        const warningEl  = document.getElementById('return-status-warning');
        const listEl     = document.getElementById('return-status-list');

        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');

            document.getElementById('modal-project-name').textContent = name;
            document.getElementById('deleteProjectForm').action = `/projects/${id}`;

            // Reset & cek status pengembalian barang setiap kali modal dibuka
            okEl.classList.add('d-none');
            warningEl.classList.add('d-none');
            listEl.innerHTML = '';
            loadingEl.classList.remove('d-none');

            fetch(`/projects/${id}/return-status`, {
                headers: { 'Accept': 'application/json' },
            })
                .then((response) => response.json())
                .then((data) => {
                    loadingEl.classList.add('d-none');

                    if (data.fully_returned) {
                        okEl.classList.remove('d-none');
                        return;
                    }

                    data.items.forEach((item) => {
                        const li = document.createElement('li');
                        li.textContent = `${item.inventory_name} - ${item.qty_belum_kembali} unit (Surat Jalan ${item.surat_jalan_nomor})`;
                        listEl.appendChild(li);
                    });
                    warningEl.classList.remove('d-none');
                })
                .catch(() => {
                    loadingEl.classList.add('d-none');
                });
        });
    }
});
