<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data keuangan manual per project (diisi Super Admin lewat card
     * "Data Keuangan" di Detail Project). Nullable karena sifatnya opsional
     * sampai memang diisi - baru diwajibkan saat mau export Laporan
     * Keuangan Bulanan (divalidasi di FinancialReportService, bukan di DB).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('projects', 'revenue')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->decimal('revenue', 15, 2)->nullable()->after('status');
                $table->decimal('expense', 15, 2)->nullable()->after('revenue');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'revenue')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn(['revenue', 'expense']);
            });
        }
    }
};
