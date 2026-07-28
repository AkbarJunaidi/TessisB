<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('surat_jalan_items', 'kategori_item')) {
            Schema::table('surat_jalan_items', function (Blueprint $table) {
                $table->string('kategori_item')->nullable()->after('inventory_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('surat_jalan_items', 'kategori_item')) {
            Schema::table('surat_jalan_items', function (Blueprint $table) {
                $table->dropColumn('kategori_item');
            });
        }
    }
};
