document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const startBtn = document.getElementById('btnGenerateAllReport');
    const modalEl = document.getElementById('generateReportModal');
    const modal = new bootstrap.Modal(modalEl);
    const progressBar = document.getElementById('generateReportProgressBar');
    const statusText = document.getElementById('generateReportStatusText');
    const downloadBtn = document.getElementById('generateReportDownloadBtn');
    const cancelBtn = document.getElementById('generateReportCancelBtn');
    const closeBtn = document.getElementById('generateReportCloseBtn');

    // ID laporan yang sedang diproses dan penanda Batal; processBatch() berhenti kirim request bila dibatalkan.
    let activeReportExportId = null;
    let cancelled = false;
    let activeAbortController = null;

    // Update progress bar proses laporan massal.
    function setProgress(processed, total) {
        const percent = total > 0 ? Math.round((processed / total) * 100) : 100;
        progressBar.style.width = percent + '%';
        progressBar.textContent = percent + '%';
        statusText.textContent = `Memproses ${processed} dari ${total} barang...`;
    }

    // Ganti tombol Batal menjadi Tutup saat proses selesai.
    function showFinishedState() {
        cancelBtn.classList.add('d-none');
        closeBtn.classList.remove('d-none');
    }

    // Proses laporan per batch lewat request berulang sampai selesai atau dibatalkan.
    function processBatch(reportExportId) {
        if (cancelled) {
            return;
        }

        activeAbortController = new AbortController();

        fetch(`/inventory/report/generate-all/${reportExportId}/batch`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            signal: activeAbortController.signal,
        })
            .then(response => response.json())
            .then(data => {
                if (cancelled) {
                    return;
                }

                setProgress(data.processed, data.total);

                if (!data.finished) {
                    processBatch(reportExportId);
                    return;
                }

                if (data.status === 'completed') {
                    statusText.textContent = 'Laporan selesai diproses.';
                    progressBar.classList.remove('progress-bar-animated');
                    downloadBtn.href = data.download_url;
                    downloadBtn.classList.remove('d-none');
                } else {
                    statusText.textContent = 'Gagal memproses laporan: ' + (data.error || 'Terjadi kesalahan.');
                    progressBar.classList.remove('progress-bar-animated', 'bg-primary');
                    progressBar.classList.add('bg-danger');
                }

                showFinishedState();
            })
            .catch((error) => {
                // Request dibatalkan lewat AbortController (tombol Batal) -
                // bukan kegagalan koneksi, jadi tidak perlu dicoba ulang.
                if (cancelled || error.name === 'AbortError') {
                    return;
                }

                statusText.textContent = 'Koneksi terputus, mencoba lagi...';
                setTimeout(() => processBatch(reportExportId), 2000);
            });
    }

    // Batalkan proses aktif: hentikan request berjalan dan beri tahu server.
    function cancelActiveReport() {
        cancelled = true;

        if (activeAbortController) {
            activeAbortController.abort();
        }

        const idToCancel = activeReportExportId;
        activeReportExportId = null;

        if (!idToCancel) {
            modal.hide();
            return;
        }

        statusText.textContent = 'Membatalkan...';

        // Beri tahu server supaya file sementara & baris datanya benar-
        // benar dihapus, bukan cuma berhenti di sisi browser saja.
        fetch(`/inventory/report/generate-all/${idToCancel}/cancel`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        }).finally(() => {
            modal.hide();
        });
    }

    if (startBtn) {
        startBtn.addEventListener('click', function () {
            // Reset tampilan & status setiap kali modal dibuka ulang.
            cancelled = false;
            activeReportExportId = null;

            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            progressBar.classList.add('progress-bar-animated');
            progressBar.classList.remove('bg-danger');
            statusText.textContent = 'Memulai...';
            downloadBtn.classList.add('d-none');
            cancelBtn.classList.remove('d-none');
            closeBtn.classList.add('d-none');

            modal.show();

            fetch('/inventory/report/generate-all/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            })
                .then(response => response.json())
                .then(data => {
                    if (cancelled) {
                        return;
                    }

                    activeReportExportId = data.report_export_id;
                    setProgress(0, data.total);
                    processBatch(data.report_export_id);
                })
                .catch(() => {
                    statusText.textContent = 'Gagal memulai proses laporan.';
                    showFinishedState();
                });
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', cancelActiveReport);
    }
});
