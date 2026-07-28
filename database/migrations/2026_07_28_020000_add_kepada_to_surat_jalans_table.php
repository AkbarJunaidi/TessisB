<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_jalans', function (Blueprint $table) {
            if (!Schema::hasColumn('surat_jalans', 'kepada')) {
                $table->string('kepada')->nullable()->after('project_id');
            }
            if (!Schema::hasColumn('surat_jalans', 'tanggal_acara_selesai')) {
                $table->date('tanggal_acara_selesai')->nullable()->after('tanggal_acara');
            }
        });
    }

    public function down(): void
    {
        Schema::table('surat_jalans', function (Blueprint $table) {
            foreach (['kepada', 'tanggal_acara_selesai'] as $column) {
                if (Schema::hasColumn('surat_jalans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
