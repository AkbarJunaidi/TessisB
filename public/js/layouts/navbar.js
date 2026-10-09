// Perbarui tanggal dan jam di navbar.
function updateDateTime() {
    const now = new Date();
    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
    document.getElementById('realtime-date').textContent = now.toLocaleDateString('id-ID', dateOptions);
    document.getElementById('realtime-clock').textContent = now.toLocaleTimeString('id-ID', timeOptions) + ' WIB';
}
updateDateTime();
setInterval(updateDateTime, 1000);
