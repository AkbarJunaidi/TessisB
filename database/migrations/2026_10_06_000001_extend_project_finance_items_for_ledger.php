<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel project_finance_items menjadi buku kas semua transaksi: boleh tanpa project,
     * punya tanggal, kategori, pihak, penerima, metode, dan sumber (pembelian/servis).
     */
    public function up(): void
    {
        Schema::table('project_finance_items', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->change();
        });

        Schema::table('project_finance_items', function (Blueprint $table) {
            $table->date('tanggal')->nullable()->after('amount');
            $table->string('category', 50)->nullable()->after('tanggal');
            $table->foreignId('contact_id')->nullable()->after('category')->constrained('contacts')->nullOnDelete();
            $table->string('recipient', 100)->nullable()->after('contact_id');
            $table->string('payment_method', 50)->nullable()->after('recipient');
            $table->string('source_type', 30)->nullable()->after('payment_method');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->foreignId('created_by')->nullable()->after('source_id')->constrained('users')->nullOnDelete();

            $table->index('tanggal');
            $table->index('category');
            $table->index(['source_type', 'source_id']);
        });

        $this->backfill();
    }

    public function down(): void
    {
        DB::table('project_finance_items')->whereNull('project_id')->delete();

        Schema::table('project_finance_items', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['created_by']);
            $table->dropIndex(['tanggal']);
            $table->dropIndex(['category']);
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['tanggal', 'category', 'contact_id', 'recipient', 'payment_method', 'source_type', 'source_id', 'created_by']);
        });

        Schema::table('project_finance_items', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
        });
    }

    /** Isi data lama; aman dijalankan ulang karena baris bersumber dicek dulu. */
    private function backfill(): void
    {
        $items = DB::table('project_finance_items');

        // Tanggal lama memakai tanggal dibuat (form Data Keuangan dulu menulis ulang baris tiap simpan).
        (clone $items)->whereNull('tanggal')->update(['tanggal' => DB::raw('DATE(created_at)')]);
        (clone $items)->whereNull('category')->where('type', 'income')->update(['category' => 'Pendapatan Project']);
        (clone $items)->whereNull('category')->where('type', 'expense')->update(['category' => 'Biaya Project']);

        $contactOrNull = fn ($id) => $id && DB::table('contacts')->where('id', $id)->exists() ? $id : null;

        foreach (DB::table('purchases')->where('payment_status', 'Dibayar')->get() as $p) {
            if (DB::table('project_finance_items')->where('source_type', 'purchase')->where('source_id', $p->id)->exists()) {
                continue;
            }

            $fields = [
                'category'    => 'Pembelian',
                'contact_id'  => $contactOrNull($p->vendor_id),
                'tanggal'     => $p->paid_at ?: substr((string) $p->updated_at, 0, 10),
                'source_type' => 'purchase',
                'source_id'   => $p->id,
            ];

            $match = $p->project_id
                ? DB::table('project_finance_items')
                    ->where('project_id', $p->project_id)->where('type', 'expense')->whereNull('source_type')
                    ->where('description', 'like', "Pembelian {$p->code} - %")->orderBy('id')->value('id')
                : null;

            if ($match) {
                DB::table('project_finance_items')->where('id', $match)->update($fields);
                continue;
            }

            DB::table('project_finance_items')->insert($fields + [
                'project_id'  => $p->project_id,
                'type'        => 'expense',
                'amount'      => $p->total,
                'description' => "Pembelian {$p->code} - {$p->vendor_name}",
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        foreach (DB::table('inventory_repairs')->where('status', 'Selesai')->where('biaya', '>', 0)->get() as $r) {
            if (DB::table('project_finance_items')->where('source_type', 'repair')->where('source_id', $r->id)->exists()) {
                continue;
            }

            DB::table('project_finance_items')->insert([
                'project_id'  => null,
                'type'        => 'expense',
                'amount'      => $r->biaya,
                'description' => "Perbaikan {$r->code} - {$r->tempat_nama}",
                'tanggal'     => $r->tanggal_selesai ?: substr((string) $r->updated_at, 0, 10),
                'category'    => 'Servis Alat',
                'contact_id'  => $contactOrNull($r->vendor_id),
                'source_type' => 'repair',
                'source_id'   => $r->id,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
};
