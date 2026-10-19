<?php

namespace App\Services\Purchase;

use App\Support\PerPage;
use App\Models\ApprovalRequest;
use App\Models\Contact;
use App\Models\Inventory;
use App\Models\ProjectFinanceItem;
use App\Support\FinanceCategory;
use App\Support\FinanceLock;
use App\Models\Purchase;
use App\Models\PurchaseAttachment;
use App\Models\PurchaseItem;
use App\Models\PurchaseStatusLog;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\Approval\ApprovalService;
use App\Services\Inventory\InventoryService;
use App\Support\Money;
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
        protected InventoryService $inventoryService,
        protected PurchaseNotifier $notifier
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
            ->when(!empty($filters['due']), fn ($q) => $q->whereIn('status', Purchase::VALID_STATUSES)
                ->where('payment_status', Purchase::PAYMENT_UNPAID)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<=', now()->addDays(Purchase::DUE_SOON_DAYS)))
            ->when(!empty($filters['from']), fn ($q) => $q->whereDate('purchase_date', '>=', $filters['from']))
            ->when(!empty($filters['to']), fn ($q) => $q->whereDate('purchase_date', '<=', $filters['to']))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(PerPage::resolve($perPage))
            ->withQueryString();
    }

    public function getStats(): array
    {
        return [
            'month_total'      => (float) Purchase::whereIn('status', Purchase::VALID_STATUSES)
                ->whereBetween('purchase_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('total'),
            'pending_approval' => Purchase::where('status', Purchase::STATUS_SUBMITTED)->count(),
            'awaiting_receipt' => Purchase::whereIn('status', [Purchase::STATUS_APPROVED, Purchase::STATUS_ORDERED, Purchase::STATUS_PARTIAL])->count(),
            'unpaid_total'     => (float) Purchase::whereIn('status', Purchase::VALID_STATUSES)
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
                'code'             => $this->generateCode(),
                'vendor_id'        => $vendor->id,
                'vendor_name'      => $vendor->name,
                'project_id'       => $data['project_id'] ?? null,
                'shopping_channel' => $data['shopping_channel'] ?? Purchase::SHOP_VENDOR,
                'payment_timing'   => $data['payment_timing'] ?? Purchase::TIMING_ON_RECEIPT,
                'due_date'         => $this->dueDate($data),
                'purchase_date'    => $data['purchase_date'],
                'total'            => $items->sum('subtotal'),
                'status'           => Purchase::STATUS_DRAFT,
                'payment_status'   => Purchase::PAYMENT_UNPAID,
                'notes'            => $data['notes'] ?? null,
                'attachment'       => $attachment?->store('purchases', 'local'),
                'created_by'       => Auth::id(),
            ]);

            $purchase->items()->createMany($items->all());

            $this->statusLog($purchase, 'Dibuat');
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
                $this->deleteFile($purchase->attachment);
                $attachmentPath = $attachment?->store('purchases', 'local');
            }

            $purchase->update([
                'vendor_id'        => $vendor->id,
                'vendor_name'      => $vendor->name,
                'project_id'       => $data['project_id'] ?? null,
                'shopping_channel' => $data['shopping_channel'] ?? $purchase->shopping_channel,
                'payment_timing'   => $data['payment_timing'] ?? $purchase->payment_timing,
                'due_date'         => $this->dueDate($data + ['payment_timing' => $data['payment_timing'] ?? $purchase->payment_timing]),
                'purchase_date'    => $data['purchase_date'],
                'total'            => $items->sum('subtotal'),
                'notes'            => $data['notes'] ?? null,
                'attachment'       => $attachmentPath,
            ]);

            $purchase->items()->delete();
            $purchase->items()->createMany($items->all());

            $this->statusLog($purchase, 'Diubah');
            $this->log("Mengubah pembelian {$purchase->code}");

            return $purchase;
        });
    }

    /**
     * Ajukan ke Approval - persetujuan dan penolakan dieksekusi lewat
     * markApproved()/markRejected() yang dipanggil ApprovalService.
     * Pembelian Ditolak boleh diajukan lagi (kode PO tetap, hitungan pengajuan naik).
     */
    public function submit(Purchase $purchase): ApprovalRequest
    {
        if (!$purchase->isEditable()) {
            throw new Exception('Hanya pembelian berstatus Draft atau Ditolak yang bisa diajukan.');
        }

        if ($purchase->items()->doesntExist()) {
            throw new Exception('Pembelian belum punya item.');
        }

        $request = DB::transaction(function () use ($purchase) {
            $purchase->update([
                'status'       => Purchase::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'submit_count' => $purchase->submit_count + 1,
            ]);

            $request = $this->approvalService->submit(
                'purchase_approve',
                $purchase,
                Str::limit((string) $purchase->notes, 250, '') ?: null,
                [
                    'code'         => $purchase->code,
                    'vendor'       => $purchase->vendor_name,
                    'total'        => (float) $purchase->total,
                    'resubmission' => $purchase->submit_count > 1 ? $purchase->submit_count : null,
                ]
            );

            $this->statusLog($purchase, 'Diajukan', $purchase->submit_count > 1 ? "Pengajuan ulang ke-{$purchase->submit_count}" : null);
            $this->log("Mengajukan pembelian {$purchase->code} untuk approval");

            return $request;
        });

        $this->notifier->submitted($purchase);

        return $request;
    }

    /**
     * Hanya dipanggil ApprovalService::approve().
     */
    public function markApproved(Purchase $purchase, ?string $note = null): void
    {
        if ($purchase->status !== Purchase::STATUS_SUBMITTED) {
            throw new Exception("Pembelian {$purchase->code} tidak sedang menunggu approval.");
        }

        // Pemisahan tugas: pembuat tidak menyetujui pembelian miliknya sendiri (Super Admin dikecualikan).
        if ($purchase->created_by === Auth::id() && !Auth::user()->isSuperAdmin()) {
            throw new Exception('Anda tidak bisa menyetujui pembelian yang Anda buat sendiri.');
        }

        $changes = ['status' => Purchase::STATUS_APPROVED, 'approved_at' => now()];

        // Bayar dulu: dana langsung diminta supaya bos dan finance tahu uangnya perlu disiapkan.
        if ($purchase->payment_timing === Purchase::TIMING_UPFRONT) {
            $changes += [
                'fund_status'       => Purchase::FUND_REQUESTED,
                'fund_requested_at' => now(),
                'fund_amount'       => $purchase->total,
            ];
        }

        $purchase->update($changes);

        $this->statusLog($purchase, 'Disetujui', $note);
        if (isset($changes['fund_status'])) {
            $this->statusLog($purchase, 'Dana diminta', 'Nominal ' . Money::formatRupiah($purchase->total));
        }
        $this->log("Pembelian {$purchase->code} disetujui");

        // Memicu popup "Langkah berikutnya" bila persetujuan dilakukan dari halaman detail.
        session()->flash('purchase_approved_id', $purchase->id);

        $this->notifier->approved($purchase);
    }

    /**
     * Hanya dipanggil ApprovalService::reject().
     */
    public function markRejected(Purchase $purchase, ?string $note = null): void
    {
        if ($purchase->status !== Purchase::STATUS_SUBMITTED) {
            throw new Exception("Pembelian {$purchase->code} tidak sedang menunggu approval.");
        }

        $purchase->update(['status' => Purchase::STATUS_REJECTED]);

        $this->statusLog($purchase, 'Ditolak', $note);
        $this->log("Pembelian {$purchase->code} ditolak");

        $this->notifier->rejected($purchase, $note);
    }

    /**
     * Tandai sudah dipesan (dari Disetujui), atau perbarui info pesanan (resi, estimasi tiba)
     * selama masih Dipesan. Toko online wajib punya nomor pesanan.
     */
    public function order(Purchase $purchase, array $data, ?UploadedFile $proof = null): Purchase
    {
        $first = $purchase->canOrder();

        if (!$first && !$purchase->canEditOrder()) {
            throw new Exception('Hanya pembelian yang sudah disetujui yang bisa ditandai dipesan.');
        }

        DB::transaction(function () use ($purchase, $data, $proof, $first) {
            $online = $purchase->isOnline();

            $changes = [
                'expected_arrival' => $data['expected_arrival'] ?? null,
                'order_notes'      => $data['order_notes'] ?? null,
                'shop_name'        => $online ? ($data['shop_name'] ?? null) : null,
                'order_number'     => $online ? ($data['order_number'] ?? null) : null,
                'order_url'        => $online ? ($data['order_url'] ?? null) : null,
                'courier'          => $data['courier'] ?? null,
                'tracking_number'  => $data['tracking_number'] ?? null,
            ];

            if ($first) {
                $changes += [
                    'status'     => Purchase::STATUS_ORDERED,
                    'ordered_at' => $data['ordered_at'] ?? now(),
                    'ordered_by' => Auth::id(),
                ];
            }

            $purchase->update($changes);

            if ($proof) {
                $this->storeAttachment($purchase, PurchaseAttachment::STAGE_ORDER, $proof);
            }

            $this->statusLog($purchase, $first ? 'Dipesan' : 'Info pesanan diperbarui', $purchase->order_notes);
            $this->log(($first ? 'Menandai dipesan' : 'Memperbarui info pesanan') . " pembelian {$purchase->code}");
        });

        if ($first) {
            $this->notifier->ordered($purchase);
        }

        return $purchase;
    }

    /**
     * Bos mencatat transfer dana ke finance. Ini perpindahan uang internal, bukan pengeluaran,
     * jadi tidak masuk buku kas dan tidak terkena kunci pembukuan.
     */
    public function recordFundTransfer(Purchase $purchase, array $data, ?UploadedFile $proof = null): Purchase
    {
        if (!$purchase->canRecordFund()) {
            throw new Exception('Transfer dana ke finance tidak bisa dicatat untuk pembelian ini.');
        }

        DB::transaction(function () use ($purchase, $data, $proof) {
            $amount = (float) $data['amount'];

            $purchase->update([
                'fund_status'         => Purchase::FUND_AT_FINANCE,
                'fund_amount'         => $amount,
                'fund_transferred_on' => $data['transferred_on'],
                'fund_transferred_by' => Auth::id(),
                'fund_note'           => $data['note'] ?? null,
            ]);

            if ($proof) {
                $this->storeAttachment($purchase, PurchaseAttachment::STAGE_FUND, $proof);
            }

            $this->statusLog($purchase, 'Dana ditransfer ke finance', Money::formatRupiah($amount) . ($purchase->fund_note ? ' - ' . $purchase->fund_note : ''));
            $this->log("Mencatat transfer dana ke finance untuk pembelian {$purchase->code}");
        });

        $this->notifier->fundTransferred($purchase);

        return $purchase;
    }

    /**
     * Terima barang, boleh bertahap. $quantities = jumlah diterima sekarang per item (kosong = semua sisa),
     * $serials = serial number barang baru yang baru pertama kali masuk, $prices = harga aktual per item
     * (hanya berlaku selama belum dibayar). Status jadi Diterima Sebagian sampai semua item lengkap.
     *
     * @param  array<int|string, int|string>  $quantities
     * @param  array<int|string, string>  $serials
     * @param  array<int|string, int|float|string>  $prices
     */
    public function receive(Purchase $purchase, array $quantities = [], array $serials = [], array $prices = []): Purchase
    {
        if (!$purchase->canReceive()) {
            throw new Exception('Hanya pembelian yang sudah disetujui atau dipesan yang bisa ditandai diterima.');
        }

        $complete = false;

        DB::transaction(function () use ($purchase, $quantities, $serials, $prices, &$complete) {
            $purchase->load('items');
            $oldTotal = (float) $purchase->total;
            $unpaid = $purchase->payment_status === Purchase::PAYMENT_UNPAID;
            $received = [];
            $priceNotes = [];

            foreach ($purchase->items as $item) {
                $now = (int) ($quantities[$item->id] ?? $item->remaining());

                if ($now < 0 || $now > $item->remaining()) {
                    throw new Exception("Jumlah \"{$item->name}\" yang diterima melebihi sisa pesanan ({$item->remaining()}).");
                }

                if ($now > 0) {
                    match ($item->stock_mode) {
                        PurchaseItem::STOCK_EXISTING => $this->inventoryService->addStock(
                            $this->inventoryFor($item),
                            $now,
                            "Pembelian {$purchase->code}"
                        ),
                        PurchaseItem::STOCK_NEW      => $this->receiveAsNew($purchase, $item, $now, $serials[$item->id] ?? null),
                        default                      => null,
                    };

                    $item->qty_received += $now;
                    $received[] = "{$item->name} {$now}";
                }

                $newPrice = $prices[$item->id] ?? null;
                if ($unpaid && $newPrice !== null && $newPrice !== '' && round((float) $newPrice, 2) !== round((float) $item->unit_price, 2)) {
                    $priceNotes[] = "{$item->name}: " . Money::formatRupiah($item->unit_price) . ' menjadi ' . Money::formatRupiah($newPrice);
                    $item->po_unit_price = $item->po_unit_price ?? $item->unit_price;
                    $item->unit_price = round((float) $newPrice, 2);
                    $item->subtotal = round($item->qty * $item->unit_price, 2);
                }

                $item->save();
            }

            if ($received === []) {
                throw new Exception('Isi jumlah barang yang diterima, minimal satu barang.');
            }

            $complete = $purchase->items->every(fn (PurchaseItem $item) => $item->qty_received >= $item->qty);
            $newTotal = $this->syncTotals($purchase);

            $purchase->update([
                'status'      => $complete ? Purchase::STATUS_RECEIVED : Purchase::STATUS_PARTIAL,
                'received_at' => now(),
                'received_by' => Auth::id(),
            ]);

            $note = implode(', ', $received);
            if ($priceNotes !== []) {
                $note .= '. Harga berubah: ' . implode('; ', $priceNotes) . '. Total ' . Money::formatRupiah($oldTotal) . ' menjadi ' . Money::formatRupiah($newTotal);
            }

            $this->statusLog($purchase, $complete ? 'Diterima' : 'Diterima sebagian', $note);
            $this->log(($complete ? 'Pembelian' : 'Penerimaan sebagian pembelian') . " {$purchase->code} diterima");
        });

        $this->notifier->received($purchase, $complete);

        return $purchase;
    }

    public function recordPayment(Purchase $purchase, array $data, ?UploadedFile $proof = null): Purchase
    {
        if (!$purchase->canPay()) {
            throw new Exception('Pembelian ini belum bisa dicatat pembayarannya.');
        }

        FinanceLock::assertOpen($data['paid_at']);

        DB::transaction(function () use ($purchase, $data, $proof) {
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

            if ($proof) {
                $this->storeAttachment($purchase, PurchaseAttachment::STAGE_PAYMENT, $proof);
            }

            $this->statusLog($purchase, 'Dibayar', Money::formatRupiah($purchase->total) . ($purchase->payment_method ? " via {$purchase->payment_method}" : ''));
            $this->log("Mencatat pembayaran pembelian {$purchase->code}");
        });

        $this->notifier->paid($purchase);

        return $purchase;
    }

    /**
     * Batalkan pembelian. Yang sudah disetujui wajib beralasan; bila sudah dibayar, uang kembali
     * dari vendor dicatat saat itu juga supaya buku kas tetap seimbang.
     */
    public function cancel(Purchase $purchase, ?string $reason = null, ?float $refund = null, ?string $refundedOn = null): Purchase
    {
        if (!$purchase->canCancel()) {
            throw new Exception('Pembelian ini tidak bisa dibatalkan.');
        }

        $reason = trim((string) $reason);
        $needsReason = in_array($purchase->status, [Purchase::STATUS_APPROVED, Purchase::STATUS_ORDERED], true);
        $paid = $purchase->payment_status === Purchase::PAYMENT_PAID;

        if ($needsReason && $reason === '') {
            throw new Exception('Alasan pembatalan wajib diisi.');
        }

        if ($paid && (float) $refund > 0) {
            if (!$refundedOn) {
                throw new Exception('Tanggal uang kembali wajib diisi.');
            }

            FinanceLock::assertOpen($refundedOn);
        }

        $wasOrdered = $purchase->status === Purchase::STATUS_ORDERED;
        $fundBack = !$paid && $purchase->fund_status === Purchase::FUND_AT_FINANCE
            ? 'Dana ' . Money::formatRupiah($purchase->fund_amount) . ' sudah di finance dan perlu dikembalikan ke bos.'
            : null;

        DB::transaction(function () use ($purchase, $reason, $paid, $refund, $refundedOn, $fundBack) {
            $purchase->update(['status' => Purchase::STATUS_CANCELLED]);

            $note = $reason;
            if ($paid && (float) $refund > 0) {
                $this->recordRefund($purchase, (float) $refund, $refundedOn, "Pembatalan {$purchase->code}");
                $note .= ($note !== '' ? '. ' : '') . 'Uang kembali ' . Money::formatRupiah($refund);
            } elseif ($paid) {
                $note .= ($note !== '' ? '. ' : '') . 'Sudah dibayar, uang kembali belum dicatat';
            }

            if ($fundBack) {
                $note .= ($note !== '' ? '. ' : '') . $fundBack;
            }

            $this->statusLog($purchase, 'Dibatalkan', $note !== '' ? $note : null);
            $this->log("Membatalkan pembelian {$purchase->code}");
        });

        if ($wasOrdered || $paid || $fundBack) {
            $this->notifier->cancelled($purchase, trim($reason . ' ' . $fundBack));
        }

        return $purchase;
    }

    /**
     * Retur barang yang sudah datang ke vendor: stok berkurang, total pembelian ikut turun,
     * dan bila pembelian sudah dibayar, uang kembali dicatat sebagai pemasukan di buku kas.
     *
     * @param  array<int|string, int|string>  $quantities  jumlah retur per item
     */
    public function returnItems(Purchase $purchase, array $quantities, string $reason, ?float $refund = null, ?string $refundedOn = null): Purchase
    {
        $purchase->loadMissing('items');

        if (!$purchase->canReturn()) {
            throw new Exception('Pembelian ini tidak punya barang yang bisa diretur.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new Exception('Alasan retur wajib diisi.');
        }

        $paid = $purchase->payment_status === Purchase::PAYMENT_PAID;
        $refund = $paid ? (float) $refund : 0.0;

        if ($refund > 0) {
            if (!$refundedOn) {
                throw new Exception('Tanggal uang kembali wajib diisi.');
            }

            FinanceLock::assertOpen($refundedOn);
        }

        $summary = '';

        DB::transaction(function () use ($purchase, $quantities, $reason, $refund, $refundedOn, &$summary) {
            $oldTotal = (float) $purchase->total;
            $returned = [];

            foreach ($purchase->items as $item) {
                $qty = (int) ($quantities[$item->id] ?? 0);

                if ($qty < 0 || $qty > $item->returnable()) {
                    throw new Exception("Jumlah retur \"{$item->name}\" melebihi barang yang sudah diterima ({$item->returnable()}).");
                }

                if ($qty === 0) {
                    continue;
                }

                if ($item->inventory_id && $item->stock_mode !== PurchaseItem::STOCK_NONE) {
                    $this->inventoryService->reduceStock($this->inventoryFor($item), $qty, "Retur pembelian {$purchase->code}");
                }

                $item->update(['qty_returned' => $item->qty_returned + $qty]);
                $returned[] = "{$item->name} {$qty}";
            }

            if ($returned === []) {
                throw new Exception('Isi jumlah barang yang diretur, minimal satu barang.');
            }

            $newTotal = $this->syncTotals($purchase);

            $note = implode(', ', $returned) . ". Alasan: {$reason}";
            if ($newTotal !== $oldTotal) {
                $note .= '. Total ' . Money::formatRupiah($oldTotal) . ' menjadi ' . Money::formatRupiah($newTotal);
            }

            if ($refund > 0) {
                $this->recordRefund($purchase, $refund, $refundedOn, "Retur {$purchase->code}");
                $note .= '. Uang kembali ' . Money::formatRupiah($refund);
            }

            $this->statusLog($purchase, 'Retur barang', $note);
            $this->log("Retur barang pembelian {$purchase->code}");

            $summary = implode(', ', $returned);
        });

        $this->notifier->returned($purchase, $summary);

        return $purchase;
    }

    public function delete(Purchase $purchase): void
    {
        if (!$purchase->canDelete()) {
            throw new Exception('Hanya pembelian berstatus Draft yang bisa dihapus.');
        }

        $code = $purchase->code;

        DB::transaction(function () use ($purchase) {
            $this->deleteFile($purchase->attachment);
            $purchase->attachments->each(fn (PurchaseAttachment $file) => $this->deleteFile($file->path));
            $purchase->delete();
        });

        $this->log("Menghapus draft pembelian {$code}");
    }

    /** Unggah lampiran tambahan (nota, resi, lainnya) ke pembelian yang sudah berjalan. */
    public function addAttachment(Purchase $purchase, string $stage, UploadedFile $file): PurchaseAttachment
    {
        $attachment = $this->storeAttachment($purchase, $stage, $file);

        $this->statusLog($purchase, 'Lampiran ditambahkan', $attachment->stageLabel() . ': ' . $attachment->original_name);
        $this->log("Menambah lampiran {$attachment->stageLabel()} pada pembelian {$purchase->code}");

        return $attachment;
    }

    public function removeAttachment(PurchaseAttachment $attachment): void
    {
        $purchase = $attachment->purchase;

        $this->deleteFile($attachment->path);
        $attachment->delete();

        $this->statusLog($purchase, 'Lampiran dihapus', $attachment->stageLabel() . ': ' . $attachment->original_name);
        $this->log("Menghapus lampiran {$attachment->stageLabel()} pada pembelian {$purchase->code}");
    }

    /**
     * Barang baru didaftarkan di penerimaan pertama (serial number wajib). Penerimaan berikutnya
     * untuk item yang sama cukup menambah stok barang yang sudah terdaftar itu.
     */
    private function receiveAsNew(Purchase $purchase, PurchaseItem $item, int $qty, ?string $serial): void
    {
        if ($item->inventory_id) {
            $this->inventoryService->addStock($this->inventoryFor($item), $qty, "Pembelian {$purchase->code}");

            return;
        }

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
            'quantity_total' => $qty,
            'mutation_note'  => "Pembelian {$purchase->code}",
        ]);

        $item->inventory_id = $inventory->id;
    }

    private function inventoryFor(PurchaseItem $item): Inventory
    {
        $inventory = $item->inventory_id ? Inventory::find($item->inventory_id) : null;

        if (!$inventory) {
            throw new Exception("Barang \"{$item->name}\" sudah tidak ada di Inventory. Hubungi admin untuk memeriksanya.");
        }

        return $inventory;
    }

    /**
     * Total pembelian = nilai item yang masih berlaku (jumlah dikurangi retur, dikali harga sekarang).
     * Dana yang masih berstatus diminta ikut disesuaikan supaya nominalnya tidak basi.
     */
    private function syncTotals(Purchase $purchase): float
    {
        $purchase->load('items');
        $total = round((float) $purchase->items->sum(fn (PurchaseItem $item) => $item->netValue()), 2);

        $changes = ['total' => $total];
        if ($purchase->fund_status === Purchase::FUND_REQUESTED) {
            $changes['fund_amount'] = $total;
        }

        $purchase->update($changes);

        return $total;
    }

    /** Uang kembali dari vendor masuk buku kas sebagai pemasukan otomatis. */
    private function recordRefund(Purchase $purchase, float $amount, string $date, string $description): void
    {
        ProjectFinanceItem::create([
            'project_id'  => $purchase->project_id,
            'type'        => 'income',
            'amount'      => $amount,
            'description' => "{$description} - {$purchase->vendor_name}",
            'tanggal'     => $date,
            'category'    => FinanceCategory::PURCHASE_REFUND,
            'contact_id'  => $purchase->vendor_id,
            'source_type' => ProjectFinanceItem::SOURCE_PURCHASE,
            'source_id'   => $purchase->id,
            'created_by'  => Auth::id(),
        ]);
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

    /** Jatuh tempo hanya berlaku untuk pembayaran tempo. */
    private function dueDate(array $data): ?string
    {
        return ($data['payment_timing'] ?? null) === Purchase::TIMING_TERM ? ($data['due_date'] ?? null) : null;
    }

    private function storeAttachment(Purchase $purchase, string $stage, UploadedFile $file): PurchaseAttachment
    {
        return $purchase->attachments()->create([
            'stage'         => $stage,
            'path'          => $file->store("purchases/{$purchase->id}", 'local'),
            'original_name' => Str::limit($file->getClientOriginalName(), 150, ''),
            'uploaded_by'   => Auth::id(),
        ]);
    }

    private function statusLog(Purchase $purchase, string $action, ?string $note = null): void
    {
        PurchaseStatusLog::create([
            'purchase_id' => $purchase->id,
            'action'      => $action,
            'to_status'   => $purchase->status,
            'note'        => $note,
            'user_id'     => Auth::id(),
        ]);
    }

    private function generateCode(): string
    {
        $year = now()->format('Y');

        $last = Purchase::where('code', 'like', "PO-{$year}-%")
            ->orderByDesc('id')
            ->value('code');

        return sprintf('PO-%s-%04d', $year, $last ? ((int) substr($last, -4) + 1) : 1);
    }

    private function deleteFile(?string $path): void
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
