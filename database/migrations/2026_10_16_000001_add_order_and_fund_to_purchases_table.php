<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchases') || Schema::hasColumn('purchases', 'shopping_channel')) {
            return;
        }

        Schema::table('purchases', function (Blueprint $table) {
            // vendor = hubungi vendor langsung, online = belanja lewat toko online.
            $table->string('shopping_channel', 20)->default('vendor')->after('project_id');
            // upfront = bayar dulu, on_receipt = bayar saat terima, term = tempo (pakai due_date).
            $table->string('payment_timing', 20)->default('on_receipt')->after('shopping_channel');
            $table->date('due_date')->nullable()->after('payment_timing');
            $table->unsignedSmallInteger('submit_count')->default(0)->after('submitted_at');

            $table->dateTime('ordered_at')->nullable();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expected_arrival')->nullable();
            $table->string('order_notes', 500)->nullable();
            $table->string('shop_name', 150)->nullable();
            $table->string('order_number', 100)->nullable();
            $table->string('order_url', 500)->nullable();
            $table->string('courier', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();

            // Jalur dana: requested (dana diminta) -> at_finance (sudah ditransfer ke finance).
            $table->string('fund_status', 20)->nullable();
            $table->dateTime('fund_requested_at')->nullable();
            $table->decimal('fund_amount', 15, 2)->nullable();
            $table->date('fund_transferred_on')->nullable();
            $table->foreignId('fund_transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('fund_note', 255)->nullable();
        });

        DB::table('purchases')->whereNotNull('submitted_at')->update(['submit_count' => 1]);
    }

    public function down(): void
    {
        if (!Schema::hasColumn('purchases', 'shopping_channel')) {
            return;
        }

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ordered_by');
            $table->dropConstrainedForeignId('fund_transferred_by');
            $table->dropColumn([
                'shopping_channel', 'payment_timing', 'due_date', 'submit_count',
                'ordered_at', 'expected_arrival', 'order_notes', 'shop_name', 'order_number',
                'order_url', 'courier', 'tracking_number',
                'fund_status', 'fund_requested_at', 'fund_amount', 'fund_transferred_on', 'fund_note',
            ]);
        });
    }
};
