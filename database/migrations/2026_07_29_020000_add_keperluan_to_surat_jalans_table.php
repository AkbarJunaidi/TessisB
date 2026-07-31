<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('surat_jalans', 'keperluan')) {
            Schema::table('surat_jalans', function (Blueprint $table) {
                $table->string('keperluan')->nullable()->after('kepada');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('surat_jalans', 'keperluan')) {
            Schema::table('surat_jalans', function (Blueprint $table) {
                $table->dropColumn('keperluan');
            });
        }
    }
};
