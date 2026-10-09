{{-- Komponen Lokasi saat ini (GPS + pilihan manual). Pakai: LocationPicker.mount(el, { detectUrl, locations: [{id, name}] }) lalu .start() / .value().
     Server menghitung ulang metode lokasi dari koordinat; nilai di sini hanya masukan. --}}
<script src="{{ \App\Support\AppAsset::url('js/inventory/partials/location-picker.js') }}"></script>
