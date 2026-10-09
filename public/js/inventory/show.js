(function () {
    'use strict';

    const modalEl   = document.getElementById('moveLocationModal');
    const form      = document.getElementById('moveLocationForm');
    const btnOpen   = document.getElementById('btnMoveLocation');
    const selectAll = document.getElementById('unitSelectAll');
    const countEl   = document.getElementById('unitSelectedCount');
    const inputsBox = document.getElementById('moveUnitInputs');
    const listEl    = document.getElementById('moveUnitList');
    const targetEl  = document.getElementById('moveTarget');
    const gpsBox    = document.getElementById('moveGpsBox');
    const gpsText   = document.getElementById('moveGpsText');
    const latEl     = document.getElementById('moveLat');
    const lngEl     = document.getElementById('moveLng');
    const accEl     = document.getElementById('moveAcc');

    if (!btnOpen) return;

    const checkboxes = Array.from(document.querySelectorAll('.unit-select'));
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    // Naik setiap deteksi baru / modal ditutup; hasil deteksi yang basi dibuang.
    let detectToken = 0;

    // Checkbox unit yang dicentang.
    function selected() {
        return checkboxes.filter(function (c) { return c.checked; });
    }

    // Sinkronkan tombol dan jumlah sesuai unit terpilih.
    function refreshSelection() {
        const n = selected().length;
        btnOpen.disabled = n === 0;
        countEl.textContent = n > 0 ? '(' + n + ')' : '';
        selectAll.checked = n > 0 && n === checkboxes.length;
        selectAll.indeterminate = n > 0 && n < checkboxes.length;
    }

    checkboxes.forEach(function (c) { c.addEventListener('change', refreshSelection); });
    selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (c) { c.checked = selectAll.checked; });
        refreshSelection();
    });

    /** Tampilan status deteksi: kind = info | success | warning | muted. */
    function setStatus(kind, text) {
        const cls = { info: 'alert-info', success: 'alert-success', warning: 'alert-warning', muted: 'alert-light border' };
        gpsBox.className = 'alert ' + (cls[kind] || cls.muted) + ' small py-2 d-flex justify-content-between align-items-center gap-2';
        gpsText.textContent = text;
    }

    // Kosongkan koordinat hasil deteksi.
    function clearCoords() {
        latEl.value = '';
        lngEl.value = '';
        accEl.value = '';
    }

    // Ambil koordinat GPS lalu minta server mencocokkan lokasi terdekat; token mencegah hasil lama menimpa.
    function detect() {
        const token = ++detectToken;
        clearCoords();
        targetEl.selectedIndex = 0;

        if (!('geolocation' in navigator)) {
            setStatus('muted', 'GPS tidak tersedia di perangkat ini. Pilih lokasi secara manual.');
            return;
        }
        if (!window.isSecureContext) {
            setStatus('muted', 'GPS hanya bisa dipakai lewat HTTPS. Pilih lokasi secara manual.');
            return;
        }

        setStatus('info', 'Mendeteksi lokasi...');

        navigator.geolocation.getCurrentPosition(function (pos) {
            if (token !== detectToken) return;

            const c = pos.coords;
            latEl.value = c.latitude;
            lngEl.value = c.longitude;
            accEl.value = Math.round(c.accuracy);

            const query = new URLSearchParams({ lat: c.latitude, lng: c.longitude, accuracy: c.accuracy });

            fetch(form.dataset.detectUrl + '?' + query.toString(), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('detect failed');
                    return res.json();
                })
                .then(function (data) {
                    if (token !== detectToken) return;

                    if (data.status === 'terdeteksi' && data.location) {
                        const option = Array.from(targetEl.options).find(function (o) {
                            return o.value === String(data.location.id);
                        });
                        if (option) {
                            targetEl.value = option.value;
                            setStatus('success', 'Terdeteksi: ' + data.location.name + ' (sekitar ' + data.distance_m + ' m)');
                            return;
                        }
                    }

                    if (data.status === 'akurasi_rendah') {
                        setStatus('warning', 'Akurasi GPS rendah (sekitar ' + data.accuracy_m + ' m). Pilih lokasi secara manual.');
                        return;
                    }

                    setStatus('warning', 'Di luar jangkauan. Pilih lokasi secara manual.');
                })
                .catch(function () {
                    if (token !== detectToken) return;
                    setStatus('muted', 'Gagal mendeteksi lokasi. Pilih lokasi secara manual.');
                });
        }, function (err) {
            if (token !== detectToken) return;
            clearCoords();
            setStatus('muted', err.code === 1
                ? 'Izin lokasi ditolak. Pilih lokasi secara manual.'
                : 'Lokasi tidak dapat dibaca. Pilih lokasi secara manual.');
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
    }

    btnOpen.addEventListener('click', function () {
        const chosen = selected();
        if (chosen.length === 0) return;

        // Isi id unit terpilih ke form + ringkasan nomor unit.
        inputsBox.replaceChildren();
        chosen.forEach(function (c) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'unit_ids[]';
            input.value = c.value;
            inputsBox.appendChild(input);
        });
        listEl.textContent = chosen.map(function (c) { return '#' + c.dataset.number; }).join(', ');
        document.getElementById('moveMakeHome').checked = false;

        modal.show();
        detect();
    });

    document.getElementById('moveGpsRetry').addEventListener('click', detect);
    modalEl.addEventListener('hidden.bs.modal', function () { detectToken++; });
})();
