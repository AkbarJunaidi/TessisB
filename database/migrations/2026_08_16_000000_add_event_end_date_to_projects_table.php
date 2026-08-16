<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan `event_end_date` (Tanggal Acara Selesai) supaya project bisa
 * merepresentasikan acara yang berlangsung lebih dari 1 hari.
 *
 * `event_date` yang sudah ada TIDAK diganti nama - kolom itu sekarang
 * berperan sebagai "Tanggal Acara Mulai". Ini sengaja supaya tidak perlu
 * mengubah kode lain yang sudah bergantung pada nama kolom `event_date`
 * (Kalender Project, Notifikasi, Laporan Keuangan, dsb).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'event_end_date')) {
                $table->date('event_end_date')->nullable()->after('event_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'event_end_date')) {
                $table->dropColumn('event_end_date');
            }
        });
    }
};
