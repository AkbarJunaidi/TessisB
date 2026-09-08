UPDATE BESAR: Halaman Kontak - Statistik, Filter A-Z, Detail Kontak,
Total Pendapatan, Tombol Kontak Cepat
========================================================================
(Lanjutan dari TessisB-kontak-tampilan-card.zip - pasang setelah itu)

RINGKASAN
---------
Terinspirasi dari referensi gambar yang dikirim, TANPA badge status
(Prospek/Hot Lead/dst - fitur ini tidak punya konsep status kontak).

1. KARTU STATISTIK (atas halaman)
   - Total Kontak
   - Punya WhatsApp (hitung yang toggle has_whatsapp aktif)
   - Kontak Baru Bulan Ini
   - Total Pendapatan (diganti dari referensi "Belum Dihubungi 30 Hari"
     karena project ini TIDAK punya data "terakhir dihubungi" - lihat
     CATATAN PENTING di bawah)

2. FILTER HURUF A-Z
   Klik salah satu huruf untuk filter Kontak yang namanya diawali
   huruf itu. Tombol "Semua" muncul untuk reset filter huruf.

3. CARD KONTAK (tanpa badge, tanpa nomor urut)
   - Avatar bulat berisi inisial nama, warna otomatis beda-beda per
     kontak (deterministik dari nama, jadi kontak yang sama selalu
     dapat warna yang sama)
   - Nama, Perusahaan (atau "Perorangan" kalau kosong)
   - No. HP/WA & Email - HANYA tampil kalau memang diisi
   - Nominal hijau = Total Pendapatan dari kontak ini (lihat poin 4)
   - 3 tombol aksi cepat (Telepon/WhatsApp/Email) - MASING-MASING
     tombol HANYA muncul kalau data terkait terisi:
       - Tombol Telepon: muncul kalau No. HP/WA diisi
       - Tombol WhatsApp: muncul HANYA kalau No. HP/WA diisi DAN
         toggle "Nomor ini punya WhatsApp" diaktifkan saat input/edit
         Kontak (nomor telepon belum tentu nomor WhatsApp, makanya
         dipisah jadi 2 kondisi)
       - Tombol Email: muncul kalau Email diisi
   - Klik di mana saja di card (selain 3 tombol aksi cepat itu) ->
     masuk ke halaman Detail Kontak

4. TOTAL PENDAPATAN PER KONTAK (di card & di halaman Detail)
   Dihitung OTOMATIS dengan mencocokkan nama Kontak terhadap kolom
   `client` (teks bebas) di tabel projects - lalu dijumlahkan seluruh
   Pendapatan (bukan Estimasi Pendapatan) dari Project-Project yang
   cocok. Pencocokan pakai nama yang disamakan huruf kecil & dirapikan
   spasinya, TAPI TETAP HARUS SAMA PERSIS PENULISANNYA. Lihat CATATAN
   PENTING soal keterbatasan ini di bawah.

5. HALAMAN DETAIL KONTAK (BARU - sebelumnya belum ada!)
   Klik card -> halaman detail berisi:
   - Info kontak lengkap (No. HP/WA, WhatsApp, Email, Alamat)
   - Catatan (field BARU - textarea bebas, bisa diisi apa saja
     tentang kontak ini)
   - Total Pendapatan dari kontak ini
   - Riwayat Project yang cocok (nama project, status, tanggal event,
     pendapatan per project) - klik salah satu baris project untuk
     langsung ke Detail Project itu
   - Tombol Edit & Hapus (dipindah ke sini dari card daftar, supaya
     card daftar lebih bersih & fokus ke tombol kontak cepat)

CATATAN PENTING - KETERBATASAN PENCOCOKAN
-------------------------------------------
Karena Kontak sengaja dibuat TIDAK terhubung (bukan foreign key) ke
Project, "Total Pendapatan" & "Riwayat Project" di atas dihitung
dengan mencocokkan TEKS nama Kontak vs kolom client di Project. Ini
berarti:
  - Kalau nama Kontak "Budi Santoso" tapi di Project field client-nya
    ditulis "Pak Budi" atau "Budi S." -> TIDAK akan cocok, datanya
    tidak akan muncul
  - Ini pernah dikonfirmasi sebagai batasan yang bisa diterima saat
    fitur ini direncanakan
  - Kartu statistik "Belum Dihubungi 30 Hari" dari referensi gambar
    SENGAJA tidak dibuat, karena project ini tidak punya data kapan
    terakhir kali kontak itu dihubungi - membuat data itu akan
    berarti mengarang informasi yang tidak benar-benar ada. Sebagai
    gantinya dipasang kartu "Total Pendapatan" yang datanya nyata.

FILE BARU
---------
- database/migrations/2026_09_06_140000_add_whatsapp_notes_to_contacts_table.php
  Tambah kolom has_whatsapp (boolean, default false), notes (text,
  nullable), dan phone diubah jadi nullable (pakai raw SQL karena
  package doctrine/dbal belum terpasang di project ini - migration
  sudah dibuat aman untuk MySQL/PostgreSQL, dan dilewati kalau
  environment-nya SQLite).
