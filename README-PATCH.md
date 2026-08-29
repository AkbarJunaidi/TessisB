# Patch: Reset Password Manual + Optimasi Performa

Timpa file-file di zip ini ke folder project TessisB yang sama (struktur folder sudah cocok, tinggal overwrite). Ini melanjutkan patch sebelumnya (Keamanan Login + Lupa Password) - kalau belum ditimpa, timpa itu dulu baru zip ini, karena beberapa file di sini melanjutkan perubahan dari situ (`UserService.php`, `UserController.php`, `NotificationService.php`, `PasswordResetRequestService.php`).

## Setelah menimpa file, WAJIB jalankan migration baru:

```
php artisan migrate
```

Ada 2 migration baru:
1. `add_temp_password_plain_to_users_table` — 1 kolom baru di tabel `users`
2. `add_performance_indexes` — index baru di beberapa tabel (`projects`, `inventories`, `inventory_units`, `surat_jalans`, `tasks`)

Keduanya migration ADDITIVE (hanya menambah kolom/index) — aman dijalankan di database yang sudah berjalan, tidak mengubah/menghapus data yang ada.

## Ringkasan Perubahan

### 1. Reset Password sekarang manual (bukan password tetap)
Sebelumnya klik "Reset Password" langsung mengganti ke password default yang sama untuk semua user. Sekarang:
- Klik "Reset Password" di halaman Detail User membuka modal berisi form: **Password Baru** + **Konfirmasi Password Baru** (validasi: wajib, minimal 8 karakter, harus sama)
- Password baru disimpan ter-hash di kolom `password` (dipakai untuk login) **dan** disimpan lagi apa adanya di kolom baru `temp_password_plain` supaya bisa ditampilkan lagi
- Di halaman Detail User, ada info box (**hanya terlihat Super Admin**) menampilkan password hasil reset terakhir, supaya Super Admin bisa menyampaikannya ke user secara langsung
- Kolom `temp_password_plain` ditambahkan ke `$hidden` di model User, supaya tidak pernah ikut ter-ekspos lewat serialisasi JSON di bagian lain aplikasi - hanya sengaja ditampilkan di halaman Detail User

**Catatan keamanan yang perlu diketahui**: menyimpan password apa adanya (meski hanya utk ditampilkan ke Super Admin) adalah kompromi keamanan yang disengaja untuk memenuhi kebutuhan "password bisa dilihat lagi di Detail User" - karena project ini tidak mengirim email. Ini aman selama akses ke halaman Detail User benar-benar dibatasi hanya untuk Super Admin/Admin yang tepercaya.

### 2. Optimasi Performa
- **Cache notifikasi navbar** (`NotificationService`, 30 detik) — endpoint `notifications.active` di-poll browser tiap 60 detik oleh SETIAP admin yang sedang login. Tanpa cache, tiap poll menjalankan ulang beberapa query database untuk semua orang yang online bersamaan. Sekarang di-cache dan otomatis di-invalidate saat ada permintaan Lupa Password baru atau saat password direset, supaya hal yang urgent tetap terasa real-time.
- **Index database baru** pada kolom yang sering difilter/di-sort tapi sebelumnya belum ter-index: `projects.deadline`, `projects.event_date`, `projects.status`, `inventories.status`, `inventory_units.status`, `surat_jalans.status`, `tasks.status`. Query kalender project, notifikasi, dan filter status di Inventory/Kanban akan tetap cepat seiring data bertambah banyak ke depannya.
- Sudah dicek: query list Inventory & Activity Log sudah eager-loading dengan baik (tidak ada N+1) — tidak perlu diubah.

## File yang berubah di patch ini
- `database/migrations/2026_08_28_000001_add_temp_password_plain_to_users_table.php` (baru)
- `database/migrations/2026_08_28_000002_add_performance_indexes.php` (baru)
- `app/Models/User.php`
- `app/Http/Requests/User/ResetPasswordRequest.php` (baru)
- `app/Services/UserService.php`
- `app/Services/PasswordResetRequestService.php`
- `app/Services/Notification/NotificationService.php`
- `app/Http/Controllers/User/UserController.php`
- `resources/views/User/show.blade.php`

## Tidak ada yang dihapus/diubah di luar cakupan ini
Tidak ada file lama yang dihapus, tidak ada perubahan struktur folder atau alur bisnis modul lain di luar Reset Password dan performa.
