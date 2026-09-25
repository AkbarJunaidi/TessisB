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
    public function suggestions(string $keyword, User $user, int $limit = 8, int $perCategory = 4): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return [];
        }

        $suggestions = [];

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
                    'subtitle' => $item->project?->name ?? $item->kepada,
                    'icon'     => 'bi-file-earmark-text',
                    'category' => 'Surat Jalan',
                    'url'      => route('surat-jalan.show', $item->id),
                ];
            }
        }

        return array_slice($suggestions, 0, $limit);
    }
}
