<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('folders', 'project_id')) {
            Schema::table('folders', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('parent_id')
                    ->constrained('projects')->nullOnDelete();
                $table->unique('project_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('folders', 'project_id')) {
            Schema::table('folders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('project_id');
            });
        }
    }
};
