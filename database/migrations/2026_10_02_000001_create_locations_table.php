<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Lokasi: kantor, gudang, lokasi event, atau lokasi lain yang bisa dipakai ulang
     * (dengan autocomplete) - mirip buku alamat Kontak.
     *
     * - can_store_units : lokasi ini boleh dipakai untuk menyimpan unit barang (kantor/gudang).
     * - is_default      : lokasi awal untuk unit baru (tepat satu).
     * - radius_m        : radius deteksi GPS dalam meter (default 100).
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('jenis', 20)->default('kantor'); // kantor, gudang, event, lainnya
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius_m')->default(100);
            $table->boolean('can_store_units')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['jenis', 'name']);
            $table->index(['is_active', 'can_store_units']);
        });

        // Lokasi awal bawaan agar semua unit yang sudah ada punya lokasi.
        // Nama dan koordinatnya bisa diubah Super Admin lewat halaman Lokasi.
        DB::table('locations')->insert([
            'name'            => 'Kantor Utama',
            'jenis'           => 'kantor',
            'radius_m'        => 100,
            'can_store_units' => true,
            'is_default'      => true,
            'is_active'       => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
