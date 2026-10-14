// Tombol Kembali sungguhan: <a href="tujuan-bawaan" data-back> kembali ke halaman sebelumnya di riwayat browser.
// href dipakai hanya bila tidak ada riwayat yang layak: halaman dibuka langsung atau di tab baru, datang dari situs lain,
// halaman sebelumnya adalah halaman yang sama (mis. setelah simpan atau error validasi), atau formulir tambah/ubah yang baru disimpan.
(function () {
    'use strict';

    function canGoBack() {
        if (history.length < 2 || !document.referrer) return false;

        let ref;
        try { ref = new URL(document.referrer); } catch (e) { return false; }

        if (ref.origin !== window.location.origin) return false;
        if (ref.pathname === window.location.pathname) return false;
        return !/\/(create|edit)\/?$/.test(ref.pathname);
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[data-back]');
        if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (!canGoBack()) return;

        event.preventDefault();
        history.back();
    });
})();
