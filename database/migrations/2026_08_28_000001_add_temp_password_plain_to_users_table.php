<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom untuk menyimpan password sementara (plaintext) hasil
     * reset oleh Super Admin, supaya bisa ditampilkan lagi di halaman Detail
     * User (dipakai Super Admin untuk memberitahu password baru ke user,
     * karena project ini tidak mengirim email). Kolom ini TIDAK dipakai
     * untuk autentikasi - login tetap memakai kolom `password` yang di-hash.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('temp_password_plain')->nullable()->after('password');
        });
    }

    /**
     * Menghapus kolom temp_password_plain.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('temp_password_plain');
        });
    }
};
