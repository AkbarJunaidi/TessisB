<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menghubungkan unit fisik dengan baris Surat Jalan Item yang sedang
     * meminjamnya. Null berarti unit sedang tidak dipinjam siapa pun
     * (masih di gudang), diisi berarti unit itu sedang keluar lewat
     * Surat Jalan tersebut.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('inventory_units', 'surat_jalan_item_id')) {
            Schema::table('inventory_units', function (Blueprint $table) {
                $table->foreignId('surat_jalan_item_id')->nullable()->after('status')
                    ->constrained('surat_jalan_items')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_units', 'surat_jalan_item_id')) {
            Schema::table('inventory_units', function (Blueprint $table) {
                $table->dropConstrainedForeignId('surat_jalan_item_id');
            });
        }
    }
};
