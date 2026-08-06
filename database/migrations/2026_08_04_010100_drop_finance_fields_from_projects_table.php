<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'revenue')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn(['revenue', 'expense']);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('projects', 'revenue')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->decimal('revenue', 15, 2)->nullable()->after('status');
                $table->decimal('expense', 15, 2)->nullable()->after('revenue');
            });
        }
    }
};
