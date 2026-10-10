{{-- Peringatan servis sebelum barang dipakai (boleh dilanjutkan). Pakai: ServisWarning.confirm([{inventory_id, qty}], preferLocationId, container?).then(ok => ...);
     dengan container, peringatan tampil inline di elemen itu (untuk dipakai di dalam modal lain). --}}
<div class="modal fade" id="servisWarningModal" tabindex="-1" aria-labelledby="servisWarningTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-semibold" id="servisWarningTitle">
                    <i class="bi bi-exclamation-triangle text-warning me-1"></i>Perhatian: Servis Barang
                </h5>
                <button type="button" class="btn-close" data-servis-cancel aria-label="Tutup"></button>
            </div>
            <div class="modal-body py-4">
                <p class="small text-muted mb-3">Unit berikut akan dipakai dan servisnya sudah dekat atau terlewat. Barang tetap bisa dipakai bila diperlukan.</p>
                <div id="servisWarningList"></div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary px-3" data-servis-cancel>Batal</button>
                <button type="button" class="btn btn-warning px-4" id="servisWarningProceed">Tetap Pakai</button>
            </div>
        </div>
    </div>
</div>

<script>
window.ServisWarning = (function () {
    const checkUrl = @json(route('inventory.servis-check'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());

    // Escape teks agar aman disisipkan ke HTML.
    function esc(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    // Susun HTML daftar barang dan unit yang servisnya terlambat/segera.
    function render(warnings) {
        return warnings.map(function (w) {
            const rows = w.units.map(function (u) {
                const late = u.state === 'terlambat';
                return `<div class="d-flex justify-content-between align-items-start gap-2 small py-1">
                    <span>Unit #${esc(u.unit_number)}</span>
                    <span class="text-end ${late ? 'text-danger' : 'text-warning'}">
                        <strong>${late ? 'Servis terlewat' : 'Segera servis'}</strong><br>${esc(u.pesan)}
                    </span>
                </div>`;
            }).join('');
            return `<div class="border rounded-3 p-3 mb-2"><div class="fw-semibold mb-1">${esc(w.inventory)}</div>${rows}</div>`;
        }).join('');
    }

    // Minta konfirmasi di dalam container (tanpa modal); resolve true bila lanjut.
    function askInline(warnings, container) {
        return new Promise(function (resolve) {
            container.innerHTML = `<div class="alert alert-warning mb-0">
                <div class="fw-semibold mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Perhatian: Servis Barang</div>
                <p class="small mb-2">Unit berikut akan dipakai dan servisnya sudah dekat atau terlewat. Barang tetap bisa dipakai bila diperlukan.</p>
                ${render(warnings)}
                <div class="d-flex justify-content-end gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-inline-cancel>Batal</button>
                    <button type="button" class="btn btn-sm btn-warning" data-inline-proceed>Tetap Pakai</button>
                </div>
            </div>`;
            container.querySelector('[data-inline-cancel]').onclick = function () { container.innerHTML = ''; resolve(false); };
            container.querySelector('[data-inline-proceed]').onclick = function () { container.innerHTML = ''; resolve(true); };
        });
    }

    // Minta konfirmasi lewat modal; resolve true bila user memilih lanjut.
    function ask(warnings) {
        return new Promise(function (resolve) {
            const el = document.getElementById('servisWarningModal');
            const modal = bootstrap.Modal.getOrCreateInstance(el);
            let decided = false;

            // Selesaikan Promise sekali saja lalu lepas listener.
            function finish(result) {
                if (decided) return;
                decided = true;
                el.removeEventListener('hidden.bs.modal', onHidden);
                modal.hide();
                resolve(result);
            }
            // Modal ditutup tanpa memilih dianggap batal.
            function onHidden() { finish(false); }

            document.getElementById('servisWarningList').innerHTML = render(warnings);
            document.getElementById('servisWarningProceed').onclick = function () { finish(true); };
            el.querySelectorAll('[data-servis-cancel]').forEach(function (b) { b.onclick = function () { finish(false); }; });
            el.addEventListener('hidden.bs.modal', onHidden);
            modal.show();
        });
    }

    // Gagal cek (jaringan/server) tidak boleh menghalangi pemakaian barang.
    function confirm(items, preferLocationId, container) {
        return fetch(checkUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ items: items, prefer_location_id: preferLocationId || null }),
        })
            .then(function (res) { return res.ok ? res.json() : { warnings: [] }; })
            .then(function (data) {
                if (!data.warnings || !data.warnings.length) return true;
                return container ? askInline(data.warnings, container) : ask(data.warnings);
            })
            .catch(function () { return true; });
    }

    return { confirm: confirm };
})();
</script>
