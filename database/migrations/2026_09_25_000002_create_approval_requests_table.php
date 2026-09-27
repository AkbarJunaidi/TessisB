<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel generik untuk SEMUA jenis permintaan approval lintas modul -
     * relasi polymorphic (requestable_type/id) supaya jenis approval baru
     * di masa depan tidak perlu tabel baru, cukup tambah handler baru di
     * ApprovalService::HANDLERS.
     */
    public function up(): void
    {
        if (!Schema::hasTable('approval_requests')) {
            Schema::create('approval_requests', function (Blueprint $table) {
                $table->id();
                $table->string('type'); // contoh: kwitansi_void
                $table->morphs('requestable');
                $table->json('payload')->nullable();
                $table->string('status')->default('pending'); // pending | approved | rejected

                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('decided_at')->nullable();

                $table->string('reason')->nullable();
                $table->string('decision_note')->nullable();

                $table->timestamps();

                $table->index(['status', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
