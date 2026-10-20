<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // shopping_type: vendor|online. payment_timing null = pembelian lama (bisa dibayar kapan saja setelah disetujui).
            $table->string('shopping_type', 10)->default('vendor')->after('project_id');
            $table->string('payment_timing', 20)->nullable()->after('shopping_type');
            $table->date('due_date')->nullable()->after('payment_timing');
            $table->unsignedSmallInteger('resubmit_count')->default(0)->after('submitted_at');

            $table->timestamp('ordered_at')->nullable()->after('approved_at');
            $table->foreignId('ordered_by')->nullable()->after('ordered_at')->constrained('users')->nullOnDelete();
            $table->date('expected_at')->nullable()->after('ordered_by');
            $table->string('store_name', 100)->nullable()->after('expected_at');
            $table->string('order_number', 100)->nullable()->after('store_name');
            $table->string('order_url', 500)->nullable()->after('order_number');
            $table->string('courier', 50)->nullable()->after('order_url');
            $table->string('tracking_number', 100)->nullable()->after('courier');

            // fund_status: requested|at_finance. Jalur uang tambahan di atas payment_status (Belum Dibayar|Dibayar).
            $table->string('fund_status', 20)->nullable()->after('paid_by');
            $table->decimal('fund_amount', 15, 2)->nullable()->after('fund_status');
            $table->date('fund_at')->nullable()->after('fund_amount');
            $table->foreignId('fund_by')->nullable()->after('fund_at')->constrained('users')->nullOnDelete();
            $table->string('fund_note')->nullable()->after('fund_by');

            $table->index('payment_timing');
        });

        Schema::create('purchase_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 30);
            $table->string('label', 150);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['purchase_id', 'created_at']);
        });

        Schema::create('purchase_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            // stage: invoice|order|receipt|fund|payment
            $table->string('stage', 20);
            $table->string('path');
            $table->string('original_name');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['purchase_id', 'stage']);
        });

        $this->backfillHistories();
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_attachments');
        Schema::dropIfExists('purchase_histories');

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ordered_by');
            $table->dropConstrainedForeignId('fund_by');
            $table->dropIndex(['payment_timing']);
            $table->dropColumn([
                'shopping_type', 'payment_timing', 'due_date', 'resubmit_count',
                'ordered_at', 'expected_at', 'store_name', 'order_number', 'order_url', 'courier', 'tracking_number',
                'fund_status', 'fund_amount', 'fund_at', 'fund_note',
            ]);
        });
    }

    /**
     * Pembelian lama diberi riwayat dari kolom waktu yang sudah ada supaya kartu Riwayat tidak kosong.
     */
    private function backfillHistories(): void
    {
        DB::table('purchases')->orderBy('id')->chunkById(200, function ($rows) {
            $insert = [];

            foreach ($rows as $p) {
                $events = [
                    ['created', 'Dibuat', $p->created_at, $p->created_by],
                    ['submitted', 'Diajukan', $p->submitted_at, $p->created_by],
                    ['approved', 'Disetujui', $p->approved_at, null],
                    ['received', 'Diterima', $p->received_at, $p->received_by],
                    ['paid', 'Dibayar', $p->paid_at, $p->paid_by],
                ];

                foreach ($events as [$event, $label, $at, $userId]) {
                    if ($at) {
                        $insert[] = [
                            'purchase_id' => $p->id,
                            'user_id'     => $userId,
                            'event'       => $event,
                            'label'       => $label,
                            'note'        => null,
                            'created_at'  => $at,
                        ];
                    }
                }
            }

            if ($insert) {
                DB::table('purchase_histories')->insert($insert);
            }
        });
    }
};
