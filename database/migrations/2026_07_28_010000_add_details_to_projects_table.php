<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom detail project (PRD v2.0 - Project Management).
     * Seluruh kolom baru nullable/berdefault agar data project lama tetap valid.
     * Setiap kolom dicek dulu (hasColumn) agar migration ini aman dijalankan ulang.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'client')) {
                $table->string('client')->nullable()->after('name');
            }
            if (!Schema::hasColumn('projects', 'pic')) {
                $table->string('pic')->nullable()->after('client');
            }
            if (!Schema::hasColumn('projects', 'category')) {
                $table->string('category')->nullable()->after('pic');
            }
            if (!Schema::hasColumn('projects', 'event_date')) {
                $table->date('event_date')->nullable()->after('deadline');
            }
            if (!Schema::hasColumn('projects', 'event_time_start')) {
                $table->time('event_time_start')->nullable()->after('event_date');
            }
            if (!Schema::hasColumn('projects', 'event_time_end')) {
                $table->time('event_time_end')->nullable()->after('event_time_start');
            }
            if (!Schema::hasColumn('projects', 'location')) {
                $table->string('location')->nullable()->after('event_time_end');
            }
            if (!Schema::hasColumn('projects', 'address')) {
                $table->text('address')->nullable()->after('location');
            }
            if (!Schema::hasColumn('projects', 'estimated_duration_minutes')) {
                $table->unsignedInteger('estimated_duration_minutes')->nullable()->after('address');
            }
            if (!Schema::hasColumn('projects', 'priority')) {
                $table->string('priority')->default('Normal')->after('estimated_duration_minutes');
            }
            if (!Schema::hasColumn('projects', 'status')) {
                $table->string('status')->default('Scheduled')->after('priority');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $columns = ['client', 'pic', 'category', 'event_date', 'event_time_start', 'event_time_end',
                        'location', 'address', 'estimated_duration_minutes', 'priority', 'status'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
