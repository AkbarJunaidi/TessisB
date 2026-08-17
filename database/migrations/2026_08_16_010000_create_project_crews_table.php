<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crew/Tim Project - input nama bebas, TIDAK perlu akun user untuk
 * didaftarkan sebagai crew.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_crews')) {
            Schema::create('project_crews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
                $table->string('name');
                $table->string('role_label');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_crews');
    }
};
