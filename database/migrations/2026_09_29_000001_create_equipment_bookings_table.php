<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('equipment_bookings')) {
            return;
        }

        Schema::create('equipment_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->unsignedInteger('qty');

            // Dipesan = rencana, belum ada Surat Jalan. Terpenuhi = sudah ada Surat
            // Jalan yang menyerap booking ini (lihat SuratJalanService). Dibatalkan =
            // rencana batal, dibiarkan tersimpan sebagai riwayat, bukan dihapus.
            $table->string('status')->default('Dipesan');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_id', 'status']);
            $table->unique(['project_id', 'inventory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_bookings');
    }
};
