<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Hapus password plaintext hasil reset; isinya ikut hilang bersama kolomnya. */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'temp_password_plain')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('temp_password_plain');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'temp_password_plain')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('temp_password_plain')->nullable()->after('password');
            });
        }
    }
};
