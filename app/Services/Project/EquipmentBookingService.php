<?php

namespace App\Services\Project;

use App\Models\EquipmentBooking;
use App\Models\Inventory;
use App\Models\Project;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class EquipmentBookingService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected InventoryService $inventoryService
    ) {
    }

    /**
     * Simpan/ubah booking 1 barang untuk project ini (upsert, mengikuti
     * unique index project_id+inventory_id di migration). Tidak pernah
     * menolak keras (soft) - $warning dikembalikan ke controller untuk
     * ditampilkan, bukan melempar Exception, karena booking cuma rencana.
     *
     * @return array{booking: EquipmentBooking, warning: ?string}
     */
    public function book(Project $project, int $inventoryId, int $qty): array
    {
        return DB::transaction(function () use ($project, $inventoryId, $qty) {
            $inventory = Inventory::lockForUpdate()->findOrFail($inventoryId);

            $available = $this->inventoryService->qtyAvailableForRange(
                $inventory,
                $project->event_date?->toDateString(),
                $project->event_end_date?->toDateString(),
                $project->id
            );

            $warning = $qty > $available
                ? "Perhatian: \"{$inventory->name}\" kemungkinan sudah dipesan/dipakai project lain di tanggal yang sama (sisa perkiraan {$available} unit)."
                : null;

            $booking = EquipmentBooking::updateOrCreate(
                ['project_id' => $project->id, 'inventory_id' => $inventory->id],
                ['qty' => $qty, 'status' => EquipmentBooking::STATUS_BOOKED, 'created_by' => Auth::id()]
            );

            $this->activityLogService->log(
                Auth::id(),
                'Booking Alat',
                "Booking \"{$inventory->name}\" x{$qty} untuk project \"{$project->name}\""
            );

            return ['booking' => $booking, 'warning' => $warning];
        });
    }

    public function cancel(EquipmentBooking $booking): void
    {
        if (!$booking->isEditable()) {
            throw new Exception('Booking ini sudah tidak bisa dibatalkan (sudah terpenuhi lewat Surat Jalan).');
        }

        $booking->update(['status' => EquipmentBooking::STATUS_CANCELLED]);

        $this->activityLogService->log(
            Auth::id(),
            'Booking Alat',
            "Membatalkan booking \"{$booking->inventory->name}\" project \"{$booking->project->name}\""
        );
    }

    /**
     * Dipanggil SuratJalanService setelah Surat Jalan berhasil dibuat -
     * booking Dipesan project ini untuk barang yang sama otomatis ditandai
     * Terpenuhi (Surat Jalan sudah jadi commitment fisik yang sesungguhnya).
     */
    public function fulfillForProject(int $projectId, array $inventoryIds): void
    {
        if (empty($inventoryIds)) {
            return;
        }

        EquipmentBooking::where('project_id', $projectId)
            ->whereIn('inventory_id', $inventoryIds)
            ->where('status', EquipmentBooking::STATUS_BOOKED)
            ->update(['status' => EquipmentBooking::STATUS_FULFILLED]);
    }
}
