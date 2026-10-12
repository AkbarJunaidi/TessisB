// Selesaikan Perbaikan: total dibagi rata ke biaya tiap unit, dan mengubah biaya unit memperbarui total.
document.addEventListener('DOMContentLoaded', () => {
    const total = document.getElementById('biayaTotal');
    const units = Array.from(document.querySelectorAll('.repair-unit-biaya'));

    if (!total || units.length === 0) {
        return;
    }

    const syncTotal = () => {
        const filled = units.some((input) => input.value !== '');
        total.value = filled ? units.reduce((sum, input) => sum + (parseInt(input.value, 10) || 0), 0) : '';
    };

    total.addEventListener('input', () => {
        if (total.value === '') {
            units.forEach((input) => { input.value = ''; });
            return;
        }

        const value = Math.max(0, parseInt(total.value, 10) || 0);
        const share = Math.floor(value / units.length);

        units.forEach((input, index) => {
            input.value = share + (index === units.length - 1 ? value - share * units.length : 0);
        });
    });

    units.forEach((input) => input.addEventListener('input', syncTotal));
    syncTotal();
});
