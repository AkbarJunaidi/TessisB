<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ambang "pertimbangkan ganti" khusus satu barang; null berarti ikut Pengaturan. */
    public function up(): void
    {
        if (!Schema::hasColumn('inventories', 'ambang_ganti_persen')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->unsignedSmallInteger('ambang_ganti_persen')->nullable()->after('harga_manual_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventories', 'ambang_ganti_persen')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropColumn('ambang_ganti_persen');
            });
        }
    }
};
