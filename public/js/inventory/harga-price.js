// Modal tambah/perbarui harga: isi form dari baris yang diklik, judul mengikuti ada tidaknya harga.
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('priceModal');

    if (!modal) {
        return;
    }

    modal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        const hasPrice = button.dataset.price !== '';
        const price = hasPrice ? parseInt(button.dataset.price, 10).toLocaleString('id-ID') : '';

        document.getElementById('priceForm').action = button.dataset.action;
        document.getElementById('priceModalName').textContent = button.dataset.name;
        document.getElementById('priceModalTitle').textContent = hasPrice ? 'Perbarui Harga Barang' : 'Tambah Harga Barang';
        document.getElementById('priceSubmit').textContent = 'Simpan';
        document.getElementById('hargaManual').value = price;
    });

    modal.addEventListener('shown.bs.modal', () => document.getElementById('hargaManual').focus());
});
