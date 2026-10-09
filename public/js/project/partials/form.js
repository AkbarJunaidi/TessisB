// Batas min Tanggal Selesai disinkronkan dengan Tanggal Mulai; validasi sebenarnya di ProjectRequest.
document.addEventListener('DOMContentLoaded', function () {
    const startInput = document.getElementById('event_date');
    const endInput   = document.getElementById('event_end_date');

    if (!startInput || !endInput) {
        return;
    }

    // Set batas minimum Tanggal Selesai mengikuti Tanggal Mulai.
    function syncEndDateMin() {
        if (!startInput.value) {
            return;
        }

        endInput.min = startInput.value;

        if (endInput.value && endInput.value < startInput.value) {
            endInput.value = startInput.value;
        }
    }

    syncEndDateMin();
    startInput.addEventListener('change', syncEndDateMin);
});
