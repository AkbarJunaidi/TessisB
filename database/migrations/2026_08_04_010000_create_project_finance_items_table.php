<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Item Pendapatan/Pengeluaran project - masing-masing baris punya
     * nominal & keterangan sendiri (menggantikan kolom revenue/expense
     * tunggal yang sebelumnya ada di tabel projects).
     */
    public function up(): void
    {
        if (!Schema::hasTable('project_finance_items')) {
            Schema::create('project_finance_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
                $table->enum('type', ['income', 'expense']);
                $table->decimal('amount', 15, 2);
                $table->string('description')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_finance_items');
    }
};
