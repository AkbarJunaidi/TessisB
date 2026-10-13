// Menu titik tiga di tabel memakai posisi fixed agar tidak terpotong atau memunculkan scrollbar di wadah tabel.
// Fase capture: instance dibuat dengan konfigurasi ini sebelum handler bawaan Bootstrap, juga untuk baris hasil tab AJAX.
document.addEventListener('click', function (event) {
    const toggle = event.target.closest('[data-row-menu]');
    if (!toggle) return;

    bootstrap.Dropdown.getOrCreateInstance(toggle, {
        popperConfig: function (config) {
            return Object.assign({}, config, { strategy: 'fixed' });
        }
    });
}, true);
