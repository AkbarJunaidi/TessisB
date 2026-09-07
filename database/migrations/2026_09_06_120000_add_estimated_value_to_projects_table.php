<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom estimasi nilai deal untuk fitur papan Pipeline
     * (dikelompokkan berdasarkan kolom `status` yang sudah ada:
     * Draft, Scheduled, Confirmed, In Progress, On Review, Done).
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'estimated_value')) {
                $table->decimal('estimated_value', 15, 2)->nullable()->after('priority');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'estimated_value')) {
                $table->dropColumn('estimated_value');
            }
        });
    }
};
