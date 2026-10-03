<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal servis: aturan di level barang (interval hari / pemakaian, opsional),
 * hitungan di level unit (servis terakhir + jumlah pemakaian sejak servis).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->unsignedSmallInteger('servis_interval_hari')->nullable()->after('quantity_total');
            $table->unsignedSmallInteger('servis_interval_pemakaian')->nullable()->after('servis_interval_hari');
        });

        Schema::table('inventory_units', function (Blueprint $table) {
            $table->date('servis_terakhir_at')->nullable();
            $table->unsignedInteger('pemakaian_sejak_servis')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('inventory_units', function (Blueprint $table) {
            $table->dropColumn(['servis_terakhir_at', 'pemakaian_sejak_servis']);
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['servis_interval_hari', 'servis_interval_pemakaian']);
        });
    }
};
