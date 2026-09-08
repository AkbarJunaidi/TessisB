<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan info kontak client langsung di Project: Nama Perusahaan,
     * Email, No. Telepon (opsional semua, melengkapi field client & pic
     * yang sudah ada sebelumnya).
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'company')) {
                $table->string('company')->nullable()->after('pic');
            }
            if (!Schema::hasColumn('projects', 'email')) {
                $table->string('email')->nullable()->after('company');
            }
            if (!Schema::hasColumn('projects', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            foreach (['company', 'email', 'phone'] as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
