@extends('layouts.app')

@section('title', 'Barang Pinjaman')

@section('content')
<div class="container-fluid p-0">

    <div class="mb-4">
        <h3 class="fw-bold text-dark m-0">Barang Pinjaman</h3>
        <p class="d-none d-md-block text-muted small m-0">Pantau seluruh barang yang masih dipinjam per project, dan konfirmasi pengembaliannya di sini.</p>
    </div>

    <div id="borrowedProjectsList">
        @forelse($projects as $project)
            @include('tracking.borrowed-items.partials.project-card', [
                'project' => $project,
                'units' => $unitsByProject->get($project->id, collect()),
            ])
        @empty
        @endforelse

        @forelse($peminjamLangsung as $user)
            @include('tracking.borrowed-items.partials.user-card', [
                'user' => $user,
                'units' => $unitsByUser->get($user->id, collect()),
            ])
        @empty
        @endforelse

        @if($projects->isEmpty() && $peminjamLangsung->isEmpty())
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-check2-circle fs-1 opacity-25 d-block mb-2"></i>
                    Tidak ada barang yang sedang dipinjam saat ini.
                </div>
            </div>
        @endif
    </div>

</div>

{{-- Modal Konfirmasi - list-nya sekarang bisa campur: sebagian unit mau
     Dikembalikan, sebagian Rusak/Hilang (hasil klik-siklus di bawah). --}}
