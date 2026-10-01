<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kunci (gembok) untuk file & folder di ruang bersama.
     *
     * - locked_at : penanda kunci (NULL = tidak terkunci). Dipakai sebagai penentu, sehingga
     *               kunci tetap berlaku walau user yang mengunci kemudian dihapus.
     * - locked_by : user yang mengunci (hanya untuk ditampilkan).
     */
    public function up(): void
    {
        foreach (['folders', 'files'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->timestamp('locked_at')->nullable();
                $table->foreignId('locked_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['folders', 'files'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('locked_by');
                $table->dropColumn('locked_at');
            });
        }
    }
};
