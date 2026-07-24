# TessisB — Management Information System

Sistem informasi manajemen berbasis Laravel untuk **CV. Arindra Production**, mencakup manajemen inventory, tracking progress project/task ala Trello, manajemen file/folder, dan manajemen user dengan hak akses (Role + Permission Override).

## Fitur Utama

- **Autentikasi** — Login, manajemen sesi
- **Dashboard** — Ringkasan aktivitas sistem
- **Inventory** — CRUD barang, QR Code otomatis per item, laporan PDF (per item & seluruh data)
- **Tracking Progress** — Project & Task berbentuk board (list bisa ditambah sendiri, task bisa di-drag & drop antar list)
- **Integrasi Data** — Manajemen file & folder
- **User Management** — Role (Super Admin, Admin, Employee) + Permission Override per user
- **Activity Log** — Riwayat aktivitas pengguna

## Tech Stack

| Komponen | Versi/Teknologi |
|---|---|
| Backend | Laravel 13 |
| PHP | ^8.3 |
| Frontend | Blade + Bootstrap 5 (CDN) |
| Database | SQLite (default) / MySQL |
| PDF | barryvdh/laravel-dompdf |
| QR Code | simplesoftwareio/simple-qrcode |

## Requirement

Pastikan sudah terpasang di komputer/server Anda:

- PHP >= 8.3
- Composer
- Ekstensi PHP: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `gd` (untuk QR Code)
- Database: SQLite (bawaan, tidak perlu instalasi tambahan) **atau** MySQL >= 8.0

> Catatan: project ini murni Blade + Bootstrap 5 via CDN, **tidak ada proses build frontend** (Node.js/npm hanya diperlukan jika Anda ingin memodifikasi asset Vite bawaan Laravel, bukan untuk menjalankan aplikasi).

## Instalasi

### 1. Clone repository

```bash
git clone https://github.com/AkbarJunaidi/TessisB.git
cd TessisB
```

### 2. Install dependency PHP

```bash
composer install
```

### 3. Buat file environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Konfigurasi database

**Opsi A — SQLite (paling cepat, tanpa setup tambahan):**

```bash
touch database/database.sqlite
```

Pastikan `.env` berisi:

```env
DB_CONNECTION=sqlite
```

**Opsi B — MySQL:**

Buat database baru, lalu atur `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tessisb
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Jalankan migration & seeder

```bash
php artisan migrate --seed
```

Seeder akan membuat 3 akun default (lihat bagian [Akun Default](#akun-default) di bawah).

### 6. Buat symbolic link storage

Wajib dijalankan supaya foto barang & QR Code bisa tampil (disimpan di `storage/app/public`):

```bash
php artisan storage:link
```

### 7. Jalankan aplikasi

```bash
php artisan serve
```

Aplikasi bisa diakses di `http://localhost:8000`.

## Akun Default

Setelah `migrate --seed`, gunakan salah satu akun berikut untuk login:

| Role | Email | Password |
|---|---|---|
| Super Admin | `superadmin@example.com` | `password` |
| Admin | `admin@example.com` | `password` |
| Employee | `employee@example.com` | `password` |

> **Penting:** ganti password akun-akun ini sebelum dipakai di lingkungan production.

## Troubleshooting Singkat

- **Foto/QR Code tidak muncul** → pastikan sudah menjalankan `php artisan storage:link`
- **Perubahan di file Blade/PHP tidak muncul di production** → jalankan `php artisan optimize:clear` untuk membersihkan cache view/config/route
- **Error permission saat generate PDF/QR** → pastikan folder `storage` dan `bootstrap/cache` punya izin tulis (`chmod -R 775 storage bootstrap/cache` di Linux/macOS)

## Lisensi

Proyek internal milik CV. Arindra Production.
