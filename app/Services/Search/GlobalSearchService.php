<?php

namespace App\Services\Search;

use App\Models\Contact;
use App\Models\Inventory;
use App\Models\InventoryRepair;
use App\Models\Kwitansi;
use App\Models\Location;
use App\Models\Project;
use App\Models\ProjectFinanceItem;
use App\Models\Purchase;
use App\Models\SuratJalan;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Pencarian global navbar: satu keyword dicek ke halaman/menu dan beberapa modul data.
 * Tiap kategori di-skip diam-diam bila user tidak punya izin yang sama dengan sidebar/route-nya.
 */
class GlobalSearchService
{
    /** Batas baris per kategori di halaman hasil (jalan pintas, bukan data berpaginasi). */
    protected const LIMIT_PER_CATEGORY = 10;

    /** Halaman/menu maksimal di saran, supaya data tetap kebagian tempat. */
    protected const MAX_PAGE_SUGGESTIONS = 4;

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

        if ($this->canViewInventory($user)) {
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

        // Kategori yang hasilnya nihil dibuang agar halaman tidak penuh kartu kosong.
        return array_filter($results, fn (array $group) => $group['items']->isNotEmpty());
    }

    /** Izin inventory sama dengan route-nya: grup role super_admin/admin lalu izin view. */
    protected function canViewInventory(User $user): bool
    {
        return $user->hasRole('super_admin', 'admin') && $user->hasPermission('inventory', 'view');
    }

    /** Pola LIKE aman: karakter % dan _ dari keyword dianggap huruf biasa. */
    protected function like(string $keyword): string
    {
        return '%' . addcslashes($keyword, '%_\\') . '%';
    }

