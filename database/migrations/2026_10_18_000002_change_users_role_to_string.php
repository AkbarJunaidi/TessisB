<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // enum tiga nilai diganti string supaya bisa menyimpan kunci role kustom.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 60)->change();
        });
    }

    public function down(): void
    {
        // Pengguna dengan role kustom dikembalikan ke role dasarnya sebelum kolom kembali menjadi enum.
        if (Schema::hasTable('custom_roles')) {
            DB::table('custom_roles')->get()->each(
                fn ($role) => DB::table('users')->where('role', $role->slug)->update(['role' => $role->base_role])
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'admin', 'employee'])->change();
        });
    }
};
