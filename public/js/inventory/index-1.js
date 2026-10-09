document.addEventListener("DOMContentLoaded", function() {
    const deleteModal = document.getElementById('deleteInventoryModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const sn = button.getAttribute('data-sn');

            document.getElementById('modal-inventory-name').textContent = name;
            document.getElementById('modal-inventory-sn').textContent = sn;
            document.getElementById('deleteInventoryForm').action = `/inventory/${id}`;
        });
    }
});
