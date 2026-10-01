<?php

namespace App\Services\DataIntegration;

use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Kunci (gembok) untuk file & folder di ruang bersama.
 *
 * Aturan:
 * - Item terkunci tidak boleh di-rename, dipindah, atau dihapus oleh siapa pun (termasuk Super Admin)
 *   sampai kuncinya dibuka.
 * - Kunci pada folder melindungi seluruh isinya (sub-folder dan file, bertingkat). Upload file
 *   baru ke folder terkunci tetap boleh; file baru itu otomatis ikut terlindungi.
 * - Folder yang di dalamnya (bertingkat) ada item terkunci tidak boleh dihapus.
 *
 * Penanda kunci adalah kolom locked_at; locked_by hanya untuk ditampilkan.
 */
class LockService
{
    /** Batas penelusuran ke atas/bawah pohon folder (pengaman data induk yang berputar). */
    private const MAX_DEPTH = 20;

    public function lock(Model $item): void
    {
        $item->update([
            'locked_at' => now(),
            'locked_by' => Auth::id(),
        ]);
    }

    public function unlock(Model $item): void
    {
        $item->update([
            'locked_at' => null,
            'locked_by' => null,
        ]);
    }

    /**
     * Kunci yang berlaku untuk item: kunci miliknya sendiri, atau kunci folder induk terdekat.
     *
     * @return array{source:string,by:?string,at:mixed,folder_name:?string}|null
     *         source = 'self' | 'folder'; null bila tidak terkunci.
     */
    public function effectiveLock(Model $item): ?array
    {
        if ($item->locked_at !== null) {
            return $this->describe('self', $item, null);
        }

        $tree  = $this->tree();
        $id    = $item instanceof File ? $item->folder_id : $item->parent_id;
        $guard = 0;

        while ($id && isset($tree[$id]) && $guard++ < self::MAX_DEPTH) {
            $folder = $tree[$id];

            if ($folder->locked_at !== null) {
                return $this->describe('folder', $folder, $folder->name);
            }

            $id = $folder->parent_id;
        }

        return null;
    }

    /**
     * Lempar pesan jelas bila item (atau folder induknya) terkunci.
     *
     * @throws Exception
     */
    public function assertCanChange(Model $item): void
    {
        $lock = $this->effectiveLock($item);

        if ($lock === null) {
            return;
        }

        $by = $lock['by'] ?? 'pengguna yang sudah dihapus';

        if ($lock['source'] === 'self') {
            $when = $this->formatDate($lock['at']);

            throw new Exception(
                "Item ini dikunci oleh {$by}" . ($when ? " pada {$when}" : '')
                . '. Buka kunci terlebih dahulu untuk mengubah, memindahkan, atau menghapusnya.'
            );
        }

        throw new Exception(
            "Item ini berada di folder terkunci \"{$lock['folder_name']}\" (dikunci oleh {$by}). "
            . 'Buka kunci folder tersebut terlebih dahulu.'
        );
    }

    /**
     * Folder tidak boleh dihapus bila di dalamnya (bertingkat) masih ada item terkunci.
     *
     * @throws Exception
     */
    public function assertNoLockedContents(Folder $folder): void
    {
        $tree = $this->tree();
        $ids  = [$folder->id];

        foreach ($this->descendantIds($tree, $folder->id) as $descendantId) {
            $ids[] = $descendantId;

            if ($tree[$descendantId]->locked_at !== null) {
                throw new Exception(
                    "Folder ini berisi item terkunci (\"{$tree[$descendantId]->name}\"). "
                    . 'Buka kuncinya terlebih dahulu sebelum menghapus folder.'
                );
            }
        }

        $lockedFile = $this->firstLockedFileName($ids);

        if ($lockedFile !== null) {
            throw new Exception(
                "Folder ini berisi item terkunci (\"{$lockedFile}\"). "
                . 'Buka kuncinya terlebih dahulu sebelum menghapus folder.'
            );
        }
    }

    /**
     * Id folder yang di dalamnya (bertingkat) ada item terkunci - dipakai daftar untuk
     * menyembunyikan menu Delete tanpa query per baris.
     *
     * @return int[]
     */
    public function folderIdsWithLockedContents(): array
    {
        $tree  = $this->tree();
        $marks = [];

        // Folder terkunci menandai semua induknya (bukan dirinya sendiri).
        foreach ($tree as $folder) {
            if ($folder->locked_at !== null) {
                $this->markUpwards($tree, $folder->parent_id, $marks);
            }
        }

        // File terkunci menandai foldernya sendiri dan semua induknya.
        foreach ($this->lockedFileFolderIds() as $folderId) {
            $this->markUpwards($tree, $folderId, $marks);
        }

        return array_map('intval', array_keys($marks));
    }

    // ------------------------------------------------------------------
    //  Internal
    // ------------------------------------------------------------------

    /**
     * @param array<int,bool> $marks
     */
    private function markUpwards(Collection $tree, $id, array &$marks): void
    {
        $guard = 0;

        while ($id && isset($tree[$id]) && $guard++ < self::MAX_DEPTH) {
            $marks[$id] = true;
            $id = $tree[$id]->parent_id;
        }
    }

    /**
     * @return int[]
     */
    private function descendantIds(Collection $tree, int $rootId): array
    {
        $byParent = $tree->groupBy('parent_id');
        $result   = [];
        $queue    = [$rootId];

        while ($queue) {
            $current = array_shift($queue);

            foreach ($byParent->get($current, collect()) as $child) {
                if (!in_array($child->id, $result, true)) {
                    $result[] = $child->id;
                    $queue[]  = $child->id;
                }
            }
        }

        return $result;
    }

    private function describe(string $source, Model $carrier, ?string $folderName): array
    {
        return [
            'source'      => $source,
            'by'          => $this->userName($carrier->locked_by),
            'at'          => $carrier->locked_at,
            'folder_name' => $folderName,
        ];
    }

    private function formatDate($date): ?string
    {
        if ($date === null) {
            return null;
        }

        return is_object($date) && method_exists($date, 'format')
            ? $date->format('d M Y H:i')
            : (string) $date;
    }

    // Pengambilan data dipisah ke method kecil agar logika di atas mudah diuji.

    /**
     * Seluruh folder aktif (kolom ringkas), berkunci id.
     *
     * @return Collection<int, Folder>
     */
    protected function tree(): Collection
    {
        return Folder::select('id', 'name', 'parent_id', 'locked_at', 'locked_by')
            ->get()
            ->keyBy('id');
    }

    protected function firstLockedFileName(array $folderIds): ?string
    {
        return File::whereIn('folder_id', $folderIds)
            ->whereNotNull('locked_at')
            ->value('file_name');
    }

    /**
     * @return int[]
     */
    protected function lockedFileFolderIds(): array
    {
        return File::whereNotNull('locked_at')
            ->whereNotNull('folder_id')
            ->distinct()
            ->pluck('folder_id')
            ->all();
    }

    protected function userName(?int $userId): ?string
    {
        return $userId ? User::whereKey($userId)->value('name') : null;
    }
}
