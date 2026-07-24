<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom permission_overrides pada tabel users.
     *
     * Jika null, user mengikuti permission default Role.
     * Jika berisi array JSON, permission tersebut menimpa (override)
     * permission default Role untuk user ini.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->json('permission_overrides')
                ->nullable()
                ->after('role');

        });
    }

    /**
     * Menghapus kolom permission_overrides dari tabel users.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn('permission_overrides');

        });
    }
};
