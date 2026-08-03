@extends('layouts.app')

@section('title', 'Barang Pinjaman')

@section('content')
<div class="container-fluid p-0">

    <div class="mb-4">
        <h3 class="fw-bold text-dark m-0">Barang Pinjaman</h3>
        <p class="text-muted small m-0">Pantau seluruh barang yang masih dipinjam per project, dan konfirmasi pengembaliannya di sini.</p>
    </div>

    <div id="borrowedProjectsList">
        @forelse($projects as $project)
            @include('tracking.borrowed-items.partials.project-card', [
                'project' => $project,
                'units' => $unitsByProject->get($project->id, collect()),
            ])
        @empty
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-check2-circle fs-1 opacity-25 d-block mb-2"></i>
                    Tidak ada barang yang sedang dipinjam saat ini.
                </div>
            </div>
        @endforelse
    </div>

</div>

{{-- Modal Konfirmasi Pengembalian --}}
<div class="modal fade" id="confirmReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Konfirmasi Pengembalian Barang</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">Anda yakin ingin mengembalikan:</p>
                <ul id="confirmReturnList" class="list-unstyled mb-3"></ul>
                <p class="small text-muted m-0">Barang lain tetap berstatus dipinjam.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="confirmReturnSubmit" class="btn btn-primary">
                    <span class="btn-text">Ya, Konfirmasi</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const selectedUnitsByProject = {}; // { projectId: Set<unitId> }
    const confirmModalEl = document.getElementById('confirmReturnModal');
    const confirmModal = new bootstrap.Modal(confirmModalEl);
    let activeProjectId = null;

    function getSelectedSet(projectId) {
        if (!selectedUnitsByProject[projectId]) {
            selectedUnitsByProject[projectId] = new Set();
        }
        return selectedUnitsByProject[projectId];
    }

    function bindUnitCards(container) {
        container.querySelectorAll('.unit-card').forEach(function (card) {
            card.addEventListener('click', function () {
                const projectId = container.dataset.projectId;
                const unitId = this.dataset.unitId;
                const selected = getSelectedSet(projectId);

                if (this.classList.contains('unit-selected')) {
                    this.classList.remove('unit-selected');
                    this.querySelector('.unit-status-label').textContent = 'Dipinjam';
                    selected.delete(unitId);
                } else {
                    this.classList.add('unit-selected');
                    this.querySelector('.unit-status-label').textContent = 'Dikembalikan';
                    selected.add(unitId);
                }
            });
        });
    }

    document.querySelectorAll('.unit-cards').forEach(bindUnitCards);

    document.getElementById('borrowedProjectsList').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-konfirmasi');
        if (!btn) return;

        const projectId = btn.dataset.projectId;
        const selected = getSelectedSet(projectId);

        if (selected.size === 0) {
            alert('Pilih minimal 1 barang yang ingin dikembalikan.');
            return;
        }

        activeProjectId = projectId;

        const listEl = document.getElementById('confirmReturnList');
        listEl.innerHTML = '';
        document.querySelectorAll(`.unit-cards[data-project-id="${projectId}"] .unit-selected`).forEach(function (card) {
            const li = document.createElement('li');
            li.innerHTML = '<i class="bi bi-check-lg text-success me-1"></i> ' + card.dataset.unitLabel;
            listEl.appendChild(li);
        });

        confirmModal.show();
    });

    document.getElementById('confirmReturnSubmit').addEventListener('click', function () {
        if (!activeProjectId) return;

        const unitIds = Array.from(getSelectedSet(activeProjectId));
        const submitBtn = this;
        const btnText = submitBtn.querySelector('.btn-text');
        const spinner = submitBtn.querySelector('.spinner-border');

        submitBtn.disabled = true;
        btnText.classList.add('d-none');
        spinner.classList.remove('d-none');

        fetch(`/barang-pinjaman/${activeProjectId}/return`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ unit_ids: unitIds }),
        })
            .then(async (response) => {
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Gagal mengembalikan barang.');
                return data;
            })
            .then(function (data) {
                confirmModal.hide();
                selectedUnitsByProject[activeProjectId] = new Set();

                const projectCard = document.querySelector(`.project-card[data-project-id="${activeProjectId}"]`);

                if (!data.project_still_has_borrowed) {
                    // Aturan: kalau seluruh barang sudah kembali, project hilang dari daftar
                    if (projectCard) {
                        projectCard.remove();
                    }
                    if (!document.querySelector('.project-card')) {
                        document.getElementById('borrowedProjectsList').innerHTML = `
                            <div class="card border-0 shadow-sm rounded-3">
                                <div class="card-body text-center text-muted py-5">
                                    <i class="bi bi-check2-circle fs-1 opacity-25 d-block mb-2"></i>
                                    Tidak ada barang yang sedang dipinjam saat ini.
                                </div>
                            </div>`;
                    }
                } else if (projectCard) {
                    // Refresh isi accordion & badge jumlah dari data terbaru (tanpa reload halaman)
                    const unitsContainer = projectCard.querySelector('.unit-cards');
                    unitsContainer.innerHTML = data.units.map(function (unit) {
                        return `
                            <div class="col-6 col-md-3 col-lg-2">
                                <div class="border rounded-3 p-2 text-center unit-card" data-unit-id="${unit.id}" data-unit-label="${unit.inventory_name} #${unit.unit_number}">
                                    <div class="fw-semibold small">${unit.inventory_name} #${unit.unit_number}</div>
                                    <div class="small unit-status-label">Dipinjam</div>
                                    <div class="small text-muted">${unit.surat_jalan_nomor}</div>
                                </div>
                            </div>`;
                    }).join('');
                    bindUnitCards(unitsContainer);

                    const countBadge = projectCard.querySelector('.borrowed-count-badge');
                    if (countBadge) countBadge.textContent = data.remaining_units_count + ' unit dipinjam';
                }

                activeProjectId = null;
            })
            .catch(function (err) {
                alert(err.message);
            })
            .finally(function () {
                submitBtn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
            });
    });
});
</script>

<style>
    .unit-card {
        cursor: pointer;
        background-color: #e7f1ff;
        border-color: #b6d4fe !important;
        transition: background-color .15s ease, border-color .15s ease;
        user-select: none;
    }
    .unit-card.unit-selected {
        background-color: #d1e7dd;
        border-color: #a3cfbb !important;
    }
</style>
@endsection
