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
use App\Models\User;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\Approval\ApprovalService;
use App\Services\Inventory\InventoryService;
use App\Services\Notification\AnnouncementService;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Throwable;

class PurchaseService
{
    private const LOG_MODULE = 'Pembelian';

    public function __construct(
        protected ActivityLogService $activityLogService,
        protected ApprovalService $approvalService,
        protected InventoryService $inventoryService,
        protected AnnouncementService $announcementService
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
            ->when(!empty($filters['due']), fn ($q) => $q->where('payment_timing', Purchase::TIMING_CREDIT)
                ->where('payment_status', Purchase::PAYMENT_UNPAID)
                ->whereIn('status', Purchase::COMMITTED)
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
            'month_total'      => (float) Purchase::whereIn('status', Purchase::COMMITTED)
                ->whereBetween('purchase_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('total'),
            'pending_approval' => Purchase::where('status', Purchase::STATUS_SUBMITTED)->count(),
            'awaiting_receipt' => Purchase::whereIn('status', [Purchase::STATUS_APPROVED, Purchase::STATUS_ORDERED, Purchase::STATUS_PARTIAL])->count(),
            'unpaid_total'     => round((float) Purchase::with('items')
                ->whereIn('status', Purchase::COMMITTED)
                ->where('payment_status', Purchase::PAYMENT_UNPAID)
                ->get()
                ->sum(fn (Purchase $purchase) => $purchase->payableTotal()), 2),
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
                'shopping_type'  => $data['shopping_type'],
                'payment_timing' => $data['payment_timing'],
                'due_date'       => $data['payment_timing'] === Purchase::TIMING_CREDIT ? $data['due_date'] : null,
                'purchase_date'  => $data['purchase_date'],
                'total'          => $items->sum('subtotal'),
                'status'         => Purchase::STATUS_DRAFT,
                'payment_status' => Purchase::PAYMENT_UNPAID,
                'notes'          => $data['notes'] ?? null,
                'attachment'     => $attachment?->store('purchases', 'local'),
                'created_by'     => Auth::id(),
            ]);

            $purchase->items()->createMany($items->all());

            $this->record($purchase, 'created', 'Dibuat');
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
                'shopping_type'  => $data['shopping_type'],
                'payment_timing' => $data['payment_timing'],
                'due_date'       => $data['payment_timing'] === Purchase::TIMING_CREDIT ? $data['due_date'] : null,
                'purchase_date' => $data['purchase_date'],
                'total'         => $items->sum('subtotal'),
                'notes'         => $data['notes'] ?? null,
                'attachment'    => $attachmentPath,
            ]);

            $purchase->items()->delete();
            $purchase->items()->createMany($items->all());

            $this->record($purchase, 'updated', 'Diubah');

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

        if (!$purchase->payment_timing) {
            throw new Exception('Waktu bayar belum diisi. Ubah pembelian lalu lengkapi.');
        }

        return DB::transaction(function () use ($purchase) {
            $isResubmit = $purchase->status === Purchase::STATUS_REJECTED;
            $prevNote = $isResubmit
                ? $purchase->approvalRequests()->where('status', 'rejected')->latest('decided_at')->latest('id')->value('decision_note')
                : null;

            $purchase->update([
                'status'         => Purchase::STATUS_SUBMITTED,
                'submitted_at'   => now(),
                'resubmit_count' => $purchase->resubmit_count + ($isResubmit ? 1 : 0),
            ]);

            $attempt = $purchase->resubmit_count + 1;

            $request = $this->approvalService->submit(
                'purchase_approve',
                $purchase,
                Str::limit((string) $purchase->notes, 250, '') ?: null,
                [
                    'code'      => $purchase->code,
                    'vendor'    => $purchase->vendor_name,
                    'total'     => (float) $purchase->total,
                    'attempt'   => $attempt,
                    'prev_note' => $prevNote,
                ]
            );

            $label = $attempt > 1 ? "Diajukan ulang ke-{$attempt}" : 'Diajukan';
            $this->record($purchase, 'submitted', $label);
            $this->log("Mengajukan pembelian {$purchase->code} untuk approval" . ($attempt > 1 ? " (ke-{$attempt})" : ''));

            $this->notify(
                $this->usersWith('approval', 'decide'),
                $purchase,
                "Pembelian {$purchase->code} menunggu persetujuan",
                "{$purchase->vendor_name} - " . Money::formatRupiah($purchase->total) . ($attempt > 1 ? ". {$label}." : '')
            );

            return $request;
        });
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

        $purchase->update([
            'status'      => Purchase::STATUS_APPROVED,
            'approved_at' => now(),
            'fund_status' => $purchase->isPayFirst() ? Purchase::FUND_REQUESTED : null,
        ]);

        $this->record($purchase, 'approved', 'Disetujui', $note);
        $this->log("Pembelian {$purchase->code} disetujui");

        // Memicu popup "Langkah berikutnya" bila persetujuan dari halaman detail (bukan AJAX).
        if (!request()->expectsJson()) {
            session()->flash('purchase_approved_id', $purchase->id);
        }

        $this->notify(
            [$purchase->created_by],
            $purchase,
            "Pembelian {$purchase->code} disetujui",
            $this->approvalSummary($purchase)
        );

        if ($purchase->isPayFirst()) {
            $this->notify(
                array_diff($this->usersWith('purchase', 'pay'), [$purchase->created_by]),
                $purchase,
                "Dana diminta untuk {$purchase->code}",
                "{$purchase->vendor_name}: dana " . Money::formatRupiah($purchase->total) . ' perlu disiapkan. Pembayaran dilakukan setelah dana tercatat di finance.'
            );
        }
    }

    /**
     * Hanya dipanggil ApprovalService::reject(). Catatan wajib supaya pembuat tahu apa yang harus diperbaiki.
     */
    public function markRejected(Purchase $purchase, ?string $note = null): void
    {
        if ($purchase->status !== Purchase::STATUS_SUBMITTED) {
            throw new Exception("Pembelian {$purchase->code} tidak sedang menunggu approval.");
        }

        $note = trim((string) $note);

        if ($note === '') {
            throw new Exception('Catatan penolakan wajib diisi.');
        }

        $purchase->update(['status' => Purchase::STATUS_REJECTED]);

        $this->record($purchase, 'rejected', 'Ditolak', $note);
        $this->log("Pembelian {$purchase->code} ditolak");

        $this->notify(
            [$purchase->created_by],
            $purchase,
            "Pembelian {$purchase->code} ditolak",
            'Ditolak oleh ' . (Auth::user()->name ?? '-') . ": {$note}"
        );
    }

    /**
     * Tandai sudah dipesan (dari Disetujui) atau perbarui info pesanan selama masih Dipesan,
     * mis. resi yang baru ada belakangan. Toko online wajib punya nomor pesanan.
     */
    public function order(Purchase $purchase, array $data, ?UploadedFile $orderProof = null, ?UploadedFile $receiptFile = null): Purchase
    {
        $first = $purchase->canOrder();

        if (!$first && !$purchase->canEditOrder()) {
            throw new Exception('Hanya pembelian yang sudah disetujui yang bisa ditandai dipesan.');
        }

        $orderNumber = trim((string) ($data['order_number'] ?? ''));

        if ($purchase->isOnline() && $orderNumber === '') {
            throw new Exception('Nomor pesanan wajib diisi untuk belanja toko online.');
        }

        return DB::transaction(function () use ($purchase, $data, $first, $orderNumber, $orderProof, $receiptFile) {
            $this->lockFresh($purchase);

            if ($first ? !$purchase->canOrder() : !$purchase->canEditOrder()) {
                throw new Exception('Status pembelian sudah berubah. Muat ulang halaman.');
            }

            $changes = [
                'expected_at'     => $data['expected_at'] ?? null,
                'store_name'      => $data['store_name'] ?? null,
                'order_number'    => $orderNumber !== '' ? $orderNumber : null,
                'order_url'       => $data['order_url'] ?? null,
                'courier'         => $data['courier'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
            ];

            if ($first) {
                $changes += [
                    'status'     => Purchase::STATUS_ORDERED,
                    'ordered_at' => empty($data['ordered_at']) ? now() : Carbon::parse($data['ordered_at'])->setTimeFrom(now()),
                    'ordered_by' => Auth::id(),
                ];
            }

            $purchase->update($changes);

            if ($orderProof) {
                $this->storeAttachment($purchase, $orderProof, PurchaseAttachment::STAGE_ORDER);
            }

            if ($receiptFile) {
                $this->storeAttachment($purchase, $receiptFile, PurchaseAttachment::STAGE_RECEIPT);
            }

            $details = array_filter([
                $purchase->expected_at ? 'Estimasi tiba ' . $purchase->expected_at->translatedFormat('d M Y') : null,
                $orderNumber !== '' ? "No. pesanan {$orderNumber}" : null,
                !empty($data['courier']) ? 'Ekspedisi ' . $data['courier'] : null,
                !empty($data['tracking_number']) ? 'Resi ' . $data['tracking_number'] : null,
                $data['note'] ?? null,
            ]);

            $this->record($purchase, $first ? 'ordered' : 'order_updated', $first ? 'Dipesan' : 'Info pesanan diperbarui', implode('. ', $details) ?: null);
            $this->log(($first ? 'Menandai pembelian' : 'Memperbarui info pesanan pembelian') . " {$purchase->code}" . ($first ? ' dipesan' : ''));

            if ($first) {
                $this->notify(
                    array_unique(array_merge([$purchase->created_by], $this->usersWith('purchase', 'pay'))),
                    $purchase,
                    "Pembelian {$purchase->code} sudah dipesan",
                    "{$purchase->vendor_name}. " . implode('. ', $details)
                );
            }

            return $purchase;
        });
    }

    /**
     * Penyetuju (bos) mencatat transfer dana ke finance. Bukan pengeluaran buku kas:
     * pengeluaran baru tercatat saat finance membayar vendor.
     */
    public function fund(Purchase $purchase, array $data, UploadedFile $proof): Purchase
    {
        if (!$purchase->canFund()) {
            throw new Exception('Pembelian ini tidak sedang meminta dana.');
        }

        return DB::transaction(function () use ($purchase, $data, $proof) {
            $this->lockFresh($purchase);

            if (!$purchase->canFund()) {
                throw new Exception('Pembelian ini tidak sedang meminta dana.');
            }

            $purchase->update([
                'fund_status' => Purchase::FUND_AT_FINANCE,
                'fund_amount' => $data['amount'],
                'fund_at'     => $data['fund_at'],
                'fund_by'     => Auth::id(),
                'fund_note'   => $data['note'] ?? null,
            ]);

            $this->storeAttachment($purchase, $proof, PurchaseAttachment::STAGE_FUND);

            $amount = Money::formatRupiah($data['amount']);

            $this->record($purchase, 'fund', 'Dana diserahkan ke finance', $amount . (!empty($data['note']) ? '. ' . $data['note'] : ''));
            $this->log("Mencatat transfer dana {$amount} ke finance untuk pembelian {$purchase->code}");

            $this->notify(
                $this->usersWith('purchase', 'pay'),
                $purchase,
                "Dana siap untuk {$purchase->code}",
                "{$amount} sudah di finance. Bayar ke {$purchase->vendor_name} lalu unggah bukti transfer."
            );

            return $purchase;
        });
    }

    /**
     * Penerimaan bisa bertahap. $lines[itemId] = ['qty' => jumlah diterima, 'serial' => serial barang baru
     * (hanya penerimaan pertama), 'price' => harga aktual per unit bila beda dari PO].
     *
     * @param  array<int|string, array{qty?: int|string|null, serial?: ?string, price?: int|float|string|null}>  $lines
     */
    public function receive(Purchase $purchase, array $lines = []): Purchase
    {
        if (!$purchase->canReceive()) {
            throw new Exception('Hanya pembelian yang sudah dipesan yang bisa ditandai diterima.');
        }

        return DB::transaction(function () use ($purchase, $lines) {
            $this->lockFresh($purchase);

            if (!$purchase->canReceive()) {
                throw new Exception('Hanya pembelian yang sudah dipesan yang bisa ditandai diterima.');
            }

            $purchase->load('items');

            $parts = [];
            $variance = 0.0;
            $serialPending = [];

            foreach ($purchase->items as $item) {
                $line = $lines[$item->id] ?? [];
                $qty = (int) ($line['qty'] ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                if ($qty > $item->remaining_qty) {
                    throw new Exception("Jumlah diterima \"{$item->name}\" melebihi sisa pesanan ({$item->remaining_qty}).");
                }

                $created = match ($item->stock_mode) {
                    PurchaseItem::STOCK_EXISTING => $this->receiveIntoExisting($purchase, $item, $qty),
                    PurchaseItem::STOCK_NEW      => $this->receiveAsNew($purchase, $item, $qty, $line['serial'] ?? null),
                    default                      => null,
                };

                if ($created instanceof Inventory) {
                    $serialPending[] = ['id' => $created->id, 'name' => $item->name];
                }

                $price = ($line['price'] ?? '') === '' ? null : round((float) $line['price'], 2);
                $actual = $price === null
                    ? $item->actual_unit_price
                    : (abs($price - (float) $item->unit_price) < 0.005 ? null : $price);

                $item->update([
                    'received_qty'      => $item->received_qty + $qty,
                    'actual_unit_price' => $actual,
                ]);

                $variance += $qty * ((float) ($actual ?? $item->unit_price) - (float) $item->unit_price);
                $parts[] = "{$item->name} {$qty}" . ($price !== null && $actual !== null ? ' @ ' . Money::formatRupiah($actual) : '');
            }

            if (!$parts) {
                throw new Exception('Isi jumlah barang yang diterima.');
            }

            $complete = $purchase->items->every(fn (PurchaseItem $item) => $item->received_qty >= $item->qty);
            $wasPartial = $purchase->status === Purchase::STATUS_PARTIAL;

            $purchase->update($complete
                ? ['status' => Purchase::STATUS_RECEIVED, 'received_at' => now(), 'received_by' => Auth::id()]
                : ['status' => Purchase::STATUS_PARTIAL]
            );

            $label = $complete ? ($wasPartial ? 'Diterima (sisa barang)' : 'Diterima') : 'Diterima sebagian';
            $note = implode('; ', $parts) . (abs($variance) >= 0.005 ? '. Selisih harga ' . ($variance > 0 ? '+' : '-') . Money::formatRupiah(abs($variance)) . ' dari PO' : '');

            $this->record($purchase, $complete ? 'received' : 'partial', $label, $note);
            $this->log("Pembelian {$purchase->code} {$label}");

            $this->notify(
                [$purchase->created_by],
                $purchase,
                "Pembelian {$purchase->code} " . ($complete ? 'diterima' : 'diterima sebagian'),
                $complete ? 'Seluruh barang diterima dan stok Inventory diperbarui.' : "{$note}. Sisa barang masih ditunggu."
            );

            if ($serialPending) {
                $names = implode(', ', array_column($serialPending, 'name'));

                // Popup di halaman detail untuk penerima; notifikasi untuk yang boleh mengisi serial di Inventory.
                session()->flash('purchase_serial_pending', $serialPending);

                $this->notify(
                    array_unique(array_merge([$purchase->created_by], $this->usersWith('inventory', 'edit'))),
                    $purchase,
                    "Serial number perlu dilengkapi ({$purchase->code})",
                    "{$names} sudah diterima tanpa serial number. Lengkapi di detail Inventory sebelum mencetak label QR."
                );
            }

            if ($variance > 0.004) {
                $this->notify(
                    $this->usersWith('approval', 'decide'),
                    $purchase,
                    "Harga aktual {$purchase->code} lebih mahal dari PO",
                    'Selisih ' . Money::formatRupiah($variance) . " untuk {$purchase->vendor_name}."
                );
            }

            return $purchase;
        });
    }

    /**
     * Retur barang yang sudah diterima: stok dikurangi, catatan refund masuk riwayat.
     * Refund uang dicatat manual di Keuangan oleh yang berwenang.
     *
     * @param  array<int|string, array{qty?: int|string|null}>  $lines
     */
    public function returnGoods(Purchase $purchase, array $lines, string $note, ?float $refund = null, ?string $refundedOn = null): Purchase
    {
        $purchase->load('items');

        if (!$purchase->canReturn()) {
            throw new Exception('Pembelian ini tidak punya barang yang bisa diretur.');
        }

        $note = trim($note);

        if ($note === '') {
            throw new Exception('Alasan retur dan catatan refund wajib diisi.');
        }

        // Uang kembali hanya relevan bila pembelian sudah dibayar; masuk buku kas sebagai pemasukan.
        $refund = $purchase->payment_status === Purchase::PAYMENT_PAID ? (float) $refund : 0.0;

        if ($refund > 0) {
            if (!$refundedOn) {
                throw new Exception('Tanggal uang kembali wajib diisi.');
            }

            FinanceLock::assertOpen($refundedOn);
        }

        return DB::transaction(function () use ($purchase, $lines, $note, $refund, $refundedOn) {
            $this->lockFresh($purchase);
            $purchase->load('items');

            if (!$purchase->canReturn()) {
                throw new Exception('Pembelian ini tidak punya barang yang bisa diretur.');
            }

            $parts = [];

            foreach ($purchase->items as $item) {
                $qty = (int) ($lines[$item->id]['qty'] ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                if ($qty > $item->returnable_qty) {
                    throw new Exception("Jumlah retur \"{$item->name}\" melebihi barang yang diterima ({$item->returnable_qty}).");
                }

                if ($item->stock_mode !== PurchaseItem::STOCK_NONE && $item->inventory_id) {
                    $inventory = Inventory::find($item->inventory_id);

                    if (!$inventory) {
                        throw new Exception("Barang \"{$item->name}\" sudah tidak ada di Inventory.");
                    }

                    $this->inventoryService->removeStock($inventory, $qty, "Retur pembelian {$purchase->code}");
                }

                $item->update(['returned_qty' => $item->returned_qty + $qty]);
                $parts[] = "{$item->name} x{$qty}";
            }

            if (!$parts) {
                throw new Exception('Isi jumlah barang yang diretur.');
            }

            $detail = implode('; ', $parts) . ". {$note}";

            if ($refund > 0) {
                ProjectFinanceItem::create([
                    'project_id'  => $purchase->project_id,
                    'type'        => 'income',
                    'amount'      => $refund,
                    'description' => "Retur {$purchase->code} - {$purchase->vendor_name}",
                    'tanggal'     => $refundedOn,
                    'category'    => FinanceCategory::PURCHASE_REFUND,
                    'contact_id'  => $purchase->vendor_id,
                    'source_type' => ProjectFinanceItem::SOURCE_PURCHASE,
                    'source_id'   => $purchase->id,
                    'created_by'  => Auth::id(),
                ]);

                $detail .= '. Uang kembali ' . Money::formatRupiah($refund);
            }

            $this->record($purchase, 'returned', 'Retur barang', $detail);
            $this->log("Retur barang pembelian {$purchase->code}");

            $paidNote = $purchase->payment_status === Purchase::PAYMENT_PAID ? ' Pembelian sudah dibayar, catat refund di Keuangan.' : '';

            $this->notify(
                array_unique(array_merge($this->usersWith('purchase', 'pay'), $this->usersWith('approval', 'decide'))),
                $purchase,
                "Retur barang {$purchase->code}",
                $detail . ($refund > 0 ? '' : $paidNote)
            );

            return $purchase;
        });
    }

    /**
     * PDF Purchase Order untuk dikirim ke vendor.
     */
    public function generatePoPdf(Purchase $purchase)
    {
        if (!in_array($purchase->status, Purchase::COMMITTED, true)) {
            throw new Exception('PO baru bisa dicetak setelah pembelian disetujui.');
        }

        $purchase->loadMissing('items', 'vendor', 'creator');

        return Pdf::loadView('purchase.po-pdf', ['purchase' => $purchase])
            ->setPaper('a4', 'portrait')
            ->stream("{$purchase->code}.pdf");
    }

    public function recordPayment(Purchase $purchase, array $data, ?UploadedFile $proof = null): Purchase
    {
        if (!$purchase->canPay()) {
            throw new Exception('Pembelian ini belum bisa dicatat pembayarannya.');
        }

        if (!$purchase->canPayBy(Auth::user())) {
            throw new Exception('Dana belum tercatat di finance. Penyetuju mencatat transfer dana lebih dulu, atau membayar langsung.');
        }

        FinanceLock::assertOpen($data['paid_at']);

        return DB::transaction(function () use ($purchase, $data, $proof) {
            $this->lockFresh($purchase);

            if (!$purchase->canPayBy(Auth::user())) {
                throw new Exception('Pembelian ini sudah dibayar atau belum bisa dibayar.');
            }

            $amount = round((float) ($data['amount'] ?? $purchase->payableTotal()), 2);

            $purchase->update([
                'payment_status' => Purchase::PAYMENT_PAID,
                'paid_at'        => $data['paid_at'],
                'payment_method' => $data['payment_method'] ?? null,
                'paid_amount'    => $amount,
                'paid_by'        => Auth::id(),
            ]);

            if ($proof) {
                $this->storeAttachment($purchase, $proof, PurchaseAttachment::STAGE_PAYMENT);
            }

            // Pengeluaran otomatis masuk buku kas; terkait project bila pembelian punya project.
            ProjectFinanceItem::create([
                'project_id'     => $purchase->project_id,
                'type'           => 'expense',
                'amount'         => $amount,
                'description'    => "Pembelian {$purchase->code} - {$purchase->vendor_name}",
                'tanggal'        => $data['paid_at'],
                'category'       => FinanceCategory::PURCHASE,
                'contact_id'     => $purchase->vendor_id,
                'payment_method' => $data['payment_method'] ?? null,
                'source_type'    => ProjectFinanceItem::SOURCE_PURCHASE,
                'source_id'      => $purchase->id,
                'created_by'     => Auth::id(),
            ]);

            $this->record($purchase, 'paid', 'Dibayar', trim(($data['payment_method'] ?? '') . ' ' . Money::formatRupiah($amount)));
            $this->log("Mencatat pembayaran pembelian {$purchase->code}");

            $this->notify(
                [$purchase->created_by],
                $purchase,
                "Pembelian {$purchase->code} dibayar",
                'Pembayaran ke ' . $purchase->vendor_name . ' selesai' . ($proof ? ', bukti terlampir.' : '.')
            );

            return $purchase;
        });
    }

    public function cancel(Purchase $purchase, ?string $reason = null): Purchase
    {
        if (!$purchase->canCancel()) {
            throw new Exception('Pembelian ini tidak bisa dibatalkan.');
        }

        $reason = trim((string) $reason);

        if ($reason === '' && in_array($purchase->status, [Purchase::STATUS_APPROVED, Purchase::STATUS_ORDERED], true)) {
            throw new Exception('Alasan pembatalan wajib diisi.');
        }

        return DB::transaction(function () use ($purchase, $reason) {
            $this->lockFresh($purchase);

            if (!$purchase->canCancel()) {
                throw new Exception('Pembelian ini tidak bisa dibatalkan.');
            }

            $fundAtFinance = $purchase->fund_status === Purchase::FUND_AT_FINANCE;
            $fundNote = $fundAtFinance ? 'Dana ' . Money::formatRupiah($purchase->fund_amount) . ' sudah di finance dan perlu dikembalikan.' : null;

            $purchase->update(['status' => Purchase::STATUS_CANCELLED]);

            $this->record($purchase, 'cancelled', 'Dibatalkan', trim($reason . ' ' . $fundNote) ?: null);
            $this->log("Membatalkan pembelian {$purchase->code}");

            $this->notify(
                array_unique(array_merge([$purchase->created_by], $fundAtFinance ? $this->usersWith('purchase', 'pay') : [])),
                $purchase,
                "Pembelian {$purchase->code} dibatalkan",
                trim(($reason !== '' ? "Alasan: {$reason}. " : '') . $fundNote)
            );

            return $purchase;
        });
    }

    /**
     * Lampiran manual (nota, bukti pesanan, resi) setelah pembelian disetujui.
     */
    public function addAttachment(Purchase $purchase, UploadedFile $file, string $stage): PurchaseAttachment
    {
        if (!in_array($purchase->status, Purchase::COMMITTED, true)) {
            throw new Exception('Lampiran hanya bisa ditambah setelah pembelian disetujui.');
        }

        if (!in_array($stage, PurchaseAttachment::MANUAL_STAGES, true)) {
            throw new Exception('Jenis lampiran tidak valid.');
        }

        return DB::transaction(function () use ($purchase, $file, $stage) {
            $attachment = $this->storeAttachment($purchase, $file, $stage);

            $this->record($purchase, 'attachment', 'Lampiran ditambahkan', "{$attachment->stage_label}: {$attachment->original_name}");

            return $attachment;
        });
    }

    /**
     * Hapus lampiran manual (nota, bukti pesanan, resi). Bukti transfer dana dan pembayaran tidak bisa dihapus.
     */
    public function removeAttachment(PurchaseAttachment $attachment): void
    {
        $purchase = $attachment->purchase;

        if (!in_array($attachment->stage, PurchaseAttachment::MANUAL_STAGES, true)) {
            throw new Exception('Bukti transfer dana dan pembayaran tidak bisa dihapus.');
        }

        if ($purchase->status === Purchase::STATUS_CANCELLED) {
            throw new Exception('Lampiran pembelian yang dibatalkan tidak bisa dihapus.');
        }

        DB::transaction(function () use ($attachment, $purchase) {
            $name = $attachment->original_name;
            $path = $attachment->path;

            $attachment->delete();
            Storage::disk('local')->delete($path);

            $this->record($purchase, 'attachment', 'Lampiran dihapus', $name);
        });
    }

    /**
     * Ringkasan langkah berikutnya setelah disetujui, ditampilkan sebagai pesan ke penyetuju.
     */
    public function approvalSummary(Purchase $purchase): string
    {
        $goods = $purchase->isOnline()
            ? 'Pembuat membuat pesanan di toko online lalu menandai Dipesan dengan nomor pesanan.'
            : 'Pembuat menghubungi vendor lewat WhatsApp lalu menandai Dipesan.';

        $money = match ($purchase->payment_timing) {
            Purchase::TIMING_PAY_FIRST  => ' Dana ' . Money::formatRupiah($purchase->total) . ' perlu disiapkan dan dicatat ke finance.',
            Purchase::TIMING_ON_RECEIPT => ' Pembayaran dilakukan setelah barang diterima.',
            Purchase::TIMING_CREDIT     => ' Pembayaran sebelum jatuh tempo' . ($purchase->due_date ? ' ' . $purchase->due_date->translatedFormat('d M Y') : '') . '.',
            default                     => '',
        };

        return $goods . $money;
    }

    /**
     * Langkah berikutnya per jalur (barang dan uang) untuk panel di detail pembelian.
     *
     * @return array<int, array{track: string, owner: string, text: string, mine: bool}>
     */
    public function nextSteps(Purchase $purchase, User $user): array
    {
        $creator = $user->hasPermission('purchase', 'edit');
        $steps = [];

        $goods = match ($purchase->status) {
            Purchase::STATUS_DRAFT     => ['Pembuat', 'Lengkapi pembelian lalu ajukan persetujuan.', $creator],
            Purchase::STATUS_SUBMITTED => ['Penyetuju', 'Menunggu keputusan persetujuan.', $user->hasPermission('approval', 'decide')],
            Purchase::STATUS_REJECTED  => ['Pembuat', 'Ubah sesuai catatan penolakan lalu ajukan lagi. Kode PO tetap sama.', $creator],
            Purchase::STATUS_APPROVED  => ['Pembuat', $purchase->isOnline()
                ? 'Buat pesanan di toko online, lalu tandai Dipesan dengan nomor pesanan.'
                : 'Hubungi vendor, lalu tandai Dipesan beserta estimasi tiba.', $creator],
            Purchase::STATUS_ORDERED   => ['Penerima barang', 'Tandai Diterima saat barang tiba'
                . ($purchase->expected_at ? ' (estimasi ' . $purchase->expected_at->translatedFormat('d M Y') . ').' : '.'), $user->hasPermission('purchase', 'receive')],
            Purchase::STATUS_PARTIAL   => ['Penerima barang', 'Tandai penerimaan lagi saat sisa barang tiba.', $user->hasPermission('purchase', 'receive')],
            Purchase::STATUS_RECEIVED  => ['Selesai', 'Barang sudah diterima dan stok masuk.', false],
            default                    => ['Selesai', 'Pembelian dibatalkan.', false],
        };
        $steps[] = ['track' => 'Barang', 'owner' => $goods[0], 'text' => $goods[1], 'mine' => $goods[2]];

        if (in_array($purchase->status, Purchase::COMMITTED, true)) {
            $steps[] = $this->moneyStep($purchase, $user);
        }

        return $steps;
    }

    private function moneyStep(Purchase $purchase, User $user): array
    {
        $pay = $user->hasPermission('purchase', 'pay');
        $decide = $user->hasPermission('approval', 'decide');
        $total = Money::formatRupiah($purchase->total);

        if ($purchase->payment_status === Purchase::PAYMENT_PAID) {
            return ['track' => 'Uang', 'owner' => 'Selesai', 'text' => 'Pembayaran sudah dicatat.', 'mine' => false];
        }

        if ($purchase->canFund()) {
            return ['track' => 'Uang', 'owner' => 'Bos', 'text' => "Siapkan dana {$total} dan catat transfer ke finance, atau bayar langsung ke vendor.", 'mine' => $decide];
        }

        if ($purchase->isPayFirst() && $purchase->fund_status === Purchase::FUND_AT_FINANCE) {
            return ['track' => 'Uang', 'owner' => 'Finance', 'text' => "Dana sudah di finance. Bayar ke vendor {$total} dan unggah bukti.", 'mine' => $pay];
        }

        if ($purchase->payment_timing === Purchase::TIMING_ON_RECEIPT && $purchase->status !== Purchase::STATUS_RECEIVED) {
            return ['track' => 'Uang', 'owner' => 'Finance', 'text' => 'Dibayar setelah barang diterima.', 'mine' => false];
        }

        $due = $purchase->payment_timing === Purchase::TIMING_CREDIT && $purchase->due_date
            ? ' Jatuh tempo ' . $purchase->due_date->translatedFormat('d M Y') . '.'
            : '';

        return ['track' => 'Uang', 'owner' => 'Finance', 'text' => "Catat pembayaran {$total} ke vendor.{$due}", 'mine' => $pay];
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

    private function receiveIntoExisting(Purchase $purchase, PurchaseItem $item, int $qty): void
    {
        $inventory = Inventory::find($item->inventory_id);

        if (!$inventory) {
            throw new Exception("Barang \"{$item->name}\" sudah tidak ada di Inventory. Batalkan pembelian ini lalu buat ulang.");
        }

        $this->inventoryService->addStock($inventory, $qty, "Pembelian {$purchase->code}");
    }

    /**
     * Penerimaan pertama membuat barang baru; penerimaan berikutnya menambah stok barang itu.
     * Serial boleh kosong (penerima belum tentu tahu): dipakai serial sementara dan ditandai
     * serial_pending sampai dilengkapi di detail Inventory. Mengembalikan Inventory yang masih sementara.
     */
    private function receiveAsNew(Purchase $purchase, PurchaseItem $item, int $qty, ?string $serial): ?Inventory
    {
        if ($item->inventory_id) {
            $this->receiveIntoExisting($purchase, $item, $qty);

            return null;
        }

        $serial = trim((string) $serial);
        $pending = $serial === '';

        if ($pending) {
            $base = "TMP-{$purchase->code}-{$item->id}";
            $serial = $base;

            for ($n = 2; Inventory::withTrashed()->where('serial_number', $serial)->exists(); $n++) {
                $serial = "{$base}-{$n}";
            }
        } elseif (Inventory::withTrashed()->where('serial_number', $serial)->exists()) {
            throw new Exception("Serial number \"{$serial}\" sudah terdaftar di Inventory.");
        }

        $inventory = $this->inventoryService->createInventory([
            'name'           => $item->name,
            'serial_number'  => $serial,
            'serial_pending' => $pending,
            'brand'          => $item->brand,
            'description'    => "Dibeli lewat {$purchase->code}",
            'status'         => 'Tersedia',
            'quantity_total' => $qty,
            'mutation_note'  => "Pembelian {$purchase->code}",
        ]);

        $item->update(['inventory_id' => $inventory->id]);

        return $pending ? $inventory : null;
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

    /** Muat status terbaru dengan kunci baris agar klik ganda tidak mencatat dua kali. */
    private function lockFresh(Purchase $purchase): void
    {
        $fresh = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

        $purchase->setRawAttributes($fresh->getAttributes(), true);
        $purchase->unsetRelations()->load('items');
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

    private function storeAttachment(Purchase $purchase, UploadedFile $file, string $stage): PurchaseAttachment
    {
        return $purchase->attachments()->create([
            'stage'         => $stage,
            'path'          => $file->store('purchases', 'local'),
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'uploaded_by'   => Auth::id(),
        ]);
    }

    private function record(Purchase $purchase, string $event, string $label, ?string $note = null): void
    {
        $purchase->histories()->create([
            'user_id' => Auth::id(),
            'event'   => $event,
            'label'   => $label,
            'note'    => $note ? Str::limit($note, 1000, '') : null,
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function usersWith(string $module, string $action): array
    {
        return User::where('status', 'active')->get()
            ->filter(fn (User $user) => $user->hasPermission($module, $action))
            ->pluck('id')
            ->all();
    }

    /**
     * Kirim lewat Pengumuman + Web Push setelah transaksi commit; kegagalan kirim tidak menggagalkan aksi.
     *
     * @param  array<int, int|null>  $userIds
     */
    private function notify(array $userIds, Purchase $purchase, string $title, string $message): void
    {
        $userIds = array_values(array_unique(array_filter($userIds)));

        if (!$userIds) {
            return;
        }

        $url = route('purchases.show', $purchase, false);

        DB::afterCommit(function () use ($userIds, $title, $message, $url) {
            try {
                $this->announcementService->sendToUsers($userIds, $title, Str::limit($message, 250, ''), $url, false);
            } catch (Throwable $e) {
                report($e);
            }
        });
    }

    private function log(string $message): void
    {
        $this->activityLogService->log(Auth::id(), self::LOG_MODULE, $message);
    }
}
