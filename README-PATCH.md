# Bugfix: Login Gagal Saat Centang "Ingat saya di perangkat ini"

Timpa 2 file ini ke folder project TessisB yang sama. **Tidak ada migration**, murni perbaikan bug validasi.

## Penyebab
Checkbox "Ingat saya di perangkat ini" di `login.blade.php` tidak punya atribut `value` eksplisit - checkbox HTML seperti ini, kalau dicentang, browser mengirim teks `"on"` secara default (bukan `true`/`1`). Sementara aturan validasi di `LoginRequest.php` mewajibkan field `remember` bernilai `boolean` ketat (`true`, `false`, `1`, `0`), yang menolak `"on"`. Akibatnya, begitu checkbox dicentang, validasi gagal khusus di field `remember` - dan karena halaman login cuma menampilkan pesan error spesifik untuk field email/password, yang muncul ke user malah pesan generik yang membingungkan ("Terjadi kesalahan saat memproses login Anda"), padahal email & password yang diketik sudah benar.

## Perbaikan
1. `app/Http/Requests/Auth/LoginRequest.php` - aturan `boolean` di field `remember` dihapus (field ini cukup `nullable`, tidak perlu divalidasi ketat karena nilainya sudah dinormalisasi lewat `$request->boolean('remember')` di `AuthenticatedSessionController`, yang memang dirancang menangani variasi nilai checkbox seperti `"on"`)
2. `resources/views/auth/login.blade.php` - checkbox diberi `value="1"` eksplisit, praktik standar supaya nilai yang dikirim jelas dan konsisten

## File yang berubah
- `app/Http/Requests/Auth/LoginRequest.php`
- `resources/views/auth/login.blade.php`
