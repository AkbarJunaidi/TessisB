<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchase_items') || Schema::hasColumn('purchase_items', 'qty_received')) {
            return;
        }

        Schema::table('purchase_items', function (Blueprint $table) {
            // Jumlah yang sudah masuk (penerimaan bisa bertahap) dan yang sudah diretur ke vendor.
            $table->unsignedInteger('qty_received')->default(0)->after('qty');
            $table->unsignedInteger('qty_returned')->default(0)->after('qty_received');
            // Harga di PO, terisi hanya bila harga aktual saat barang diterima berbeda.
            $table->decimal('po_unit_price', 15, 2)->nullable()->after('unit_price');
        });

        // Pembelian yang sudah Diterima sebelum fitur ini: semua barangnya dianggap sudah masuk.
        DB::table('purchase_items')
            ->whereIn('purchase_id', fn ($query) => $query->select('id')->from('purchases')->where('status', 'Diterima'))
            ->update(['qty_received' => DB::raw('qty')]);
    }

    public function down(): void
    {
        if (!Schema::hasColumn('purchase_items', 'qty_received')) {
            return;
        }

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['qty_received', 'qty_returned', 'po_unit_price']);
        });
    }
};
