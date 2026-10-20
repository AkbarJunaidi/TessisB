// Selesaikan Perbaikan: total dibagi rata ke biaya tiap unit, dan mengubah biaya unit memperbarui total.
document.addEventListener('DOMContentLoaded', () => {
    const total = document.getElementById('biayaTotal');
    const units = Array.from(document.querySelectorAll('.repair-unit-biaya'));

    if (!total || units.length === 0) {
        return;
    }

    const syncTotal = () => {
        const filled = units.some((input) => input.value !== '');
        NumberInput.set(total, filled ? units.reduce((sum, input) => sum + NumberInput.parse(input.value), 0) : '');
    };

    total.addEventListener('input', () => {
        if (total.value === '') {
            units.forEach((input) => NumberInput.set(input, ''));
            return;
        }

        const value = NumberInput.parse(total.value);
        const share = Math.floor(value / units.length);

        units.forEach((input, index) => {
            NumberInput.set(input, share + (index === units.length - 1 ? value - share * units.length : 0));
        });
    });

    units.forEach((input) => input.addEventListener('input', syncTotal));
    syncTotal();
});

// Selesaikan Perbaikan: kolom catatan muncul per barang hanya untuk hasil yang dipilih (berhasil atau gagal).
document.addEventListener('DOMContentLoaded', () => {
    const selects = Array.from(document.querySelectorAll('.repair-hasil'));
    const notes = Array.from(document.querySelectorAll('.repair-note'));

    if (selects.length === 0 || notes.length === 0) {
        return;
    }

    const sync = () => {
        notes.forEach((note) => {
            const used = selects.some((select) => select.dataset.inventory === note.dataset.inventory && select.value === note.dataset.hasil);
            const field = note.querySelector('textarea');

            note.classList.toggle('d-none', !used);
            field.disabled = !used;
            field.required = used && note.dataset.hasil === 'Rusak';
        });
    };

    selects.forEach((select) => select.addEventListener('change', sync));
    sync();
});
