<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengubah Crew/Tim Project dari "harus user terdaftar" (pivot project_users)
 * menjadi input nama bebas (project_crews) - tidak perlu akun untuk
 * didaftarkan sebagai crew.
 *
 * Tabel `project_users` yang lama SENGAJA TIDAK dihapus di migration ini -
 * datanya dibackfill (dikonversi jadi nama teks) ke project_crews di bawah,
 * supaya crew yang sudah pernah didaftarkan tidak hilang. Setelah dipastikan
 * semuanya berjalan normal, project_users bisa dihapus lewat migration
 * terpisah di kemudian hari kalau memang sudah tidak diperlukan lagi.
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

        // Backfill: setiap crew berbasis akun yang sudah ada dikonversi jadi
        // baris project_crews dengan nama user tsb sebagai teks bebas.
        if (Schema::hasTable('project_users') && Schema::hasTable('users')) {
            DB::table('project_users')
                ->join('users', 'users.id', '=', 'project_users.user_id')
                ->select(
                    'project_users.project_id',
                    'users.name',
                    'project_users.role_label',
                    'project_users.created_at',
                    'project_users.updated_at'
                )
                ->orderBy('project_users.id')
                ->chunk(200, function ($rows) {
                    $now = now();

                    DB::table('project_crews')->insert(
                        $rows->map(fn ($row) => [
                            'project_id' => $row->project_id,
                            'name'       => $row->name,
                            'role_label' => $row->role_label,
                            'created_at' => $row->created_at ?? $now,
                            'updated_at' => $row->updated_at ?? $now,
                        ])->all()
                    );
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_crews');
    }
};
