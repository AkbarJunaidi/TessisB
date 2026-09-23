<?php

namespace App\Services\Inventory;

use App\Models\InventoryMutation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * Pencatat & pembaca buku besar Mutasi Aset (lihat migration
 * create_inventory_mutations_table untuk daftar event_type dan alasan
 * kenapa 1 baris = 1 transaksi, bukan per unit fisik).
 *
 * Dipanggil dari 4 titik yang sudah ada (TIDAK mengubah alur bisnisnya):
 * SuratJalanService::createSuratJalan() & applyReturn() untuk
 * dipinjam/dikembalikan, InventoryService::syncUnits() untuk
 * ditambahkan/dihapus, dan recordStatusChange() dari updateUnitStatus().
 */
class InventoryMutationService
{
    /**
     * Buat 1 baris baru - dipakai untuk kejadian yang SUDAH otomatis 1
     * transaksi per aksi (tambah/hapus stok, pinjam, kembali), jadi tidak
     * perlu digabung dengan baris lain.
     */
    public function record(
        int $inventoryId,
        string $eventType,
        int $qty,
        ?int $suratJalanId = null,
        ?string $keterangan = null
    ): void {
        InventoryMutation::create([
            'inventory_id'   => $inventoryId,
            'event_type'     => $eventType,
            'qty'            => $qty,
            'surat_jalan_id' => $suratJalanId,
            'actor_id'       => Auth::id(),
            'keterangan'     => $keterangan,
        ]);
    }

    /**
     * Khusus perubahan status unit (dropdown di Edit Inventory ubah 1 unit
     * per klik) - kalau hari ini SUDAH ada baris barang+status_after yang
     * sama, jumlahnya ditambah ke baris itu saja (bukan bikin baris baru),
     * supaya 3x klik "Hilang" hari yang sama tetap tampil "3 unit, Hilang"
     * seperti yang diminta, bukan 3 baris terpisah.
     */
    public function recordStatusChange(int $inventoryId, string $statusBefore, string $statusAfter): void
    {
        $existing = InventoryMutation::where('inventory_id', $inventoryId)
            ->where('event_type', 'status_berubah')
            ->where('status_after', $statusAfter)
            ->where('created_at', '>=', now()->startOfDay())
            ->where('actor_id', Auth::id())
            ->first();

        if ($existing) {
            $existing->increment('qty');

            return;
        }

        InventoryMutation::create([
            'inventory_id'   => $inventoryId,
            'event_type'     => 'status_berubah',
            'qty'            => 1,
            'status_before'  => $statusBefore,
            'status_after'   => $statusAfter,
            'actor_id'       => Auth::id(),
        ]);
    }

    /**
     * Daftar mutasi lintas SEMUA barang untuk halaman Mutasi Aset, dengan
     * filter nama barang, rentang tanggal, dan jenis kejadian.
     */
    public function getFiltered(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = InventoryMutation::with(['inventory:id,name', 'suratJalan:id,nomor,project_id', 'actor:id,name'])
            ->latest('created_at');

        if (!empty($filters['search'])) {
            $keyword = trim($filters['search']);
            $query->whereHas('inventory', fn ($q) => $q->where('name', 'like', "%{$keyword}%"));
        }

        if (!empty($filters['event_types']) && is_array($filters['event_types'])) {
            $query->whereIn('event_type', $filters['event_types']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
