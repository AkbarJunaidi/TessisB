<?php

namespace App\Services;

use App\Models\Inventory;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class InventoryService
{
    /**
     * Service untuk Activity Log.
     */
    protected ActivityLogService $activityLogService;

    /**
     * Constructor.
     */
    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Mengambil seluruh data inventory dengan pagination.
     */
    public function getAllPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Inventory::latest()->paginate($perPage);
    }

    /**
     * Menambahkan data inventory baru.
     */
    public function createInventory(array $data, ?UploadedFile $imageFile = null): Inventory
    {
        $imagePath = null;

        if ($imageFile) {
            $imagePath = $imageFile->store('assets/inventory/images', 'public');
        }

        $inventory = Inventory::create([
            'name'          => $data['name'],
            'serial_number' => $data['serial_number'],
            'image'         => $imagePath,
            'qr_code'       => null,
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'Inventory',
            'Create Inventory'
        );

        return $inventory;
    }

    /**
     * Mengubah data inventory.
     */
    public function updateInventory(
        Inventory $inventory,
        array $data,
        ?UploadedFile $imageFile = null
    ): Inventory {

        if ($imageFile) {

            if ($inventory->image) {
                Storage::disk('public')->delete($inventory->image);
            }

            $inventory->image = $imageFile->store(
                'assets/inventory/images',
                'public'
            );
        }

        $inventory->update([
            'name'          => $data['name'],
            'serial_number' => $data['serial_number'],
            'image'         => $inventory->image,
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'Inventory',
            'Update Inventory'
        );

        return $inventory;
    }

    /**
     * Menghapus inventory (Soft Delete).
     */
    public function deleteInventory(Inventory $inventory): bool
    {
        $deleted = $inventory->delete();

        if ($deleted) {
            $this->activityLogService->log(
                Auth::id(),
                'Inventory',
                'Delete Inventory'
            );
        }

        return $deleted;
    }
}
