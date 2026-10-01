<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Folder pribadi (dibuat lewat My Files). Pemiliknya adalah `created_by`.
     * Semua folder yang sudah ada tetap menjadi folder bersama (default false).
     */
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->boolean('is_private')->default(false)->after('project_id');
            $table->index(['is_private', 'created_by']);
        });
    }

    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropIndex(['is_private', 'created_by']);
            $table->dropColumn('is_private');
        });
    }
};
