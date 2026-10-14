<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rentang hari yang bisa diatur Super Admin per jenis notifikasi (NULL = pakai bawaan di NotificationService):
 * - unpaid_deadline: days_before / days_after = hari sebelum / sesudah deadline.
 * - finance_missing: days_before = hari menjelang akhir bulan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_type_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('days_before')->nullable()->after('sort_order');
            $table->unsignedSmallInteger('days_after')->nullable()->after('days_before');
        });
    }

    public function down(): void
    {
        Schema::table('notification_type_settings', function (Blueprint $table) {
            $table->dropColumn(['days_before', 'days_after']);
        });
    }
};
