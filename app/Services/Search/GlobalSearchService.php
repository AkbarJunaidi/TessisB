<?php

namespace App\Services\Search;

use App\Models\Contact;
use App\Models\Inventory;
use App\Models\Project;
use App\Models\SuratJalan;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Pencarian global navbar - satu keyword dicek ke beberapa modul
 * (Project, Inventory, Kontak, Surat Jalan) sekaligus.
 *
 * Setiap modul di-skip diam-diam (bukan error) kalau user yang sedang
 * login tidak punya permission "view" untuk modul itu - dipakai
 * `hasPermission()` yang sama seperti yang sudah dipakai controller
 * masing-masing modul (lihat config/permissions.php), supaya hasil
 * pencarian tidak pernah menampilkan data yang seharusnya tidak boleh
 * dilihat user tersebut.
 */
class GlobalSearchService
{
    /**
     * Batas jumlah baris per kategori, biar halaman hasil tetap ringkas
     * (bukan pencarian data lengkap/berpaginasi, cuma jalan pintas).
     */
    protected const LIMIT_PER_CATEGORY = 10;

    /**
     * @return array<string, array{label:string, icon:string, route:string, items: Collection}>
     */
    public function search(string $keyword, User $user): array
    {
        $keyword = trim($keyword);
        $results = [];

        if ($keyword === '') {
            return $results;
        }

        if ($user->hasPermission('tracking_progress', 'view')) {
            $results['projects'] = [
                'label' => 'Project',
                'icon'  => 'bi-kanban',
                'items' => $this->searchProjects($keyword),
            ];
        }

        if ($user->hasPermission('inventory', 'view')) {
            $results['inventories'] = [
                'label' => 'Inventory',
                'icon'  => 'bi-box-seam',
                'items' => $this->searchInventories($keyword),
            ];
        }

        if ($user->hasPermission('kontak', 'view')) {
            $results['contacts'] = [
                'label' => 'Kontak',
                'icon'  => 'bi-person-lines-fill',
                'items' => $this->searchContacts($keyword),
            ];
        }

        if ($user->hasPermission('surat_jalan', 'view')) {
            $results['surat_jalans'] = [
                'label' => 'Surat Jalan',
                'icon'  => 'bi-file-earmark-text',
                'items' => $this->searchSuratJalans($keyword),
            ];
        }

        // Buang kategori yang izinnya ada tapi hasilnya nihil, supaya
        // halaman hasil tidak dipenuhi kartu kosong.
        return array_filter($results, fn (array $group) => $group['items']->isNotEmpty());
    }

