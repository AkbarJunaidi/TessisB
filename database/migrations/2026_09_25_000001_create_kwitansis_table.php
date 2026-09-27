<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kwitansis')) {
            Schema::create('kwitansis', function (Blueprint $table) {
                $table->id();
                $table->string('nomor')->unique(); // contoh: KWT-2026-0007

                $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');

                // nullOnDelete (BEDA dari surat_jalans.created_by yang cascade) -
                // dokumen keuangan sengaja tidak ikut hilang kalau user pembuatnya dihapus.
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->date('tanggal');
                $table->decimal('jumlah', 15, 2);
                $table->string('metode_pembayaran')->nullable();
                $table->string('keterangan')->nullable();

                // Aktif | Dibatalkan - tidak pernah hard-delete, pembatalan lewat
                // Approval (lihat ApprovalService) supaya jejak keuangan tetap ada.
                $table->string('status')->default('Aktif');

                $table->timestamps();

                $table->index(['project_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kwitansis');
    }
};