- resources/views/contact/show.blade.php (halaman Detail Kontak baru)

FILE YANG DIUBAH
-----------------
- app/Models/Contact.php
  + tambah has_whatsapp, notes ke $fillable & cast has_whatsapp jadi
    boolean
  + tambah accessor initials (inisial nama utk avatar) & avatar_color
    (warna avatar deterministik dari nama)
  + tambah method matchedProjects() - cari Project yang nama client-
    nya cocok dengan nama Kontak (case-insensitive, trim spasi)
  + tambah accessor total_income - jumlah Pendapatan dari seluruh
    Project yang cocok

- app/Http/Requests/Contact/ContactRequest.php
  + phone jadi nullable (sebelumnya wajib)
  + tambah has_whatsapp (boolean) & notes (nullable) ke validasi
  + prepareForValidation(): normalisasi checkbox has_whatsapp jadi
    boolean eksplisit (checkbox HTML tidak terkirim sama sekali kalau
    tidak dicentang)

- app/Services/Contact/ContactService.php
  + tambah getStats() - hitung 4 angka statistik di atas
  + getAllPaginated() sekarang terima filter 'letter' (huruf awal
    nama) selain 'search', dan otomatis menempelkan total_income ke
    setiap Kontak lewat 1 QUERY BATCH (bukan query berulang per
    kontak, supaya tetap cepat walau daftar Kontak-nya banyak)
  + createContact()/updateContact() ikut simpan has_whatsapp & notes

- app/Http/Controllers/Contact/ContactController.php
  + PENTING: tambah method show() yang SEBELUMNYA BELUM ADA. Route
    resource Kontak sejak awal sudah otomatis mendaftarkan route
    GET /contacts/{contact} (bagian dari Route::resource bawaan
    Laravel), tapi controller-nya belum punya method show() - kalau
    ada yang membuka URL itu sebelumnya, akan error "method tidak
    ditemukan". Sekarang sudah diperbaiki sekaligus dengan halaman
    Detail Kontak yang baru.
  + index() sekarang juga kirim $stats ke view

- resources/views/contact/index.blade.php
  Didesain ulang total: kartu statistik, filter A-Z, card dengan
  avatar & tombol kontak cepat kondisional, klik card -> Detail
  Kontak (bukan langsung Edit lagi)

- resources/views/contact/partials/form.blade.php
  + tambah toggle "Nomor ini punya WhatsApp" di bawah field No. HP/WA
  + tambah field Catatan (textarea) sebelum tombol Simpan
  + No. HP/WA tidak lagi wajib diisi (tanda bintang dihapus)

CARA PASANG
-----------
1. Tempel isi zip ini ke root project (menimpa file yang sudah ada di
   path yang sama; contact/show.blade.php adalah file baru).
2. WAJIB jalankan migration:
     php artisan migrate
3. TIDAK perlu composer dump-autoload.

CARA TEST
---------
1. Buka halaman Kontak -> pastikan 4 kartu statistik muncul dengan
   angka yang masuk akal.
2. Klik salah satu huruf A-Z -> daftar ke-filter sesuai huruf awal
   nama. Klik "Semua" untuk reset.
3. Tambah Kontak baru -> isi No. HP/WA + aktifkan toggle "punya
   WhatsApp" -> submit -> di card, tombol Telepon & WhatsApp harus
   muncul keduanya.
4. Tambah Kontak lain -> isi No. HP/WA TANPA aktifkan toggle WhatsApp
   -> di card, tombol Telepon muncul, tombol WhatsApp TIDAK muncul.
5. Tambah Kontak lain lagi -> KOSONGKAN No. HP/WA & Email -> di card,
   kedua tombol itu (Telepon & WhatsApp) & tombol Email semua TIDAK
   muncul.
6. Klik salah satu card (bukan di tombol aksi cepatnya) -> harus
   masuk ke halaman Detail Kontak, bukan Edit.
7. Di halaman Detail, isi Catatan lewat tombol Edit -> simpan ->
   pastikan catatan muncul di halaman Detail.
8. Buat 1 Project dengan field Client PERSIS SAMA dengan nama salah
   satu Kontak (termasuk huruf besar/kecil boleh beda, tapi kata-
   katanya harus sama), isi Data Keuangan (Pendapatan) di Project itu
   -> buka Detail Kontak yang namanya cocok -> pastikan Project itu
   muncul di "Riwayat Project" dan nominalnya ikut kehitung di "Total
   Pendapatan".
9. Klik tombol Telepon/WhatsApp/Email di card -> pastikan card TIDAK
   ikut membuka halaman Detail (klik tombol tidak boleh "nembus" ke
   klik card di belakangnya).

CATATAN TEKNIS
--------------
Sudah dicek: php -l lolos di semua file PHP, tag Blade seimbang di
semua view yang dibuat/diubah, scanner cross-namespace-import tetap
bersih (2 bug lama yang sengaja ditunda, tidak berubah), dan seluruh
64 route (termasuk ContactController yang sekarang resmi valid
sebagai resource controller lengkap - show() sudah ada) ter-resolve
ke method yang benar.
