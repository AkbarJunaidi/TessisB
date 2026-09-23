<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\InventoryMutation;
use App\Models\InventoryUnit;
use App\Models\SuratJalanItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill SATU KALI JALAN untuk data sebelum fitur Mutasi Aset ada.
 * Lihat README fitur ini untuk penjelasan lengkap kenapa sebagian baris di
 * sini cuma estimasi/snapshot (bukan kejadian real-time - histori tanggal
 * pasti sebagiannya memang tidak pernah disimpan sistem lama).
 */
class BackfillInventoryMutations extends Command
{
    protected $signature = 'inventory:backfill-mutasi {--force : Tetap jalan walau tabel inventory_mutations sudah berisi data}';

    protected $description = 'Isi tabel inventory_mutations dari data lama (unit, Surat Jalan, status) sebelum fitur ini ada';

    public function handle(): int
    {
        if (InventoryMutation::exists() && !$this->option('force')) {
            $this->error('Tabel inventory_mutations sudah berisi data. Pakai --force kalau tetap mau menambahkan baris backfill lagi (bisa dobel).');

            return self::FAILURE;
        }

        DB::transaction(function () {
            $this->backfillDitambahkan();
            $this->backfillDipinjamDikembalikan();
            $this->backfillStatusSaatIni();
        });

        $this->info('Backfill Mutasi Aset selesai.');

        return self::SUCCESS;
    }

    /**
     * Helper backfill-only: `created_at`/`updated_at` sengaja TIDAK ada di
     * $fillable InventoryMutation (supaya create() normal di Service tidak
     * bisa disusupi tanggal palsu) - jadi di sini di-set manual +
     * $timestamps=false supaya Eloquent tidak menimpanya lagi jadi "now()"
     * saat save().
     */
    private function createWithDate(array $attributes, $date): void
    {
        $mutation = new InventoryMutation($attributes);
        $mutation->created_at = $date;
        $mutation->updated_at = $date;
        $mutation->timestamps = false;
        $mutation->save();
    }

    /**
     * 1 baris "ditambahkan" per barang = jumlah unit yang ADA SEKARANG,
     * tertanggal created_at barangnya - bukan riwayat naik-turun stok dari
     * waktu ke waktu (histori itu tidak pernah disimpan sistem lama).
     */
    private function backfillDitambahkan(): void
    {
        Inventory::withCount('units')->each(function (Inventory $inventory) {
            if ($inventory->units_count > 0) {
                $this->createWithDate([
                    'inventory_id' => $inventory->id,
                    'event_type'   => 'ditambahkan',
                    'qty'          => $inventory->units_count,
                ], $inventory->created_at);
            }
        });

        $this->info('Baseline "ditambahkan" selesai.');
    }

    /**
     * "Dipinjam" akurat (tanggal_terbit tersimpan per Surat Jalan).
     * "Dikembalikan" cuma estimasi 1 baris gabungan (total qty_dikembalikan
     * saat ini), tertanggal updated_at baris itu - kalau pengembaliannya
     * dulu bertahap di beberapa tanggal berbeda, tanggal aslinya per tahap
     * TIDAK tersimpan di sistem lama, jadi digabung jadi 1 baris saja.
     */
    private function backfillDipinjamDikembalikan(): void
    {
        SuratJalanItem::with('suratJalan.project')->chunk(200, function ($items) {
            foreach ($items as $item) {
                // Surat Jalan-nya sudah dihapus (soft delete) - lewati,
                // tidak ada referensi/tanggal yang valid untuk dipakai.
                if (!$item->suratJalan) {
                    continue;
                }

                $projectName = $item->suratJalan->project->name ?? null;

                if ($item->qty_dipakai > 0) {
                    $this->createWithDate([
                        'inventory_id'   => $item->inventory_id,
                        'event_type'     => 'dipinjam',
                        'qty'            => $item->qty_dipakai,
                        'surat_jalan_id' => $item->surat_jalan_id,
                        'keterangan'     => $projectName,
                    ], $item->suratJalan->tanggal_terbit ?? $item->created_at);
                }

                if ($item->qty_dikembalikan > 0) {
                    $this->createWithDate([
                        'inventory_id'   => $item->inventory_id,
                        'event_type'     => 'dikembalikan',
                        'qty'            => $item->qty_dikembalikan,
                        'surat_jalan_id' => $item->surat_jalan_id,
                        'keterangan'     => $projectName,
                    ], $item->updated_at);
                }
            }
        });

        $this->info('Baseline "dipinjam"/"dikembalikan" selesai.');
    }

    /**
     * 1 baris snapshot per (barang, status) untuk unit yang SAAT INI
     * berstatus Rusak/Perbaikan/Hilang - tertanggal HARI INI (backfill
     * dijalankan), bukan tanggal asli kejadiannya (tidak pernah disimpan).
     */
    private function backfillStatusSaatIni(): void
    {
        $grouped = InventoryUnit::where('status', '!=', 'Tersedia')
            ->selectRaw('inventory_id, status, COUNT(*) as jumlah')
            ->groupBy('inventory_id', 'status')
            ->get();

        foreach ($grouped as $row) {
            InventoryMutation::create([
                'inventory_id' => $row->inventory_id,
                'event_type'   => 'status_berubah',
                'qty'          => $row->jumlah,
                'status_after' => $row->status,
            ]);
        }

        $this->info('Baseline status Rusak/Perbaikan/Hilang selesai.');
    }
}