    protected function searchProjects(string $keyword, int $limit = self::LIMIT_PER_CATEGORY): Collection
    {
        return Project::query()
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('client', 'like', "%{$keyword}%")
                    ->orWhere('company', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'client', 'company', 'status']);
    }

    protected function searchInventories(string $keyword, int $limit = self::LIMIT_PER_CATEGORY): Collection
    {
        return Inventory::query()
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('serial_number', 'like', "%{$keyword}%")
                    ->orWhere('brand', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'serial_number', 'brand', 'status']);
    }

    protected function searchContacts(string $keyword, int $limit = self::LIMIT_PER_CATEGORY): Collection
    {
        return Contact::query()
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('company', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'company', 'phone', 'email']);
    }

    protected function searchSuratJalans(string $keyword, int $limit = self::LIMIT_PER_CATEGORY): Collection
    {
        return SuratJalan::query()
            ->with('project:id,name')
            ->where(function ($q) use ($keyword) {
                $q->where('nomor', 'like', "%{$keyword}%")
                    ->orWhere('kepada', 'like', "%{$keyword}%");
            })
            ->orderByDesc('tanggal_terbit')
            ->limit($limit)
            ->get(['id', 'nomor', 'kepada', 'project_id', 'tanggal_terbit']);
    }

    /**
     * Versi "flat" untuk dropdown saran (typeahead) di navbar - dipakai
     * SearchController::suggest(). Beda dari search() di atas: hasilnya
     * bukan dikelompokkan per kategori untuk halaman hasil, tapi satu
     * list datar yang siap dirender jadi daftar saran klik-langsung
     * (mirip pola autocomplete Client di form Project - lihat
     * resources/views/project/partials/form.blade.php), masing-masing
     * sudah punya URL tujuan langsung ke halaman detailnya.
     *
     * @return array<int, array{label:string, subtitle:string, icon:string, category:string, url:string}>
     */
    public function suggestions(string $keyword, User $user, int $limit = 8, int $perCategory = 3): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return [];
        }

        // Halaman/menu (Dashboard, Daftar Barang, Kontak, dst) dicek
        // duluan - ini yang menjawab kasus ketik "inventory" atau
        // "kontak" (nama MENU-nya sendiri, bukan nama data di
        // dalamnya) supaya tetap dapat saran, bukan langsung invalid.
        $suggestions = $this->matchStaticPages($keyword, $user);

        if ($user->hasPermission('tracking_progress', 'view')) {
            foreach ($this->searchProjects($keyword, $perCategory) as $item) {
                $suggestions[] = [
                    'label'    => $item->name,
                    'subtitle' => $item->client ?: ($item->company ?: '-'),
                    'icon'     => 'bi-kanban',
                    'category' => 'Project',
                    'url'      => route('projects.show', $item->id),
                ];
            }
        }

        if ($user->hasPermission('inventory', 'view')) {
            foreach ($this->searchInventories($keyword, $perCategory) as $item) {
                $suggestions[] = [
                    'label'    => $item->name,
                    'subtitle' => $item->serial_number ?: ($item->brand ?: '-'),
                    'icon'     => 'bi-box-seam',
                    'category' => 'Inventory',
                    'url'      => route('inventory.show', $item->id),
                ];
            }
        }

        if ($user->hasPermission('kontak', 'view')) {
            foreach ($this->searchContacts($keyword, $perCategory) as $item) {
                $suggestions[] = [
                    'label'    => $item->name,
                    'subtitle' => $item->company ?: ($item->phone ?: ($item->email ?: '-')),
                    'icon'     => 'bi-person-lines-fill',
                    'category' => 'Kontak',
                    'url'      => route('contacts.show', $item->id),
                ];
            }
        }

        if ($user->hasPermission('surat_jalan', 'view')) {
            foreach ($this->searchSuratJalans($keyword, $perCategory) as $item) {
                $suggestions[] = [
                    'label'    => $item->nomor,
                    'subtitle' => $item->project?->short_name ?? $item->kepada,
                    'icon'     => 'bi-file-earmark-text',
                    'category' => 'Surat Jalan',
                    'url'      => route('surat-jalan.show', $item->id),
                ];
            }
        }

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Daftar statis semua menu/halaman di sidebar (lihat
     * resources/views/layouts/sidebar.blade.php - daftar & gate-nya
     * SENGAJA dicontek 1:1 dari sana, bukan ditebak, supaya saran yang
     * muncul konsisten dengan menu yang benar-benar bisa dibuka user
     * yang sedang login). `keywords` berisi kata-kata yang wajar diketik
     * orang untuk menuju halaman itu (nama menu, sinonim, istilah
     * modulnya) - keyword pengetikan dicocokkan ke label ATAU salah satu
     * `keywords` ini, bukan cuma ke label persis.
     *
     * @return array<int, array{label:string, icon:string, keywords:string[], url:string}>
     */
    protected function staticPages(User $user): array
    {
        $pages = [
            [
                'label' => 'Dashboard',
                'icon' => 'bi-speedometer2',
                'keywords' => ['dashboard', 'utama', 'home', 'beranda'],
                'url' => route('dashboard'),
                'visible' => true,
            ],
            [
                'label' => 'Daftar Barang',
                'icon' => 'bi-box-seam',
                'keywords' => ['inventory', 'inventaris', 'daftar barang', 'barang', 'aset', 'stok'],
                'url' => route('inventory.index'),
                'visible' => $user->hasPermission('inventory', 'view'),
            ],
            [
                'label' => 'Tambah Barang',
                'icon' => 'bi-plus-circle',
                'keywords' => ['tambah barang', 'tambah inventory', 'add inventory', 'barang baru', 'inventory'],
                'url' => route('inventory.create'),
                'visible' => $user->hasPermission('inventory', 'create'),
            ],
            [
                'label' => 'Mutasi Aset',
                'icon' => 'bi-arrow-left-right',
                'keywords' => ['mutasi', 'mutasi aset', 'riwayat barang', 'inventory'],
                'url' => route('inventory.mutasi'),
                'visible' => $user->hasPermission('inventory', 'view'),
            ],
            [
                'label' => 'Daftar Project',
                'icon' => 'bi-kanban',
                'keywords' => ['project', 'projects', 'daftar project'],
                'url' => route('projects.index'),
                'visible' => $user->hasPermission('tracking_progress', 'view'),
            ],
            [
                'label' => 'Pipeline',
                'icon' => 'bi-diagram-3',
                'keywords' => ['pipeline', 'papan kanban', 'kanban', 'project'],
                'url' => route('projects.pipeline'),
                'visible' => $user->hasRole('super_admin', 'admin'),
            ],
            [
                'label' => 'Barang Pinjaman',
                'icon' => 'bi-box-arrow-in-left',
                'keywords' => ['barang pinjaman', 'pinjaman', 'borrowed items', 'peminjaman'],
                'url' => route('borrowed-items.index'),
                'visible' => $user->hasPermission('borrowed_items', 'view'),
            ],
            [
                'label' => 'Tambah Project',
                'icon' => 'bi-folder-plus',
                'keywords' => ['tambah project', 'add project', 'project baru'],
                'url' => route('projects.create'),
                'visible' => $user->hasPermission('tracking_progress', 'create_project'),
            ],
            [
                'label' => 'Kelola Folder',
                'icon' => 'bi-folder2-open',
                'keywords' => ['folder', 'kelola folder', 'folder management', 'integrasi data'],
                'url' => route('folders.index'),
                'visible' => $user->hasPermission('data_integration', 'view'),
            ],
            [
                'label' => 'File Saya',
                'icon' => 'bi-file-earmark-arrow-up',
                'keywords' => ['my files', 'file saya', 'files', 'berkas'],
                'url' => route('files.my-files'),
                'visible' => $user->hasPermission('data_integration', 'view'),
            ],
            [
                'label' => 'Kontak',
                'icon' => 'bi-person-vcard',
                'keywords' => ['kontak', 'contact', 'buku alamat', 'client'],
                'url' => route('contacts.index'),
                'visible' => $user->hasPermission('kontak', 'view'),
            ],
            [
                'label' => 'Data User',
                'icon' => 'bi-person-lines-fill',
                'keywords' => ['user', 'data user', 'pengguna', 'kelola user', 'user management'],
                'url' => route('users.index'),
                'visible' => $user->isSuperAdmin(),
            ],
            [
                'label' => 'Tambah User',
                'icon' => 'bi-person-plus',
                'keywords' => ['tambah user', 'add user', 'user baru'],
                'url' => route('users.create'),
                'visible' => $user->isSuperAdmin(),
            ],
            [
                'label' => 'Log Aktivitas',
                'icon' => 'bi-journal-text',
                'keywords' => ['activity log', 'activity logs', 'log aktivitas', 'riwayat aktivitas'],
                'url' => route('activity-logs.index'),
                'visible' => $user->hasRole('super_admin', 'admin'),
            ],
            [
                'label' => 'Sampah',
                'icon' => 'bi-trash',
                'keywords' => ['trash', 'sampah', 'recycle bin', 'data terhapus'],
                'url' => route('trash.index'),
                'visible' => $user->hasPermission('trash', 'view'),
            ],
            [
                'label' => 'Notifikasi',
                'icon' => 'bi-megaphone',
                'keywords' => ['notifikasi', 'pengumuman', 'announcement'],
                'url' => route('announcements.index'),
                'visible' => true,
            ],
        ];

        return array_values(array_filter($pages, fn (array $page) => $page['visible']));
    }

    /**
     * @return array<int, array{label:string, subtitle:string, icon:string, category:string, url:string}>
     */
    protected function matchStaticPages(string $keyword, User $user): array
    {
        $needle = mb_strtolower($keyword);
        $matches = [];

        foreach ($this->staticPages($user) as $page) {
            $haystack = mb_strtolower($page['label'] . ' ' . implode(' ', $page['keywords']));

            if (mb_strpos($haystack, $needle) === false) {
                continue;
            }

            $matches[] = [
                'label'    => $page['label'],
                'subtitle' => 'Buka halaman ini',
                'icon'     => $page['icon'],
                'category' => 'Halaman',
                'url'      => $page['url'],
            ];
        }

        return $matches;
    }
}
