<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_repairs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('vendor_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('tempat_nama');
            $table->text('tempat_alamat')->nullable();
            $table->string('status', 20)->default('Diservis'); // Diservis, Selesai, Dibatalkan
            $table->date('tanggal_masuk');
            $table->date('estimasi_selesai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('diantar_oleh')->nullable();
            $table->string('diambil_oleh')->nullable();
            $table->text('keluhan')->nullable();
            $table->text('catatan_hasil')->nullable();
            $table->decimal('biaya', 15, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'tanggal_masuk']);
        });

        Schema::create('inventory_repair_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_id')->constrained('inventory_repairs')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->foreignId('inventory_unit_id')->constrained('inventory_units')->cascadeOnDelete();
            $table->string('status_sebelum', 20);
            $table->string('hasil', 20)->nullable(); // Tersedia atau Rusak, diisi saat perbaikan selesai
            $table->timestamps();

            $table->index('inventory_unit_id');
        });

        Schema::table('inventory_units', function (Blueprint $table) {
            $table->foreignId('repair_item_id')->nullable()->constrained('inventory_repair_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('repair_item_id');
        });

        Schema::dropIfExists('inventory_repair_items');
        Schema::dropIfExists('inventory_repairs');
    }
};
