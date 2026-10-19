<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_attachments')) {
            return;
        }

        Schema::create('purchase_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            // nota, order, resi, fund, payment, other
            $table->string('stage', 20);
            $table->string('path');
            $table->string('original_name', 150);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['purchase_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_attachments');
    }
};
