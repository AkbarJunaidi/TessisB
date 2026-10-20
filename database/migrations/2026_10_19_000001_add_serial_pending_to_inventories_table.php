<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            // true = serial number masih sementara (barang diterima dari Pembelian sebelum serial diketahui).
            $table->boolean('serial_pending')->default(false)->after('serial_number')->index();
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropIndex(['serial_pending']);
            $table->dropColumn('serial_pending');
        });
    }
};
