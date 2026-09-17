<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel notifications - skema STANDAR bawaan Laravel untuk sistem Database
 * Notification (`Illuminate\Notifications\Notifiable`, sudah dipasang di
 * model User sejak awal). Biasanya di-generate otomatis lewat
 * `php artisan notifications:table`, ditulis manual di sini karena sandbox
 * pengembangan ini tidak bisa menjalankan Artisan.
 *
 * JANGAN diubah strukturnya (nama kolom/tipe) - kode inti Laravel
 * (DatabaseNotification, $user->notifications, markAsRead(), dst)
 * bergantung persis pada skema ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
