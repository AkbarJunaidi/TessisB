<?php

namespace App\Services\Purchase;

use App\Models\ApprovalRequest;
use App\Models\Contact;
use App\Models\Inventory;
use App\Models\ProjectFinanceItem;
use App\Support\FinanceCategory;
use App\Support\FinanceLock;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\Approval\ApprovalService;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class PurchaseService
{
    private const LOG_MODULE = 'Pembelian';

    public function __construct(
        protected ActivityLogService $activityLogService,
        protected ApprovalService $approvalService,
        protected InventoryService $inventoryService
    ) {
    }

    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Purchase::with(['project:id,name', 'creator:id,name'])
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $keyword = trim($filters['search']);
                $q->where(fn ($w) => $w->where('code', 'like', "%{$keyword}%")
                    ->orWhere('vendor_name', 'like', "%{$keyword}%"));
            })
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['payment_status']), fn ($q) => $q->where('payment_status', $filters['payment_status']))
            ->when(!empty($filters['from']), fn ($q) => $q->whereDate('purchase_date', '>=', $filters['from']))
            ->when(!empty($filters['to']), fn ($q) => $q->whereDate('purchase_date', '<=', $filters['to']))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getStats(): array
    {
        $valid = [Purchase::STATUS_APPROVED, Purchase::STATUS_RECEIVED];

        return [
            'month_total'      => (float) Purchase::whereIn('status', $valid)
                ->whereBetween('purchase_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('total'),
            'pending_approval' => Purchase::where('status', Purchase::STATUS_SUBMITTED)->count(),
            'awaiting_receipt' => Purchase::where('status', Purchase::STATUS_APPROVED)->count(),
            'unpaid_total'     => (float) Purchase::whereIn('status', $valid)
                ->where('payment_status', Purchase::PAYMENT_UNPAID)
                ->sum('total'),
        ];
    }

    public function create(array $data, ?UploadedFile $attachment = null): Purchase
    {
        return DB::transaction(function () use ($data, $attachment) {
            $vendor = Contact::findOrFail($data['vendor_id']);
            $items = $this->normalizeItems($data['items']);

            $purchase = Purchase::create([
                'code'           => $this->generateCode(),
                'vendor_id'      => $vendor->id,
                'vendor_name'    => $vendor->name,
                'project_id'     => $data['project_id'] ?? null,
                'purchase_date'  => $data['purchase_date'],
                'total'          => $items->sum('subtotal'),
                'status'         => Purchase::STATUS_DRAFT,
                'payment_status' => Purchase::PAYMENT_UNPAID,
                'notes'          => $data['notes'] ?? null,
                'attachment'     => $attachment?->store('purchases', 'local'),
                'created_by'     => Auth::id(),
            ]);

            $purchase->items()->createMany($items->all());

            $this->log("Membuat pembelian {$purchase->code} dari \"{$vendor->name}\"");

            return $purchase;
        });
    }

    public function update(Purchase $purchase, array $data, ?UploadedFile $attachment = null): Purchase
    {
        if (!$purchase->isEditable()) {
            throw new Exception('Pembelian ini sudah tidak bisa diubah.');
        }

        return DB::transaction(function () use ($purchase, $data, $attachment) {
            $vendor = Contact::findOrFail($data['vendor_id']);
            $items = $this->normalizeItems($data['items']);

            $attachmentPath = $purchase->attachment;
            if ($attachment || !empty($data['remove_attachment'])) {
                $this->deleteAttachment($purchase->attachment);
                $attachmentPath = $attachment?->store('purchases', 'local');
            }

            $purchase->update([
                'vendor_id'     => $vendor->id,
                'vendor_name'   => $vendor->name,
                'project_id'    => $data['project_id'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'total'         => $items->sum('subtotal'),
                'notes'         => $data['notes'] ?? null,
                'attachment'    => $attachmentPath,
            ]);

            $purchase->items()->delete();
            $purchase->items()->createMany($items->all());

            $this->log("Mengubah pembelian {$purchase->code}");

            return $purchase;
        });
    }

    /**
     * Ajukan ke Approval - persetujuan dan penolakan dieksekusi lewat
     * markApproved()/markRejected() yang dipanggil ApprovalService.
     */
    public function submit(Purchase $purchase): ApprovalRequest
    {
        if (!$purchase->isEditable()) {
            throw new Exception('Hanya pembelian berstatus Draft atau Ditolak yang bisa diajukan.');
        }

        if ($purchase->items()->doesntExist()) {
            throw new Exception('Pembelian belum punya item.');
        }

        return DB::transaction(function () use ($purchase) {
            $purchase->update([
                'status'       => Purchase::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            $request = $this->approvalService->submit(
                'purchase_approve',
                $purchase,
                Str::limit((string) $purchase->notes, 250, '') ?: null,
                [
                    'code'   => $purchase->code,
                    'vendor' => $purchase->vendor_name,
                    'total'  => (float) $purchase->total,
                ]
            );

            $this->log("Mengajukan pembelian {$purchase->code} untuk approval");

            return $request;
        });
    }

    /**
     * Hanya dipanggil ApprovalService::approve().
     */
    public function markApproved(Purchase $purchase): void
    {
        if ($purchase->status !== Purchase::STATUS_SUBMITTED) {
            throw new Exception("Pembelian {$purchase->code} tidak sedang menunggu approval.");
        }

        // Pemisahan tugas: pembuat tidak menyetujui pembelian miliknya sendiri (Super Admin dikecualikan).
        if ($purchase->created_by === Auth::id() && !Auth::user()->isSuperAdmin()) {
            throw new Exception('Anda tidak bisa menyetujui pembelian yang Anda buat sendiri.');
        }

        $purchase->update([
            'status'      => Purchase::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $this->log("Pembelian {$purchase->code} disetujui");
    }

    /**
     * Hanya dipanggil ApprovalService::reject().
     */
    public function markRejected(Purchase $purchase): void
    {
        if ($purchase->status !== Purchase::STATUS_SUBMITTED) {
            throw new Exception("Pembelian {$purchase->code} tidak sedang menunggu approval.");
        }

        $purchase->update(['status' => Purchase::STATUS_REJECTED]);

        $this->log("Pembelian {$purchase->code} ditolak");
    }

    /**
     * @param  array<int|string, string>  $serials  serial number per item id, khusus item "barang baru"
     */
    public function receive(Purchase $purchase, array $serials = []): Purchase
    {
        if (!$purchase->canReceive()) {
            throw new Exception('Hanya pembelian yang sudah disetujui yang bisa ditandai diterima.');
        }

        return DB::transaction(function () use ($purchase, $serials) {
            $purchase->load('items');

            foreach ($purchase->items as $item) {
                match ($item->stock_mode) {
                    PurchaseItem::STOCK_EXISTING => $this->receiveIntoExisting($purchase, $item),
                    PurchaseItem::STOCK_NEW      => $this->receiveAsNew($purchase, $item, $serials[$item->id] ?? null),
                    default                      => null,
                };
            }

            $purchase->update([
                'status'      => Purchase::STATUS_RECEIVED,
                'received_at' => now(),
                'received_by' => Auth::id(),
            ]);

            $this->log("Pembelian {$purchase->code} diterima");

            return $purchase;
        });
    }

    public function recordPayment(Purchase $purchase, array $data): Purchase
    {
        if (!$purchase->canPay()) {
            throw new Exception('Pembelian ini belum bisa dicatat pembayarannya.');
        }

        FinanceLock::assertOpen($data['paid_at']);

        return DB::transaction(function () use ($purchase, $data) {
            $purchase->update([
                'payment_status' => Purchase::PAYMENT_PAID,
                'paid_at'        => $data['paid_at'],
                'payment_method' => $data['payment_method'] ?? null,
                'paid_by'        => Auth::id(),
            ]);

            // Pengeluaran otomatis masuk buku kas; terkait project bila pembelian punya project.
            ProjectFinanceItem::create([
                'project_id'     => $purchase->project_id,
                'type'           => 'expense',
                'amount'         => $purchase->total,
                'description'    => "Pembelian {$purchase->code} - {$purchase->vendor_name}",
                'tanggal'        => $data['paid_at'],
                'category'       => FinanceCategory::PURCHASE,
                'contact_id'     => $purchase->vendor_id,
                'payment_method' => $data['payment_method'] ?? null,
                'source_type'    => ProjectFinanceItem::SOURCE_PURCHASE,
                'source_id'      => $purchase->id,
                'created_by'     => Auth::id(),
            ]);

            $this->log("Mencatat pembayaran pembelian {$purchase->code}");

            return $purchase;
        });
    }

    public function cancel(Purchase $purchase): Purchase
    {
        if (!$purchase->canCancel()) {
            throw new Exception('Pembelian ini tidak bisa dibatalkan.');
        }

        $purchase->update(['status' => Purchase::STATUS_CANCELLED]);

        $this->log("Membatalkan pembelian {$purchase->code}");

        return $purchase;
    }

    public function delete(Purchase $purchase): void
    {
        if (!$purchase->canDelete()) {
            throw new Exception('Hanya pembelian berstatus Draft yang bisa dihapus.');
        }

        $code = $purchase->code;

        DB::transaction(function () use ($purchase) {
            $this->deleteAttachment($purchase->attachment);
            $purchase->delete();
        });

        $this->log("Menghapus draft pembelian {$code}");
    }

    private function receiveIntoExisting(Purchase $purchase, PurchaseItem $item): void
    {
        $inventory = Inventory::find($item->inventory_id);

        if (!$inventory) {
            throw new Exception("Barang \"{$item->name}\" sudah tidak ada di Inventory. Batalkan pembelian ini lalu buat ulang.");
        }

        $this->inventoryService->addStock($inventory, $item->qty, "Pembelian {$purchase->code}");
    }

    private function receiveAsNew(Purchase $purchase, PurchaseItem $item, ?string $serial): void
    {
        $serial = trim((string) $serial);

        if ($serial === '') {
            throw new Exception("Serial number untuk \"{$item->name}\" wajib diisi.");
        }

        if (Inventory::withTrashed()->where('serial_number', $serial)->exists()) {
            throw new Exception("Serial number \"{$serial}\" sudah terdaftar di Inventory.");
        }

        $inventory = $this->inventoryService->createInventory([
            'name'           => $item->name,
            'serial_number'  => $serial,
            'brand'          => $item->brand,
            'description'    => "Dibeli lewat {$purchase->code}",
            'status'         => 'Tersedia',
            'quantity_total' => $item->qty,
            'mutation_note'  => "Pembelian {$purchase->code}",
        ]);

        $item->update(['inventory_id' => $inventory->id]);
    }

    /**
     * Subtotal & total selalu dihitung ulang di server, tidak percaya nilai dari form.
     */
    private function normalizeItems(array $rows): Collection
    {
        return collect($rows)->map(function (array $row) {
            $mode = $row['stock_mode'] ?? PurchaseItem::STOCK_NONE;
            $qty = (int) $row['qty'];
            $price = (float) $row['unit_price'];

            return [
                'name'         => trim($row['name']),
                'qty'          => $qty,
                'unit_price'   => $price,
                'subtotal'     => round($qty * $price, 2),
                'stock_mode'   => $mode,
                'inventory_id' => $mode === PurchaseItem::STOCK_EXISTING ? $row['inventory_id'] : null,
                'brand'        => $mode === PurchaseItem::STOCK_NEW ? ($row['brand'] ?? null) : null,
            ];
        })->values();
    }

    private function generateCode(): string
    {
        $year = now()->format('Y');

        $last = Purchase::where('code', 'like', "PO-{$year}-%")
            ->orderByDesc('id')
            ->value('code');

        return sprintf('PO-%s-%04d', $year, $last ? ((int) substr($last, -4) + 1) : 1);
    }

    private function deleteAttachment(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    private function log(string $message): void
    {
        $this->activityLogService->log(Auth::id(), self::LOG_MODULE, $message);
    }
}
