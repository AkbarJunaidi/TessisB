<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Harga beli per unit yang diisi manual; cadangan bila barang belum pernah dibeli lewat Pembelian. */
    public function up(): void
    {
        if (!Schema::hasColumn('inventories', 'harga_manual')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->decimal('harga_manual', 15, 2)->nullable()->after('brand');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventories', 'harga_manual')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropColumn('harga_manual');
            });
        }
    }
};