<div class="modal fade" id="confirmReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Konfirmasi Perubahan Barang</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Lokasi saat ini dicari tiap modal dibuka; dipakai untuk semua perubahan di bawah. --}}
                <div id="confirmLocationBar" class="mb-3"></div>

                <p class="small text-muted mb-2">Perubahan yang akan diproses:</p>
                <ul id="confirmReturnList" class="list-unstyled mb-2"></ul>
                <p class="small text-muted mb-2">Barang lain tetap berstatus dipinjam.</p>
                <p id="confirmLocationNote" class="small text-muted m-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="confirmReturnSubmit" class="btn btn-primary" disabled>
                    <span class="btn-text">Ya, Konfirmasi</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@include('inventory.partials.location-picker')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const confirmModalEl = document.getElementById('confirmReturnModal');
    const confirmModal = new bootstrap.Modal(confirmModalEl);

    const locationPicker = LocationPicker.mount(document.getElementById('confirmLocationBar'), {
        detectUrl: '{{ route('inventory.locations.detect') }}',
        locations: {{ \Illuminate\Support\Js::from($storageLocations->map(fn ($l) => ['id' => $l->id, 'name' => $l->name])->values()) }},
    });
    const submitButton = document.getElementById('confirmReturnSubmit');
    const locationNote = document.getElementById('confirmLocationNote');
    let isSubmitting = false;

    // Tombol Ya, Konfirmasi aktif hanya setelah lokasi siap (terdeteksi atau dipilih manual).
    function syncSubmitState() {
        const name = locationPicker.name();
        const hasHilang = activeGroupKey && Array.from(getStagedMap(activeGroupKey).values()).includes('Hilang');

        submitButton.disabled = isSubmitting || locationPicker.value() === null;
        locationNote.textContent = !name
            ? (locationPicker.isBusy() ? 'Menunggu lokasi saat ini...' : 'Pilih lokasi terlebih dahulu untuk melanjutkan.')
            : 'Dikembalikan/Rusak tercatat di ' + name + (hasHilang ? '; Hilang hanya dicatat dilaporkan di sana.' : '.');
    }

    locationPicker.onChange(syncSubmitState);

    // { groupKey: Map<unitId, statusTujuan> } - cuma unit yang statusnya
    // BUKAN "Dipinjam" (sudah di-cycle minimal 1x) yang tersimpan di sini.
    const stagedByGroup = {};
    let activeGroupKey = null;

    // Siklus status per klik. "Dipinjam" = kondisi awal/tidak dipilih,
    // jadi tidak pernah jadi target aksi - cuma titik "reset".
    const STATUS_CYCLE = ['Dipinjam', 'Dikembalikan', 'Rusak', 'Hilang'];

    // Kunci grup peminjam: per project atau per user.
    function getGroupKey(el) {
        return el.dataset.projectId ? `project-${el.dataset.projectId}` : `user-${el.dataset.userId}`;
    }

    // Peta unit yang dipilih (staged) untuk satu grup.
    function getStagedMap(groupKey) {
        if (!stagedByGroup[groupKey]) {
            stagedByGroup[groupKey] = new Map();
        }
        return stagedByGroup[groupKey];
    }

    // Klik kartu unit memutar status tujuan (STATUS_CYCLE) dan mencatatnya di peta staged.
    function bindUnitCards(container) {
        const groupKey = getGroupKey(container);

        container.querySelectorAll('.unit-card').forEach(function (card) {
            card.addEventListener('click', function () {
                const unitId = this.dataset.unitId;
                const label = this.querySelector('.unit-status-label');
                const currentIndex = STATUS_CYCLE.indexOf(label.textContent.trim());
                const nextStatus = STATUS_CYCLE[(currentIndex + 1) % STATUS_CYCLE.length];
                const staged = getStagedMap(groupKey);

                label.textContent = nextStatus;
                this.classList.remove('status-dikembalikan', 'status-rusak', 'status-hilang');
                this.classList.toggle('unit-selected', nextStatus !== 'Dipinjam');
                if (nextStatus !== 'Dipinjam') {
                    this.classList.add('status-' + nextStatus.toLowerCase());
                }

                if (nextStatus === 'Dipinjam') {
                    staged.delete(unitId);
                } else {
                    staged.set(unitId, nextStatus);
                }
            });
        });
    }

    document.querySelectorAll('.unit-cards').forEach(bindUnitCards);

    document.getElementById('borrowedProjectsList').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-konfirmasi');
        if (!btn) return;

        const groupKey = getGroupKey(btn);
        const staged = getStagedMap(groupKey);

        if (staged.size === 0) {
            AppUI.toast('Klik barang yang mau diubah statusnya dulu (Dikembalikan/Rusak/Hilang).');
            return;
        }

        activeGroupKey = groupKey;

        const listEl = document.getElementById('confirmReturnList');
        listEl.innerHTML = '';
        document.querySelectorAll(`.unit-cards[data-project-id="${btn.dataset.projectId || ''}"] .unit-selected, .unit-cards[data-user-id="${btn.dataset.userId || ''}"] .unit-selected`)
            .forEach(function (card) {
                const targetStatus = staged.get(card.dataset.unitId) || '-';
                const li = document.createElement('li');
                li.innerHTML = `<i class="bi bi-arrow-right-short text-primary"></i> ${card.dataset.unitLabel} &rarr; <strong>${targetStatus}</strong>`;
                listEl.appendChild(li);
            });

        confirmModal.show();
        locationPicker.start();
    });

    document.getElementById('confirmReturnSubmit').addEventListener('click', function () {
        if (!activeGroupKey) return;

        const lokasi = locationPicker.value();

        if (!lokasi) {
            AppUI.toast('Lokasi saat ini belum siap. Tunggu deteksi selesai atau pilih lokasi manual.');
            return;
        }

        const staged = getStagedMap(activeGroupKey);
        const submitBtn = this;
        const btnText = submitBtn.querySelector('.btn-text');
        const spinner = submitBtn.querySelector('.spinner-border');

        // Kelompokkan per status tujuan: Dikembalikan lewat return-by-ids, Rusak/Hilang lewat mark-status.
        const unitIdsByStatus = { Dikembalikan: [], Rusak: [], Hilang: [] };
        staged.forEach(function (status, unitId) { unitIdsByStatus[status].push(Number(unitId)); });

        isSubmitting = true;
        submitBtn.disabled = true;
        btnText.classList.add('d-none');
        spinner.classList.remove('d-none');

        const requests = [];

        if (unitIdsByStatus.Dikembalikan.length > 0) {
            requests.push(postJson('{{ route('borrowed-items.return-by-ids') }}', Object.assign({ unit_ids: unitIdsByStatus.Dikembalikan }, lokasi)));
        }
        ['Rusak', 'Hilang'].forEach(function (status) {
            if (unitIdsByStatus[status].length > 0) {
                requests.push(postJson('{{ route('borrowed-items.mark-status') }}', Object.assign({ unit_ids: unitIdsByStatus[status], status: status }, lokasi)));
            }
        });

        Promise.allSettled(requests)
            .then(function (results) {
                const failed = results.filter((r) => r.status === 'rejected');

                if (failed.length > 0) {
                    AppUI.toast(failed.map((r) => r.reason.message).join('\n'));
                    return;
                }

                // Cukup reload halaman: perubahan bisa campuran 3 jenis aksi, lebih aman daripada membangun ulang accordion.
                window.location.reload();
            })
            .finally(function () {
                isSubmitting = false;
                syncSubmitState();
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
            });
    });

    // POST JSON dengan CSRF; mengembalikan JSON respons.
    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        }).then(async function (response) {
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Gagal memproses barang.');
            return data;
        });
    }
});
</script>

@endsection
