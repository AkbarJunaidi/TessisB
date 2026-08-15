<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom `deleted_by` (siapa yang menghapus) dan index pada
 * `deleted_at` untuk seluruh tabel yang dijadikan sumber fitur Trash:
 * projects, tasks, inventories, folders, files.
 *
 * Kenapa perlu index deleted_at:
 * Halaman Trash selalu query dengan onlyTrashed() (WHERE deleted_at IS NOT
 * NULL) dan mengurutkan berdasarkan deleted_at DESC pada tabel yang juga
 * dipakai sangat sering untuk query normal (WHERE deleted_at IS NULL via
 * global scope). Index gabungan (deleted_at, deleted_by) mempercepat kedua
 * pola query tersebut sekaligus filter "Dihapus Oleh" tanpa full table scan.
 *
 * deleted_by sengaja nullable + nullOnDelete: jika user yang menghapus
 * suatu data kemudian ikut dihapus, riwayat Trash tidak ikut hilang/rusak.
 */
return new class extends Migration
{
    /**
     * Daftar tabel trashable beserta kolom referensi setelahnya (untuk posisi kolom).
     */
    private const TARGET_TABLES = [
        'projects'    => 'deleted_at',
        'tasks'       => 'deleted_at',
        'inventories' => 'deleted_at',
        'folders'     => 'deleted_at',
        'files'       => 'deleted_at',
    ];

    public function up(): void
    {
        foreach (self::TARGET_TABLES as $table => $afterColumn) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $afterColumn) {
                if (!Schema::hasColumn($table, 'deleted_by')) {
                    $blueprint->foreignId('deleted_by')
                        ->nullable()
                        ->after($afterColumn)
                        ->constrained('users')
                        ->nullOnDelete();
                }

                $blueprint->index(['deleted_at', 'deleted_by'], $table . '_deleted_at_deleted_by_index');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TARGET_TABLES as $table => $afterColumn) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropIndex($table . '_deleted_at_deleted_by_index');

                if (Schema::hasColumn($table, 'deleted_by')) {
                    $blueprint->dropConstrainedForeignId('deleted_by');
                }
            });
        }
    }
};
