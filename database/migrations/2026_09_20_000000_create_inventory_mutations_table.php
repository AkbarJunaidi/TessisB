<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buku besar mutasi barang - 1 baris = 1 KEJADIAN per transaksi (bukan per
 * unit fisik), dikelompokkan berdasarkan jumlah (contoh: "3 unit, Hilang,
 * 20/09/2026"). Dipakai oleh halaman Mutasi Aset (lintas semua barang, ada
 * filter) - BEDA dari card "Riwayat Peminjaman" di Inventory Detail yang
 * sudah ada (itu cuma pinjam/kembali untuk 1 barang, dibiarkan tidak
 * berubah). Tabel ini murni catatan (append-only), tidak pernah di-update
 * atau dihapus oleh aplikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_mutations')) {
            Schema::create('inventory_mutations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();

                // ditambahkan, dihapus, dipinjam, dikembalikan, status_berubah
                $table->string('event_type');
                $table->unsignedInteger('qty');

                // Cuma diisi untuk event_type=status_berubah (Tersedia/Rusak/Perbaikan/Hilang)
                $table->string('status_before')->nullable();
                $table->string('status_after')->nullable();

                // Cuma diisi untuk event_type=dipinjam/dikembalikan
                $table->foreignId('surat_jalan_id')->nullable()
                    ->constrained('surat_jalans')->nullOnDelete();

                // Null = kejadian dari backfill data lama (lihat command
                // inventory:backfill-mutasi), bukan berarti sistemnya.
                $table->foreignId('actor_id')->nullable()
                    ->constrained('users')->nullOnDelete();

                $table->string('keterangan')->nullable();

                $table->timestamps();

                $table->index(['inventory_id', 'created_at']);
                $table->index('event_type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_mutations');
    }
};
