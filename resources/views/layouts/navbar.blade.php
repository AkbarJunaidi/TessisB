<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-2 shadow-sm">
    <div class="container-fluid p-0">
        <button class="btn btn-outline-secondary btn-sm me-3" id="menu-toggle">
            <i class="bi bi-list fs-5"></i>
        </button>

        <span class="navbar-text fw-medium text-secondary d-none d-md-inline">
            Tesis
        </span>

        <div class="ms-auto d-flex align-items-center">
            <!--komponen waktu-->
            <div class="d-flex align-items-center bg-light border rounded-pill px-3 py-1 shadow-sm gap-2">
                <!--tanggal dan waktu-->
                <div class="d-flex flex-column text-end" style="line-height: 1.1;">
                        <span class="fw-semibold text-dark" style="font-size: 0.85rem;" id="realtime-date">
                            Memuat tanggal...
                        </span>
                    <span class="text-muted fw-bold" style="font-size: 0.75rem;" id="realtime-clock">
                        --:--:--
                    </span>
                </div>
            </div>
        </div>
    </div>
</nav>

<!--waktu nyata-->
<script>
    function updateDateTime()
    {
        const now = new Date();
        const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = now.toLocaleDateString('id-ID', dateOptions);
        const timeOptions = { hour: '2-digit', minute: '2-digit', hour12: false };
        const formattedTime = now.toLocaleTimeString('id-ID', timeOptions);

        document.getElementById('realtime-date').textContent = formattedDate;
        document.getElementById('realtime-clock').textContent = formattedTime + ' WIB';
    }

    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>
