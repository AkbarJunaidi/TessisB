<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivityLog;
use Carbon\Carbon;

class ClearOldActivityLogs extends Command
{
    // Perintah yang akan dijalankan di terminal
    protected $signature = 'mis:clear-logs';

    // Deskripsi utilitas command
    protected $description = 'Membersihkan data activity logs yang sudah berumur lebih dari 30 hari secara otomatis';

    public function handle()
    {
        // Menentukan batas tanggal (30 hari yang lalu)
        $expirationDate = Carbon::now()->subDays(30);

        $deletedRows = ActivityLog::query()
            ->whereDate('created_at', '<', $expirationDate)
            ->delete();

        $this->info("Pembersihan sukses! Total {$deletedRows} data log usang berhasil dihapus.");
    }
}
