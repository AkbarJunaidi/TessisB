<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->unsignedInteger('received_qty')->default(0)->after('qty');
            $table->unsignedInteger('returned_qty')->default(0)->after('received_qty');
            // Harga aktual per unit saat diterima; null = sama dengan harga di PO.
            $table->decimal('actual_unit_price', 15, 2)->nullable()->after('unit_price');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)->nullable()->after('payment_method');
        });

        // Pembelian lama yang sudah Diterima/Dibayar dianggap lengkap.
        DB::table('purchase_items')
            ->whereIn('purchase_id', DB::table('purchases')->where('status', 'Diterima')->select('id'))
            ->update(['received_qty' => DB::raw('qty')]);

        DB::table('purchases')
            ->where('payment_status', 'Dibayar')
            ->update(['paid_amount' => DB::raw('total')]);
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['received_qty', 'returned_qty', 'actual_unit_price']);
        });
    }
};
