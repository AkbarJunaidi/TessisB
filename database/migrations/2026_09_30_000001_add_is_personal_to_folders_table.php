<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menandai folder pribadi (My Files) vs folder bersama (Folder Management).
 * Semua folder yang sudah ada otomatis menjadi folder bersama (default false).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->boolean('is_personal')->default(false)->after('project_id');
            $table->index(['is_personal', 'created_by', 'parent_id'], 'folders_personal_owner_parent_index');
        });
    }

    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropIndex('folders_personal_owner_parent_index');
            $table->dropColumn('is_personal');
        });
    }
};