    protected function searchProjects(string $keyword, int $limit = self::LIMIT_PER_CATEGORY): Collection
    {
        $like = $this->like($keyword);

        return Project::query()
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('client', 'like', $like)
                    ->orWhere('company', 'like', $like);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'client', 'company', 'status']);
    }

    protected function searchInventories(string $keyword, int $limit = self::LIMIT_PER_CATEGORY): Collection
    {
        $like = $this->like($keyword);

        return Inventory::query()
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('serial_number', 'like', $like)
                    ->orWhere('brand', 'like', $like);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'serial_number', 'brand', 'status']);
    }

    protected function searchContacts(string $keyword, int $limit = self::LIMIT_PER_CATEGORY): Collection
    {
        $like = $this->like($keyword);

        return Contact::query()
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('company', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'company', 'phone', 'email']);
    }

    protected function searchSuratJalans(string $keyword, int $limit = self::LIMIT_PER_CATEGORY): Collection
    {
        $like = $this->like($keyword);

        return SuratJalan::query()
            ->with('project:id,name')
            ->where(function ($q) use ($like) {
                $q->where('nomor', 'like', $like)
                    ->orWhere('kepada', 'like', $like);
            })
            ->orderByDesc('tanggal_terbit')
            ->limit($limit)
            ->get(['id', 'nomor', 'kepada', 'project_id', 'tanggal_terbit']);
    }

    protected function searchPurchases(string $keyword, int $limit): Collection
    {
        $like = $this->like($keyword);

        return Purchase::query()
            ->where(fn ($q) => $q->where('code', 'like', $like)->orWhere('vendor_name', 'like', $like))
            ->orderByDesc('purchase_date')
            ->limit($limit)
            ->get(['id', 'code', 'vendor_name']);
    }

    protected function searchKwitansis(string $keyword, int $limit): Collection
    {
        $like = $this->like($keyword);

        return Kwitansi::query()
            ->with('project:id,name')
            ->where(fn ($q) => $q->where('nomor', 'like', $like)->orWhere('keterangan', 'like', $like))
            ->orderByDesc('tanggal')
            ->limit($limit)
            ->get(['id', 'nomor', 'project_id', 'keterangan']);
    }

    protected function searchRepairs(string $keyword, int $limit): Collection
    {
        $like = $this->like($keyword);

        return InventoryRepair::query()
            ->where(function ($q) use ($like) {
                $q->where('code', 'like', $like)
                    ->orWhere('tempat_nama', 'like', $like)
                    ->orWhere('keluhan', 'like', $like);
            })
            ->orderByDesc('tanggal_masuk')
            ->limit($limit)
            ->get(['id', 'code', 'tempat_nama']);
    }

    protected function searchLocations(string $keyword, int $limit): Collection
    {
        $like = $this->like($keyword);

        return Location::query()
            ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('address', 'like', $like))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'jenis', 'address']);
    }

    protected function searchTasks(string $keyword, int $limit): Collection
    {
        return Task::query()
            ->with('project:id,name')
            ->where('title', 'like', $this->like($keyword))
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'title', 'project_id']);
    }

    protected function searchUsers(string $keyword, int $limit): Collection
    {
        $like = $this->like($keyword);

        return User::query()
            ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email']);
    }

    protected function searchTransactions(string $keyword, int $limit): Collection
    {
        $like = $this->like($keyword);

        return ProjectFinanceItem::query()
            ->where(fn ($q) => $q->where('description', 'like', $like)->orWhere('recipient', 'like', $like))
            ->orderByDesc('tanggal')
            ->limit($limit)
            ->get(['id', 'description', 'recipient', 'category', 'tanggal']);
    }

    /**
     * Daftar saran datar untuk dropdown navbar, tiap item langsung punya URL tujuan.
     * Halaman tampil dulu, lalu data digilir antar kategori agar setiap kategori kebagian.
     *
     * @return array<int, array{label:string, subtitle:string, icon:string, category:string, url:string}>
     */
    public function suggestions(string $keyword, User $user, int $limit = 12, int $perCategory = 3): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return [];
        }

        $pages  = array_slice($this->matchStaticPages($keyword, $user), 0, self::MAX_PAGE_SUGGESTIONS);
        $groups = [];

        if ($user->hasPermission('tracking_progress', 'view')) {
            $groups[] = $this->searchProjects($keyword, $perCategory)->map(fn ($item) => [
                'label'    => $item->name,
                'subtitle' => $item->client ?: ($item->company ?: '-'),
                'icon'     => 'bi-kanban',
                'category' => 'Project',
                'url'      => route('projects.show', $item->id),
            ])->all();

            $groups[] = $this->searchTasks($keyword, $perCategory)->map(fn ($item) => [
                'label'    => $item->title,
                'subtitle' => $item->project?->name ?? '-',
                'icon'     => 'bi-list-task',
                'category' => 'Task',
                'url'      => route('tasks.show', $item->id),
            ])->all();
        }

        if ($this->canViewInventory($user)) {
            $groups[] = $this->searchInventories($keyword, $perCategory)->map(fn ($item) => [
                'label'    => $item->name,
                'subtitle' => $item->serial_number ?: ($item->brand ?: '-'),
                'icon'     => 'bi-box-seam',
                'category' => 'Inventory',
                'url'      => route('inventory.show', $item->id),
            ])->all();

            $groups[] = $this->searchRepairs($keyword, $perCategory)->map(fn ($item) => [
                'label'    => $item->code,
                'subtitle' => $item->tempat_nama ?: '-',
                'icon'     => 'bi-tools',
                'category' => 'Perbaikan',
                'url'      => route('inventory.repairs.show', $item->id),
            ])->all();

            $groups[] = $this->searchLocations($keyword, $perCategory)->map(fn ($item) => [
                'label'    => $item->name,
                'subtitle' => $item->address ?: ($item->jenis ?: '-'),
                'icon'     => 'bi-geo-alt',
                'category' => 'Lokasi',
                'url'      => route('inventory.locations.index', ['search' => $item->name]),
            ])->all();
        }

        if ($user->hasPermission('kontak', 'view')) {
            $groups[] = $this->searchContacts($keyword, $perCategory)->map(fn ($item) => [
                'label'    => $item->name,
                'subtitle' => $item->company ?: ($item->phone ?: ($item->email ?: '-')),
                'icon'     => 'bi-person-lines-fill',
                'category' => 'Kontak',
                'url'      => route('contacts.show', $item->id),
            ])->all();
        }

        if ($user->hasPermission('surat_jalan', 'view')) {
            $groups[] = $this->searchSuratJalans($keyword, $perCategory)->map(fn ($item) => [
                'label'    => $item->nomor,
                'subtitle' => $item->project?->short_name ?? $item->kepada,
                'icon'     => 'bi-file-earmark-text',
                'category' => 'Surat Jalan',
                'url'      => route('surat-jalan.show', $item->id),
            ])->all();
        }

        if ($user->hasPermission('purchase', 'view')) {
            $groups[] = $this->searchPurchases($keyword, $perCategory)->map(fn ($item) => [
                'label'    => $item->code,
                'subtitle' => $item->vendor_name ?: '-',
                'icon'     => 'bi-bag-check',
                'category' => 'Pembelian',
                'url'      => route('purchases.show', $item->id),
            ])->all();
        }

        if ($user->hasPermission('finance', 'view')) {
            $groups[] = $this->searchKwitansis($keyword, $perCategory)->map(fn ($item) => [
                'label'    => $item->nomor,
                'subtitle' => $item->project?->name ?? ($item->keterangan ?: '-'),
                'icon'     => 'bi-receipt',
                'category' => 'Kwitansi',
                'url'      => route('kwitansi.show-project', $item->project_id),
            ])->all();

            // Daftar transaksi default-nya per bulan, jadi tautan dikunci ke tanggal transaksinya.
            $groups[] = $this->searchTransactions($keyword, $perCategory)->map(fn ($item) => [
                'label'    => $item->description ?: ($item->category ?: 'Transaksi'),
                'subtitle' => $item->recipient ?: ($item->category ?: '-'),
                'icon'     => 'bi-cash-coin',
                'category' => 'Transaksi',
                'url'      => route('finance.transactions.index', array_filter([
                    'search' => $keyword,
                    'from'   => $item->tanggal ? Carbon::parse($item->tanggal)->toDateString() : null,
                    'to'     => $item->tanggal ? Carbon::parse($item->tanggal)->toDateString() : null,
                ])),
            ])->all();
        }

        if ($user->isSuperAdmin()) {
            $groups[] = $this->searchUsers($keyword, $perCategory)->map(fn ($item) => [
                'label'    => $item->name,
                'subtitle' => $item->email ?: '-',
                'icon'     => 'bi-people',
                'category' => 'User',
                'url'      => route('users.show', $item->id),
            ])->all();
        }

        return array_slice(array_merge($pages, $this->interleave($groups, $perCategory)), 0, $limit);
    }

    /**
     * Gilir satu item per kategori per putaran, supaya kategori di urutan bawah tidak terpotong.
     *
     * @param  array<int, array<int, array<string, string>>>  $groups
     * @return array<int, array<string, string>>
     */
    protected function interleave(array $groups, int $rounds): array
    {
        $merged = [];

        for ($i = 0; $i < $rounds; $i++) {
            foreach ($groups as $group) {
                if (isset($group[$i])) {
                    $merged[] = $group[$i];
                }
            }
        }

        return $merged;
    }

    /**
     * Daftar menu/halaman sidebar dengan gate yang sama persis (lihat layouts/sidebar.blade.php).
     * `keywords` adalah kata yang wajar diketik orang; cocok ke label ATAU salah satu keywords.
     *
     * @return array<int, array{label:string, icon:string, keywords:string[], url:string}>
     */
    protected function staticPages(User $user): array
    {
        $inv        = $this->canViewInventory($user);
        $invAdmin   = $user->hasRole('super_admin', 'admin');
        $finance    = $user->hasPermission('finance', 'view');

        $pages = [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'keywords' => ['dashboard', 'utama', 'home', 'beranda'],
                'url' => route('dashboard'), 'visible' => true],
            ['label' => 'Notifikasi', 'icon' => 'bi-megaphone', 'keywords' => ['notifikasi', 'pengumuman', 'announcement'],
                'url' => route('announcements.index'), 'visible' => true],
            ['label' => 'Approval', 'icon' => 'bi-check2-square', 'keywords' => ['approval', 'persetujuan', 'pengajuan', 'setujui'],
                'url' => route('approval.index'), 'visible' => $user->hasPermission('approval', 'view')],
            ['label' => 'Scan Barang', 'icon' => 'bi-upc-scan', 'keywords' => ['scan', 'scan barang', 'qr', 'barcode', 'pindai'],
                'url' => route('scan.index'), 'visible' => $user->hasPermission('scan_barang', 'view')],
            ['label' => 'Daftar Barang', 'icon' => 'bi-box-seam', 'keywords' => ['inventory', 'inventaris', 'daftar barang', 'barang', 'aset', 'stok'],
                'url' => route('inventory.index'), 'visible' => $inv],
            ['label' => 'Tambah Barang', 'icon' => 'bi-plus-circle', 'keywords' => ['tambah barang', 'tambah inventory', 'add inventory', 'barang baru', 'inventory'],
                'url' => route('inventory.create'), 'visible' => $invAdmin && $user->hasPermission('inventory', 'create')],
            ['label' => 'Mutasi Aset', 'icon' => 'bi-arrow-left-right', 'keywords' => ['mutasi', 'mutasi aset', 'riwayat barang', 'inventory'],
                'url' => route('inventory.mutasi'), 'visible' => $inv],
            ['label' => 'Perbaikan Barang', 'icon' => 'bi-tools', 'keywords' => ['perbaikan', 'servis', 'service', 'repair', 'rusak', 'reparasi'],
                'url' => route('inventory.repairs.index'), 'visible' => $inv],
            ['label' => 'Daftar Harga Barang', 'icon' => 'bi-currency-dollar', 'keywords' => ['harga', 'daftar harga', 'harga barang', 'biaya servis', 'price'],
                'url' => route('inventory.prices.index'), 'visible' => $invAdmin && $user->hasPermission('purchase', 'view')],
            ['label' => 'Daftar Project', 'icon' => 'bi-kanban', 'keywords' => ['project', 'projects', 'daftar project'],
                'url' => route('projects.index'), 'visible' => $user->hasPermission('tracking_progress', 'view')],
            ['label' => 'Pipeline', 'icon' => 'bi-diagram-3', 'keywords' => ['pipeline', 'papan kanban', 'kanban', 'project'],
                'url' => route('projects.pipeline'), 'visible' => $invAdmin],
            ['label' => 'Barang Pinjaman', 'icon' => 'bi-box-arrow-in-left', 'keywords' => ['barang pinjaman', 'pinjaman', 'borrowed items', 'peminjaman'],
                'url' => route('borrowed-items.index'), 'visible' => $user->hasPermission('borrowed_items', 'view')],
            ['label' => 'Tambah Project', 'icon' => 'bi-folder-plus', 'keywords' => ['tambah project', 'add project', 'project baru'],
                'url' => route('projects.create'), 'visible' => $user->hasPermission('tracking_progress', 'create_project')],
            ['label' => 'Pembelian', 'icon' => 'bi-bag-check', 'keywords' => ['pembelian', 'purchase', 'beli', 'vendor', 'supplier'],
                'url' => route('purchases.index'), 'visible' => $user->hasPermission('purchase', 'view')],
            ['label' => 'Kontak', 'icon' => 'bi-person-vcard', 'keywords' => ['kontak', 'contact', 'buku alamat', 'client'],
                'url' => route('contacts.index'), 'visible' => $user->hasPermission('kontak', 'view')],
            ['label' => 'Ringkasan Keuangan', 'icon' => 'bi-cash-coin', 'keywords' => ['keuangan', 'finance', 'ringkasan', 'laporan keuangan', 'laba'],
                'url' => route('finance.summary'), 'visible' => $finance],
            ['label' => 'Transaksi Keuangan', 'icon' => 'bi-cash-coin', 'keywords' => ['keuangan', 'transaksi', 'kas', 'pemasukan', 'pengeluaran'],
                'url' => route('finance.transactions.index'), 'visible' => $finance],
            ['label' => 'Keuangan per Project', 'icon' => 'bi-cash-coin', 'keywords' => ['keuangan', 'kwitansi', 'per project', 'tagihan', 'pembayaran'],
                'url' => route('kwitansi.index'), 'visible' => $finance],
            ['label' => 'Kelola Folder', 'icon' => 'bi-folder2-open', 'keywords' => ['folder', 'kelola folder', 'folder management', 'integrasi data'],
                'url' => route('folders.index'), 'visible' => $user->hasPermission('data_integration', 'view')],
            ['label' => 'File Saya', 'icon' => 'bi-file-earmark-arrow-up', 'keywords' => ['my files', 'file saya', 'files', 'berkas'],
                'url' => route('files.my-files'), 'visible' => $user->hasPermission('data_integration', 'view')],
            ['label' => 'Data User', 'icon' => 'bi-person-lines-fill', 'keywords' => ['user', 'data user', 'pengguna', 'kelola user', 'user management'],
                'url' => route('users.index'), 'visible' => $user->isSuperAdmin()],
            ['label' => 'Tambah User', 'icon' => 'bi-person-plus', 'keywords' => ['tambah user', 'add user', 'user baru'],
                'url' => route('users.create'), 'visible' => $user->isSuperAdmin()],
            ['label' => 'Log Aktivitas', 'icon' => 'bi-journal-text', 'keywords' => ['activity log', 'activity logs', 'log aktivitas', 'riwayat aktivitas'],
                'url' => route('activity-logs.index'), 'visible' => $invAdmin],
            ['label' => 'Sampah', 'icon' => 'bi-trash', 'keywords' => ['trash', 'sampah', 'recycle bin', 'data terhapus'],
                'url' => route('trash.index'), 'visible' => $user->hasPermission('trash', 'view')],
            ['label' => 'Pengaturan Umum', 'icon' => 'bi-building', 'keywords' => ['pengaturan', 'setting', 'settings', 'umum', 'profil perusahaan', 'perusahaan'],
                'url' => route('settings.index'), 'visible' => $user->isSuperAdmin()],
            ['label' => 'Lokasi', 'icon' => 'bi-geo-alt', 'keywords' => ['lokasi', 'location', 'gudang', 'kantor', 'pengaturan'],
                'url' => route('inventory.locations.index'), 'visible' => $inv],
            ['label' => 'Tanda Tangan Saya', 'icon' => 'bi-pen', 'keywords' => ['tanda tangan', 'ttd', 'signature', 'paraf'],
                'url' => route('signature.index'), 'visible' => true],
        ];

        return array_values(array_filter($pages, fn (array $page) => $page['visible']));
    }

    /**
     * Halaman yang labelnya memuat keyword didahulukan dari yang cuma cocok lewat keywords.
     *
     * @return array<int, array{label:string, subtitle:string, icon:string, category:string, url:string}>
     */
    protected function matchStaticPages(string $keyword, User $user): array
    {
        $needle  = mb_strtolower($keyword);
        $matches = [];

        foreach ($this->staticPages($user) as $page) {
            $rank = mb_strpos(mb_strtolower($page['label']), $needle) !== false
                ? 0
                : (mb_strpos(mb_strtolower(implode(' ', $page['keywords'])), $needle) !== false ? 1 : null);

            if ($rank === null) {
                continue;
            }

            $matches[] = [
                'rank'     => $rank,
                'label'    => $page['label'],
                'subtitle' => 'Buka halaman ini',
                'icon'     => $page['icon'],
                'category' => 'Halaman',
                'url'      => $page['url'],
            ];
        }

        usort($matches, fn (array $a, array $b) => $a['rank'] <=> $b['rank']);

        return array_map(function (array $match) {
            unset($match['rank']);

            return $match;
        }, $matches);
    }
}
