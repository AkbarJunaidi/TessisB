<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Anggaran biaya per project (dibandingkan dengan total pengeluaran di Data Keuangan). */
    public function up(): void
    {
        if (!Schema::hasColumn('projects', 'budget')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->decimal('budget', 15, 2)->nullable()->after('estimated_value');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'budget')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('budget');
            });
        }
    }
};
