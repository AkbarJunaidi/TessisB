<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill data: setiap inventory yang sudah ada sebelum fitur Unit Fisik ini
     * dibuatkan unit sejumlah quantity_total (status default "Tersedia"), supaya
     * fitur Kelola Unit Fisik langsung terisi untuk data lama, bukan cuma data baru.
     */
    public function up(): void
    {
        $inventories = DB::table('inventories')
            ->whereNull('deleted_at')
            ->get(['id', 'quantity_total']);

        $now = now();

        foreach ($inventories as $inventory) {
            $existingCount = DB::table('inventory_units')
                ->where('inventory_id', $inventory->id)
                ->count();

            $targetQuantity = $inventory->quantity_total ?? 1;

            if ($existingCount < $targetQuantity) {
                $rows = [];
                for ($i = $existingCount + 1; $i <= $targetQuantity; $i++) {
                    $rows[] = [
                        'inventory_id' => $inventory->id,
                        'unit_number'  => $i,
                        'status'       => 'Tersedia',
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ];
                }
                if (!empty($rows)) {
                    DB::table('inventory_units')->insert($rows);
                }
            }
        }
    }

    public function down(): void
    {
        // Data backfill tidak di-rollback otomatis (menghindari kehilangan status
        // unit yang mungkin sudah diubah admin setelah backfill berjalan).
    }
};
