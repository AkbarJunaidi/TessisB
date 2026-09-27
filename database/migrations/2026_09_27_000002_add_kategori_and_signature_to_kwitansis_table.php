<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('kwitansis', 'kategori_pembayaran')) {
            Schema::table('kwitansis', function (Blueprint $table) {
                // booking_fee | dp | pelunasan | null (dipilih EKSPLISIT di
                // form Kwitansi - sebelumnya sempat ditebak dari teks
                // Keterangan, sekarang jadi pilihan sungguhan).
                $table->string('kategori_pembayaran')->nullable()->after('keterangan');

                // Tanda tangan yang dipilih saat kwitansi ini dibuat -
                // nullOnDelete supaya kwitansi tidak rusak kalau tanda
                // tangannya nanti dihapus dari daftar tanda tangan user.
                $table->foreignId('signature_id')->nullable()->after('created_by')
                    ->constrained('signatures')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('kwitansis', function (Blueprint $table) {
            if (Schema::hasColumn('kwitansis', 'signature_id')) {
                $table->dropConstrainedForeignId('signature_id');
            }
            if (Schema::hasColumn('kwitansis', 'kategori_pembayaran')) {
                $table->dropColumn('kategori_pembayaran');
            }
        });
    }
};
