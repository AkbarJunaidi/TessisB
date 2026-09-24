<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Katalog Modul & Aksi Permission
    |--------------------------------------------------------------------------
    |
    | Modul & aksi yang ditampilkan pada Card "Hak Akses Pengguna" di halaman
    | Create/Edit User. Dashboard & Activity Log sengaja tidak dimasukkan
    | sesuai permintaan (keduanya tidak diatur lewat permission override).
    |
    */

    'modules' => [

        'inventory' => [
            'label' => 'Inventory',
            'icon'  => 'bi-box-seam',
            'actions' => [
                'view'         => 'Melihat data inventory',
                'create'       => 'Tambah data inventory',
                'edit'         => 'Ubah data inventory',
                'delete'       => 'Hapus data inventory',
                'upload_image' => 'Upload gambar barang',
                'download_pdf' => 'Download PDF inventory',
                'print_qr'     => 'Cetak / QR Code',
            ],
        ],

        'tracking_progress' => [
            'label' => 'Tracking Progress',
            'icon'  => 'bi-kanban',
            'actions' => [
                'view'           => 'Melihat project & task',
                'create_project' => 'Buat project baru',
                'edit_project'   => 'Ubah project',
                'delete_project' => 'Hapus project',
                'create_task'    => 'Buat task',
                'edit_task'      => 'Ubah task',
                'delete_task'    => 'Hapus task',
                'update_status'  => 'Ubah status task',
            ],
        ],

        'surat_jalan' => [
            'label' => 'Surat Jalan',
            'icon'  => 'bi-file-earmark-text',
            'actions' => [
                'view'  => 'Melihat surat jalan',
                'print' => 'Cetak / download surat jalan',
            ],
        ],

        'data_integration' => [
            'label' => 'Integrasi Data',
            'icon'  => 'bi-hdd-network',
            'actions' => [
                'view'          => 'Melihat file & folder',
                'upload'        => 'Upload file',
                'download'      => 'Download file',
                'delete'        => 'Hapus file / folder',
                'create_folder' => 'Buat folder',
                'rename'        => 'Ubah nama file / folder',
            ],
        ],

        'finance' => [
            'label' => 'Data Keuangan',
            'icon'  => 'bi-cash-coin',
            'actions' => [
                'view'          => 'Melihat data keuangan project',
                'manage'        => 'Mengisi / mengubah data keuangan project',
                'export_report' => 'Export laporan keuangan bulanan (PDF)',
            ],
        ],

        'borrowed_items' => [
            'label' => 'Barang Pinjaman',
            'icon'  => 'bi-box-arrow-in-left',
            'actions' => [
                'view'           => 'Melihat daftar barang pinjaman',
                'process_return' => 'Proses pengembalian barang pinjaman',
            ],
        ],

        'user_management' => [
            'label' => 'User Management',
            'icon'  => 'bi-people',
            'actions' => [
                'view_user'      => 'Melihat data user',
                'create_user'    => 'Tambah user baru',
                'edit_user'      => 'Ubah data user',
                'delete_user'    => 'Hapus user',
                'reset_password' => 'Reset password user',
                'change_role'    => 'Ubah role user',
            ],
        ],

        // Default hanya Super Admin (lihat role_defaults di bawah) - Admin
        // bisa diberi akses ini per-user lewat Permission Override.
        'notifikasi_sistem' => [
            'label' => 'Notifikasi Sistem',
            'icon'  => 'bi-bell',
            'actions' => [
                'delete' => 'Hapus notifikasi sistem (berlaku untuk semua user)',
                'kirim'  => 'Kirim pengumuman baru',
            ],
        ],

        // 1 aksi saja ('view') - siapa yang punya ini bisa pakai SEMUA mode
        // di dalam fitur Scan (Pinjam/Kembalikan/Rusak/Hilang), sengaja
        // TIDAK ikut/turunan dari permission modul 'inventory'.
        'scan_barang' => [
            'label' => 'Scan Barang',
            'icon'  => 'bi-upc-scan',
            'actions' => [
                'view' => 'Akses fitur Scan Barcode (Pinjam/Kembalikan/Rusak/Hilang)',
            ],
        ],

        // Default TERBUKA untuk 3 role (lihat role_defaults) - menyamakan
        // akses yang SUDAH berlaku sebelum modul ini didaftarkan (CRUD
        // Kontak sebelumnya tidak dibatasi sama sekali), supaya tidak ada
        // yang tiba-tiba kehilangan akses begitu modul ini aktif.
        'kontak' => [
            'label' => 'Kontak',
            'icon'  => 'bi-person-lines-fill',
            'actions' => [
                'view'   => 'Melihat data kontak',
                'create' => 'Tambah kontak baru',
                'edit'   => 'Ubah data kontak',
                'delete' => 'Hapus kontak',
            ],
        ],

        // force_delete beda dari view/restore - bukan berarti "hapus" biasa,
        // ini permanen & tidak bisa dibatalkan (lihat role_defaults, default
        // HANYA Super Admin, sama seperti Reset Password User).
        'trash' => [
            'label' => 'Trash',
            'icon'  => 'bi-trash3',
            'actions' => [
                'view'         => 'Melihat isi Trash',
                'restore'      => 'Memulihkan data dari Trash',
                'force_delete' => 'Menghapus data secara permanen',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Default Permission per Role
    |--------------------------------------------------------------------------
    |
    | Dipakai sebagai:
    | 1. Template awal saat Role dipilih di form (auto-centang via JS).
    | 2. Pembanding untuk menentukan status "Custom Permission: Aktif/Tidak".
    | 3. Nilai yang dipakai saat user tidak memiliki permission_overrides.
    |
    | Disusun mengikuti akses yang sudah berlaku hari ini lewat RoleMiddleware
    | di routes/web.php (super_admin & admin = penuh di Inventory/Tracking/
    | Integrasi Data, hanya super_admin yang punya akses User Management).
    |
    */

    'role_defaults' => [

        'super_admin' => [
            'inventory' => [
                'view' => true, 'create' => true, 'edit' => true, 'delete' => true,
                'upload_image' => true, 'download_pdf' => true, 'print_qr' => true,
            ],
            'tracking_progress' => [
                'view' => true, 'create_project' => true, 'edit_project' => true, 'delete_project' => true,
                'create_task' => true, 'edit_task' => true, 'delete_task' => true, 'update_status' => true,
            ],
            'surat_jalan' => [
                'view' => true, 'print' => true,
            ],
            'data_integration' => [
                'view' => true, 'upload' => true, 'download' => true, 'delete' => true,
                'create_folder' => true, 'rename' => true,
            ],
            'finance' => [
                'view' => true, 'manage' => true, 'export_report' => true,
            ],
            'borrowed_items' => [
                'view' => true, 'process_return' => true,
            ],
            'user_management' => [
                'view_user' => true, 'create_user' => true, 'edit_user' => true, 'delete_user' => true,
                'reset_password' => true, 'change_role' => true,
            ],
            'notifikasi_sistem' => [
                'delete' => true, 'kirim' => true,
            ],
            'scan_barang' => [
                'view' => true,
            ],
            'kontak' => [
                'view' => true, 'create' => true, 'edit' => true, 'delete' => true,
            ],
            'trash' => [
                'view' => true, 'restore' => true, 'force_delete' => true,
            ],
        ],

        'admin' => [
            'inventory' => [
                'view' => true, 'create' => true, 'edit' => true, 'delete' => true,
                'upload_image' => true, 'download_pdf' => true, 'print_qr' => true,
            ],
            'tracking_progress' => [
                'view' => true, 'create_project' => true, 'edit_project' => true, 'delete_project' => true,
                'create_task' => true, 'edit_task' => true, 'delete_task' => true, 'update_status' => true,
            ],
            'surat_jalan' => [
                'view' => true, 'print' => true,
            ],
            'data_integration' => [
                'view' => true, 'upload' => true, 'download' => true, 'delete' => true,
                'create_folder' => true, 'rename' => true,
            ],
            'finance' => [
                // Default: HANYA Super Admin yang bisa akses data keuangan.
                // Bisa diaktifkan per-user lewat Permission Override di Edit User.
                'view' => false, 'manage' => false, 'export_report' => false,
            ],
            'borrowed_items' => [
                'view' => true, 'process_return' => true,
            ],
            'user_management' => [
                'view_user' => false, 'create_user' => false, 'edit_user' => false, 'delete_user' => false,
                'reset_password' => false, 'change_role' => false,
            ],
            // Default nonaktif - Super Admin bisa aktifkan lewat Permission
            // Override di Edit User kalau admin tertentu perlu akses ini.
            'notifikasi_sistem' => [
                'delete' => false, 'kirim' => false,
            ],
            'scan_barang' => [
                'view' => false,
            ],
            // Default TERBUKA (view/edit/dst) - sama seperti akses yang
            // sudah berlaku sebelum modul ini didaftarkan, tidak ada
            // pengurangan akses untuk Admin yang sudah ada.
            'kontak' => [
                'view' => true, 'create' => true, 'edit' => true, 'delete' => true,
            ],
            // force_delete default nonaktif - sama seperti sebelum modul
            // ini didaftarkan (dulu hardcode HANYA Super Admin).
            'trash' => [
                'view' => true, 'restore' => true, 'force_delete' => false,
            ],
        ],

        'employee' => [
            'inventory' => [
                'view' => false, 'create' => false, 'edit' => false, 'delete' => false,
                'upload_image' => false, 'download_pdf' => false, 'print_qr' => false,
            ],
            'tracking_progress' => [
                'view' => true, 'create_project' => false, 'edit_project' => false, 'delete_project' => false,
                'create_task' => true, 'edit_task' => true, 'delete_task' => false, 'update_status' => true,
            ],
            'surat_jalan' => [
                'view' => true, 'print' => false,
            ],
            'data_integration' => [
                'view' => true, 'upload' => true, 'download' => true, 'delete' => false,
                'create_folder' => false, 'rename' => false,
            ],
            'finance' => [
                'view' => false, 'manage' => false, 'export_report' => false,
            ],
            'borrowed_items' => [
                'view' => true, 'process_return' => false,
            ],
            'user_management' => [
                'view_user' => false, 'create_user' => false, 'edit_user' => false, 'delete_user' => false,
                'reset_password' => false, 'change_role' => false,
            ],
            'notifikasi_sistem' => [
                'delete' => false, 'kirim' => false,
            ],
            'scan_barang' => [
                'view' => false,
            ],
            // Default TERBUKA - sama seperti akses yang sudah berlaku
            // sebelum modul ini didaftarkan (Employee juga sudah bisa
            // kelola Kontak dari dulu).
            'kontak' => [
                'view' => true, 'create' => true, 'edit' => true, 'delete' => true,
            ],
            // Trash memang tidak pernah dibuka untuk Employee.
            'trash' => [
                'view' => false, 'restore' => false, 'force_delete' => false,
            ],
        ],

    ],

];
