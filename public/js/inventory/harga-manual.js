// Format ribuan id-ID saat mengetik harga; server membersihkan titiknya lagi.
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('hargaManual');

    if (!input) {
        return;
    }

    input.addEventListener('input', () => {
        const digits = input.value.replace(/\D/g, '');
        input.value = digits === '' ? '' : parseInt(digits, 10).toLocaleString('id-ID');
    });
});
