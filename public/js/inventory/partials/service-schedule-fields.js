document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('use_servis_toggle');
    const hidden = document.getElementById('use_servis_hidden');
    const label  = document.getElementById('use_servis_label');
    const fields = document.getElementById('servisFields');

    toggle.addEventListener('change', function () {
        hidden.value = toggle.checked ? '1' : '0';
        label.textContent = toggle.checked ? 'Ya' : 'Tidak';
        fields.classList.toggle('d-none', !toggle.checked);
    });
});
