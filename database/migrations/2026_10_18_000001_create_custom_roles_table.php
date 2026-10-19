<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('custom_roles')) {
            return;
        }

        Schema::create('custom_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            // Kunci tetap yang tersimpan di users.role, tidak berubah walau nama role diganti.
            $table->string('slug', 60)->unique();
            // admin atau employee: menentukan halaman dan notifikasi tingkat role (izin tetap dari kolom permissions).
            $table->string('base_role', 20)->default('employee');
            $table->string('description', 255)->nullable();
            $table->json('permissions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_roles');
    }
};
