<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('surat_jalan_items')) {
            Schema::create('surat_jalan_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('surat_jalan_id')->constrained('surat_jalans')->onDelete('cascade');
                $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
                $table->string('kategori_item')->nullable();

                $table->unsignedInteger('qty_dipakai');
                $table->unsignedInteger('qty_dikembalikan')->default(0);

                $table->timestamps();

                $table->index(['inventory_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_jalan_items');
    }
};
