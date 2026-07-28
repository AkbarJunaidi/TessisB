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
                'view'   => 'Melihat surat jalan',
                'create' => 'Membuat surat jalan',
                'print'  => 'Cetak / download surat jalan',
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
                'view' => true, 'create' => true, 'print' => true,
            ],
            'data_integration' => [
                'view' => true, 'upload' => true, 'download' => true, 'delete' => true,
                'create_folder' => true, 'rename' => true,
            ],
            'user_management' => [
                'view_user' => true, 'create_user' => true, 'edit_user' => true, 'delete_user' => true,
                'reset_password' => true, 'change_role' => true,
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
                'view' => true, 'create' => false, 'print' => true,
            ],
            'data_integration' => [
                'view' => true, 'upload' => true, 'download' => true, 'delete' => true,
                'create_folder' => true, 'rename' => true,
            ],
            'user_management' => [
                'view_user' => false, 'create_user' => false, 'edit_user' => false, 'delete_user' => false,
                'reset_password' => false, 'change_role' => false,
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
                'view' => true, 'create' => false, 'print' => false,
            ],
            'data_integration' => [
                'view' => true, 'upload' => true, 'download' => true, 'delete' => false,
                'create_folder' => false, 'rename' => false,
            ],
            'user_management' => [
                'view_user' => false, 'create_user' => false, 'edit_user' => false, 'delete_user' => false,
                'reset_password' => false, 'change_role' => false,
            ],
        ],

    ],

];
