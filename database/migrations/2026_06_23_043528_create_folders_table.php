<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Self-referencing foreign key untuk mendukung struktur subfolder bertingkat
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('folders')
                ->onDelete('cascade'); // Jika folder induk dihapus, subfolder di dalamnya otomatis terhapus

            // Folder khusus dokumen 1 project (nullable = folder bersama biasa)
            $table->foreignId('project_id')
                ->nullable()
                ->unique()
                ->constrained('projects')
                ->nullOnDelete();

            // Relasi ke tabel users sebagai pembuat folder
            $table->foreignId('created_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes(); // Sesuai standarisasi spesifikasi Database Design.md

            // Indexing untuk optimalisasi query read folder bertingkat
            $table->index(['parent_id', 'created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
