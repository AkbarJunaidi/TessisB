<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill: Surat Jalan Item lama (dibuat sebelum kolom surat_jalan_item_id
     * ada di inventory_units) belum terhubung ke unit fisik manapun, padahal
     * qty_dipakai-nya masih aktif (belum sepenuhnya dikembalikan). Migration ini
     * meng-assign unit "Tersedia" & belum terpakai secara otomatis, sejumlah
     * sisa yang masih dipakai (qty_dipakai - qty_dikembalikan), per item,
     * diurutkan dari yang paling lama dibuat.
     */
    public function up(): void
    {
        $items = DB::table('surat_jalan_items')
            ->whereColumn('qty_dipakai', '>', 'qty_dikembalikan')
            ->orderBy('created_at')
            ->get(['id', 'inventory_id', 'qty_dipakai', 'qty_dikembalikan']);

        foreach ($items as $item) {
            $sisa = $item->qty_dipakai - $item->qty_dikembalikan;

            $availableUnitIds = DB::table('inventory_units')
                ->where('inventory_id', $item->inventory_id)
                ->where('status', 'Tersedia')
                ->whereNull('surat_jalan_item_id')
                ->orderBy('unit_number')
                ->limit($sisa)
                ->pluck('id');

            if ($availableUnitIds->isNotEmpty()) {
                DB::table('inventory_units')
                    ->whereIn('id', $availableUnitIds)
                    ->update(['surat_jalan_item_id' => $item->id]);
            }
            // Catatan: kalau unit yang tersedia tidak cukup untuk sejumlah $sisa
            // (misalnya data historis tidak konsisten), sisanya tetap tidak
            // ter-assign - tidak menimbulkan error, hanya berarti tampilan detail
            // unit untuk item itu tidak 100% presisi, tapi qty_available/qty_in_use
            // (accessor lama berbasis SUM qty) tetap akurat seperti sebelumnya.
        }
    }

    public function down(): void
    {
        // Tidak di-rollback otomatis (menghindari melepas assignment yang sudah
        // benar dan mungkin sudah berubah lagi setelah backfill berjalan).
    }
};
