<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'is_client')) {
                $table->boolean('is_client')->default(true)->after('notes');
            }
            if (!Schema::hasColumn('contacts', 'is_vendor')) {
                $table->boolean('is_vendor')->default(false)->after('is_client');
                $table->index('is_vendor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'is_vendor')) {
                $table->dropIndex(['is_vendor']);
                $table->dropColumn('is_vendor');
            }
            if (Schema::hasColumn('contacts', 'is_client')) {
                $table->dropColumn('is_client');
            }
        });
    }
};
