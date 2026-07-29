<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unit fisik per entri Inventory. 1 entri Inventory (1 serial number/QR)
     * bisa punya banyak unit fisik (sejumlah quantity_total), masing-masing
     * dengan status kondisi sendiri (Tersedia/Rusak/Perbaikan/Hilang).
     */
    public function up(): void
    {
        if (!Schema::hasTable('inventory_units')) {
            Schema::create('inventory_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
                $table->unsignedInteger('unit_number');
                $table->string('status')->default('Tersedia'); // Tersedia, Rusak, Perbaikan, Hilang
                $table->timestamps();

                $table->unique(['inventory_id', 'unit_number']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_units');
    }
};
