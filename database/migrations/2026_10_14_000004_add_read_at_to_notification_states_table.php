<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `read_at` = notifikasi OTOMATIS sudah dibaca oleh user itu (PRIBADI, seperti `pinned_at`; beda dari
 * `dismissed_at` yang global). Yang sudah dibaca hilang dari lonceng user tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_states', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('pinned_at');
        });
    }

    public function down(): void
    {
        Schema::table('notification_states', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });
    }
};
