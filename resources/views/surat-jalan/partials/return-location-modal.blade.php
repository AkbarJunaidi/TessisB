{{--
    Modal "Lokasi pengembalian" untuk form Kembalikan Barang Surat Jalan.
    Pakai: tandai form dengan data-sj-return, lalu sertakan partial ini sekali per halaman ($storageLocations wajib ada).
    Tanpa lokasi penyimpanan aktif, form tetap dikirim langsung tanpa lokasi.
--}}
@include('inventory.partials.location-picker')

<div class="modal fade" id="sjReturnLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Lokasi Pengembalian</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="sjReturnLocationBar" class="mb-2"></div>
                <p id="sjReturnLocationNote" class="small text-muted m-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="sjReturnLocationSubmit" class="btn btn-primary" disabled>Kembalikan</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const locations = {{ \Illuminate\Support\Js::from($storageLocations->map(fn ($l) => ['id' => $l->id, 'name' => $l->name])->values()) }};
    if (!locations.length) { return; }

    const modalEl = document.getElementById('sjReturnLocationModal');
    const submitBtn = document.getElementById('sjReturnLocationSubmit');
    const note = document.getElementById('sjReturnLocationNote');
    let pendingForm = null;

    // Pindah ke body supaya tidak terpotong tab-pane/collapse di halaman project.
    document.body.appendChild(modalEl);

    const modal = new bootstrap.Modal(modalEl);
    const picker = LocationPicker.mount(document.getElementById('sjReturnLocationBar'), {
        detectUrl: '{{ route('inventory.locations.detect') }}',
        locations: locations,
    });

    function sync() {
        const name = picker.name();
        submitBtn.disabled = picker.value() === null;
        note.textContent = name
            ? 'Barang dikembalikan dan dicatat di ' + name + '.'
            : (picker.isBusy() ? 'Menunggu lokasi saat ini...' : 'Pilih lokasi terlebih dahulu untuk melanjutkan.');
    }
    picker.onChange(sync);

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.matches || !form.matches('form[data-sj-return]')) { return; }

        e.preventDefault();
        pendingForm = form;
        modal.show();
        picker.start();
    });

    submitBtn.addEventListener('click', function () {
        const lokasi = picker.value();
        if (!pendingForm || !lokasi) { return; }

        submitBtn.disabled = true;
        pendingForm.querySelectorAll('input[data-sj-loc]').forEach(function (el) { el.remove(); });
        Object.keys(lokasi).forEach(function (key) {
            if (lokasi[key] === null) { return; }
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.dataset.sjLoc = '1';
            input.value = lokasi[key];
            pendingForm.appendChild(input);
        });
        pendingForm.submit();
    });

    modalEl.addEventListener('hidden.bs.modal', function () { pendingForm = null; });
});
</script>
