<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom progres ke report_exports - dipakai pendekatan "batch
 * lewat beberapa request kecil" (lihat InventoryService::processAllReportBatch)
 * alih-alih queue worker terpisah, supaya fitur Laporan Massal PDF tetap
 * jalan di hosting apa pun tanpa perlu proses background tambahan
 * (php artisan queue:work).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_exports', function (Blueprint $table) {
            $table->unsignedInteger('total_items')->default(0)->after('error_message');
            $table->unsignedInteger('processed_items')->default(0)->after('total_items');
        });
    }

    public function down(): void
    {
        Schema::table('report_exports', function (Blueprint $table) {
            $table->dropColumn(['total_items', 'processed_items']);
        });
    }
};
