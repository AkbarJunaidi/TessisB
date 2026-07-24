<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom board_lists pada tabel projects.
     *
     * Menyimpan daftar list/kolom board (label + warna) secara berurutan,
     * contoh: [{"label":"Todo","color":"secondary"}, ...].
     *
     * Jika null, board memakai 4 list default (Todo, In Progress,
     * Review, Done) supaya project lama tetap tampil normal.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            $table->json('board_lists')
                ->nullable()
                ->after('deadline');

        });
    }

    /**
     * Menghapus kolom board_lists dari tabel projects.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            $table->dropColumn('board_lists');

        });
    }
};
