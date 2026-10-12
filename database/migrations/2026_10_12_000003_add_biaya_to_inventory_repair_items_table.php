<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Biaya servis per unit; catatan lama dibagi rata dan ditandai perkiraan. */
    public function up(): void
    {
        if (Schema::hasColumn('inventory_repair_items', 'biaya')) {
            return;
        }

        Schema::table('inventory_repair_items', function (Blueprint $table) {
            $table->decimal('biaya', 15, 2)->nullable()->after('hasil');
            $table->boolean('biaya_estimasi')->default(false)->after('biaya');
        });

        DB::table('inventory_repairs')
            ->where('status', 'Selesai')
            ->where('biaya', '>', 0)
            ->orderBy('id')
            ->each(function ($repair) {
                $itemIds = DB::table('inventory_repair_items')->where('repair_id', $repair->id)->orderBy('id')->pluck('id');

                if ($itemIds->isEmpty()) {
                    return;
                }

                $share = floor($repair->biaya / $itemIds->count());
                $rest  = $repair->biaya - $share * $itemIds->count();

                foreach ($itemIds as $index => $itemId) {
                    DB::table('inventory_repair_items')->where('id', $itemId)->update([
                        'biaya'          => $share + ($index === $itemIds->count() - 1 ? $rest : 0),
                        'biaya_estimasi' => true,
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_repair_items', 'biaya')) {
            Schema::table('inventory_repair_items', function (Blueprint $table) {
                $table->dropColumn(['biaya', 'biaya_estimasi']);
            });
        }
    }
};
