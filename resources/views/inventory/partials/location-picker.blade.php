{{--
    Komponen "Lokasi saat ini" (deteksi GPS + pilihan manual) untuk aksi pinjam/kembali/rusak/hilang.
    Pakai: LocationPicker.mount(el, { detectUrl, locations: [{id, name}] }) lalu .start() / .value().
    Server menghitung ulang metode lokasi dari koordinat, jadi nilai di sini hanya masukan.
--}}
<script>
(function () {
    'use strict';

    if (window.LocationPicker) { return; }

    const ALERT_CLASS = { info: 'alert-info', success: 'alert-success', warning: 'alert-warning', muted: 'alert-secondary' };

    function mount(root, options) {
        const state = { busy: false, lat: null, lng: null, accuracy: null, token: 0, listeners: [] };

        root.innerHTML =
            '<div class="border rounded-3 p-2 bg-white">' +
                '<div class="d-flex align-items-center justify-content-between mb-1">' +
                    '<span class="small fw-semibold text-muted"><i class="bi bi-geo-alt-fill me-1"></i>Lokasi saat ini</span>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary lp-retry">Deteksi ulang</button>' +
                '</div>' +
                '<div class="alert py-2 px-3 mb-2 small d-flex align-items-center gap-2 lp-status" role="status"></div>' +
                '<select class="form-select form-select-sm lp-select" aria-label="Pilih lokasi secara manual"></select>' +
            '</div>';

        const statusEl = root.querySelector('.lp-status');
        const selectEl = root.querySelector('.lp-select');
        const retryEl  = root.querySelector('.lp-retry');

        function resetSelect() {
            selectEl.replaceChildren();
            const placeholder = new Option('-- Pilih lokasi manual --', '', true, true);
            placeholder.disabled = true;
            selectEl.appendChild(placeholder);
            (options.locations || []).forEach(function (loc) {
                selectEl.appendChild(new Option(loc.name, String(loc.id)));
            });
        }

        function emit() {
            state.listeners.forEach(function (fn) { fn(); });
        }

        function setStatus(kind, text, spinning) {
            statusEl.className = 'alert py-2 px-3 mb-2 small d-flex align-items-center gap-2 lp-status ' + ALERT_CLASS[kind];
            statusEl.replaceChildren();

            if (spinning) {
                const spinner = document.createElement('span');
                spinner.className = 'spinner-border spinner-border-sm flex-shrink-0';
                statusEl.appendChild(spinner);
            }

            const span = document.createElement('span');
            span.textContent = text;
            statusEl.appendChild(span);
        }

        function finish(kind, text) {
            state.busy = false;
            retryEl.disabled = false;
            setStatus(kind, text, false);
            emit();
        }

        function start() {
            const token = ++state.token;

            state.busy = true;
            state.lat = state.lng = state.accuracy = null;
            retryEl.disabled = true;
            resetSelect();
            setStatus('info', 'Mencari lokasi saat ini...', true);
            emit();

            return new Promise(function (resolve) {
                function done(kind, text) {
                    if (token !== state.token) { return resolve(); }
                    finish(kind, text);
                    resolve();
                }

                if (!('geolocation' in navigator)) {
                    return done('muted', 'GPS tidak tersedia di perangkat ini. Pilih lokasi secara manual.');
                }
                if (!window.isSecureContext) {
                    return done('muted', 'GPS hanya bisa dipakai lewat HTTPS atau localhost. Pilih lokasi secara manual.');
                }

                navigator.geolocation.getCurrentPosition(function (pos) {
                    if (token !== state.token) { return resolve(); }

                    const c = pos.coords;
                    state.lat = c.latitude;
                    state.lng = c.longitude;
                    state.accuracy = Math.round(c.accuracy);

                    const query = new URLSearchParams({ lat: c.latitude, lng: c.longitude, accuracy: c.accuracy });

                    fetch(options.detectUrl + '?' + query.toString(), {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    })
                        .then(function (res) {
                            if (!res.ok) { throw new Error('detect failed'); }
                            return res.json();
                        })
                        .then(function (data) {
                            if (token !== state.token) { return resolve(); }

                            if (data.status === 'terdeteksi' && data.location) {
                                const option = Array.from(selectEl.options).find(function (o) {
                                    return o.value === String(data.location.id);
                                });
                                if (option) {
                                    selectEl.value = option.value;
                                    return done('success', 'Terdeteksi: ' + data.location.name + ' (sekitar ' + data.distance_m + ' m)');
                                }
                            }

                            if (data.status === 'akurasi_rendah') {
                                return done('warning', 'Akurasi GPS rendah (sekitar ' + data.accuracy_m + ' m). Pilih lokasi secara manual.');
                            }

                            done('warning', 'Di luar jangkauan lokasi terdaftar. Pilih lokasi secara manual.');
                        })
                        .catch(function () {
                            done('muted', 'Gagal mendeteksi lokasi. Pilih lokasi secara manual.');
                        });
                }, function (err) {
                    done('muted', err.code === 1
                        ? 'Izin lokasi ditolak. Pilih lokasi secara manual.'
                        : 'Lokasi tidak dapat dibaca. Pilih lokasi secara manual.');
                }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
            });
        }

        selectEl.addEventListener('change', emit);
        retryEl.addEventListener('click', start);
        resetSelect();
        setStatus('muted', 'Lokasi belum dideteksi.', false);

        return {
            start: start,
            isBusy: function () { return state.busy; },
            onChange: function (fn) { state.listeners.push(fn); },
            // Payload untuk request; null selama mencari atau belum ada lokasi terpilih.
            value: function () {
                if (state.busy || !selectEl.value) { return null; }
                return { lokasi_id: Number(selectEl.value), lat: state.lat, lng: state.lng, accuracy: state.accuracy };
            },
            name: function () {
                const opt = selectEl.selectedOptions[0];
                return selectEl.value && opt ? opt.textContent : '';
            },
            locationId: function () { return selectEl.value ? Number(selectEl.value) : null; }
        };
    }

    window.LocationPicker = { mount: mount };
})();
</script>
