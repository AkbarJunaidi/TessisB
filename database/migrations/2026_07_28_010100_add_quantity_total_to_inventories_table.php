<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('inventories', 'quantity_total')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->unsignedInteger('quantity_total')->default(1)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventories', 'quantity_total')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropColumn('quantity_total');
            });
        }
    }
};
