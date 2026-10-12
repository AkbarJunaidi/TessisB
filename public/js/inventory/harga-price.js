// Modal isi / ubah harga di Daftar Harga Barang: isi form dari tombol yang diklik.
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('priceModal');

    if (!modal) {
        return;
    }

    modal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        const price = button.dataset.price;

        document.getElementById('priceForm').action = button.dataset.action;
        document.getElementById('priceModalName').textContent = button.dataset.name;
        document.getElementById('hargaManual').value = price ? parseInt(price, 10).toLocaleString('id-ID') : '';
    });

    modal.addEventListener('shown.bs.modal', () => document.getElementById('hargaManual').focus());
});
