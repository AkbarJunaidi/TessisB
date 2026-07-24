<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('serial_number')->unique(); // Menjamin tidak ada SN kembar
            $table->text('description')->nullable();
            $table->string('status')->default('Tersedia');
            $table->string('brand')->nullable(); // Menyimpan nama brand/merek barang
            $table->string('image')->nullable(); // Menyimpan path foto barang
            $table->string('qr_code')->nullable(); // Menyimpan path foto QR Code
            $table->timestamps();   // Membuat created_at & updated_at
            $table->softDeletes(); // Membuat deleted_at untuk Soft Delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
