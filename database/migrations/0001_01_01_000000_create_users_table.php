
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menjalankan proses pembuatan tabel users.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {

            $table->id();

            // Informasi dasar pengguna
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            // Hak akses pengguna
            $table->enum('role', [
                'super_admin',
                'admin',
                'employee',
            ]);

            // Status akun
            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            // Waktu login terakhir
            $table->timestamp('last_login_at')->nullable();

            // Token Remember Me bawaan Laravel
            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();

            // Index untuk optimasi pencarian
            $table->index('role');
            $table->index('status');
        });
    }

    /**
     * Menghapus tabel users.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

