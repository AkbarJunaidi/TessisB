<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan index pada kolom yang sering dipakai untuk filter/sort/notifikasi
 * tapi belum ter-index - supaya query tetap cepat seiring data bertambah
 * (kalender project, notifikasi navbar yang di-poll tiap 60 detik, filter
 * status di Inventory/Task/Surat Jalan). Migration ini hanya MENAMBAH index,
 * tidak mengubah kolom/data yang sudah ada - aman dijalankan di database
 * yang sudah berjalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Dipakai Kalender Project & 2 notifikasi navbar (whereBetween/whereMonth
        // pada deadline & event_date, dijalankan tiap 60 detik oleh browser).
        Schema::table('projects', function (Blueprint $table) {
            $table->index('deadline');
            $table->index('event_date');
            $table->index('status');
        });

        // Dipakai filter dropdown status di halaman Inventory.
        Schema::table('inventories', function (Blueprint $table) {
            $table->index('status');
        });

        // Dipakai perhitungan "unit Tersedia" (withCount di scopeWithAvailability)
        // dan halaman detail unit per item.
        Schema::table('inventory_units', function (Blueprint $table) {
            $table->index('status');
        });

        // Dipakai filter status Surat Jalan (Aktif/selesai/dsb).
        Schema::table('surat_jalans', function (Blueprint $table) {
            $table->index('status');
        });

        // Dipakai Kanban board (filter task per kolom/status).
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['deadline']);
            $table->dropIndex(['event_date']);
            $table->dropIndex(['status']);
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('inventory_units', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('surat_jalans', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
