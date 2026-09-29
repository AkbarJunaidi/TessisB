<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();

                // vendor_name = snapshot, supaya riwayat tetap terbaca kalau kontak vendor dihapus.
                $table->foreignId('vendor_id')->nullable()->constrained('contacts')->nullOnDelete();
                $table->string('vendor_name');

                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->date('purchase_date');
                $table->decimal('total', 15, 2)->default(0);

                // status: Draft|Diajukan|Disetujui|Ditolak|Diterima|Dibatalkan
                $table->string('status')->default('Draft');
                // payment_status: Belum Dibayar|Dibayar - terpisah dari status barang.
                $table->string('payment_status')->default('Belum Dibayar');
                $table->string('payment_method')->nullable();
                $table->date('paid_at')->nullable();

                $table->text('notes')->nullable();
                $table->string('attachment')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('received_at')->nullable();

                $table->timestamps();

                $table->index(['status', 'payment_status']);
                $table->index('purchase_date');
            });
        }

        if (!Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
                $table->string('name');
                $table->unsignedInteger('qty');
                $table->decimal('unit_price', 15, 2);
                $table->decimal('subtotal', 15, 2);

                // none = bukan stok | existing = tambah stok barang ada | new = daftarkan barang baru
                $table->string('stock_mode')->default('none');
                $table->foreignId('inventory_id')->nullable()->constrained('inventories')->nullOnDelete();
                $table->string('brand', 100)->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
