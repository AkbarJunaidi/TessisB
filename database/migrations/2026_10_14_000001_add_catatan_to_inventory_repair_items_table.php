<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Catatan hasil per unit (satu catatan dipakai bersama oleh unit sekelompok: berhasil atau gagal). */
    public function up(): void
    {
        if (!Schema::hasColumn('inventory_repair_items', 'catatan')) {
            Schema::table('inventory_repair_items', function (Blueprint $table) {
                $table->text('catatan')->nullable()->after('hasil');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_repair_items', 'catatan')) {
            Schema::table('inventory_repair_items', function (Blueprint $table) {
                $table->dropColumn('catatan');
            });
        }
    }
};
