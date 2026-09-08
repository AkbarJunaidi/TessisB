<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Services\Inventory\QrCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class GenerateInventoryReportQr extends Command
{
    // Perintah yang akan dijalankan di terminal
    protected $signature = 'inventory:generate-report-qr {--force : Generate ulang walau file QR Report sudah ada}';

    // Deskripsi utilitas command
    protected $description = 'Generate QR Code Report (signed URL scan publik) untuk barang inventory lama yang dibuat sebelum fitur ini ada';

    public function handle(QrCodeService $qrCodeService): int
    {
        $force = $this->option('force');

        $query = Inventory::query();

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('qr_code_report')->orWhere('qr_code_report', '');
            });
        }

        $inventories = $query->get();

        if ($inventories->isEmpty()) {
            $this->info('Tidak ada barang inventory yang perlu di-generate QR Report-nya.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($inventories->count());
        $bar->start();

        $generated = 0;

        foreach ($inventories as $inventory) {
            // Skip kalau file fisiknya ternyata sudah ada (self-healing,
            // sama seperti logic di InventoryService::updateInventory())
            if (!$force && $inventory->qr_code_report && Storage::disk('public')->exists($inventory->qr_code_report)) {
                $bar->advance();
                continue;
            }

            $path = $qrCodeService->generateFromUrl(
                URL::signedRoute('inventory.scan', ['inventory' => $inventory->id]),
                (string) $inventory->id
            );

            $inventory->update(['qr_code_report' => $path]);

            $generated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Selesai! {$generated} QR Report berhasil digenerate dari total {$inventories->count()} barang yang diproses.");

        return self::SUCCESS;
    }
}
