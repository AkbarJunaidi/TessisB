<nav class="app-topbar navbar navbar-expand-lg border-bottom bg-white px-3 px-md-4 py-2">
    <div class="container-fluid p-0">

        {{-- Tombol buka sidebar: pakai atribut Bootstrap native (data-bs-toggle),
             otomatis accessible (aria, focus-trap, keyboard Esc) tanpa JS tambahan. --}}
        <button class="btn btn-light border rounded-3 me-3 d-lg-none" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar"
                aria-label="Buka menu navigasi">
            <i class="bi bi-list fs-5"></i>
        </button>

        <div>
            <span class="fw-semibold text-navy d-block" style="font-size:.95rem;">
                @yield('title', 'Dashboard')
            </span>
            <span class="text-muted d-none d-sm-block" style="font-size:.72rem;">
                Sistem Informasi Manajemen
            </span>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">

            {{-- Komponen waktu real-time --}}
            <div class="d-none d-sm-flex align-items-center bg-light border rounded-pill px-3 py-1 gap-2">
                <i class="bi bi-clock text-primary"></i>
                <div class="d-flex flex-column text-end lh-sm">
                    <span class="fw-semibold text-dark" style="font-size:.8rem;" id="realtime-date">Memuat tanggal...</span>
                    <span class="text-muted fw-medium" style="font-size:.72rem;" id="realtime-clock">--:--:--</span>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
    .app-topbar { position: sticky; top: 0; z-index: 1030; }
    .text-navy { color: var(--c-navy); }
</style>

{{-- Waktu nyata --}}
<script>
    function updateDateTime() {
        const now = new Date();
        const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        document.getElementById('realtime-date').textContent = now.toLocaleDateString('id-ID', dateOptions);
        document.getElementById('realtime-clock').textContent = now.toLocaleTimeString('id-ID', timeOptions) + ' WIB';
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>
