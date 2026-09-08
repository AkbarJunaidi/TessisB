<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom QR Code kedua, khusus untuk Inventory Report - isinya Signed
     * URL permanen ke halaman scan publik (bukan serial number seperti
     * qr_code/QR Label). Dipisah dari kolom qr_code karena keduanya harus
     * ada bersamaan (2 gambar QR berbeda untuk 1 barang).
     */
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            if (!Schema::hasColumn('inventories', 'qr_code_report')) {
                $table->string('qr_code_report')->nullable()->after('qr_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            if (Schema::hasColumn('inventories', 'qr_code_report')) {
                $table->dropColumn('qr_code_report');
            }
        });
    }
};
