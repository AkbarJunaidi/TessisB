<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Requests\Purchase\PurchaseAttachmentRequest;
use App\Http\Requests\Purchase\PurchaseCancelRequest;
use App\Http\Requests\Purchase\PurchaseReturnRequest;
use App\Http\Requests\Purchase\PurchaseFundRequest;
use App\Http\Requests\Purchase\PurchaseOrderRequest;
use App\Http\Requests\Purchase\PurchasePaymentRequest;
use App\Http\Requests\Purchase\PurchaseReceiveRequest;
use App\Http\Requests\Purchase\PurchaseRequest;
use App\Models\Contact;
use App\Models\Inventory;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\PurchaseAttachment;
use App\Services\Inventory\RepairService;
use App\Services\Purchase\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Exception;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService,
        protected RepairService $repairService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizeAction('view', 'melihat data pembelian');

        $filters = $request->only('search', 'status', 'payment_status', 'from', 'to', 'due');

        $purchases = $this->purchaseService->getAllPaginated($filters);
        $stats = $this->purchaseService->getStats();

        return view('purchase.index', compact('purchases', 'filters', 'stats'));
    }

    public function create(): View
    {
        $this->authorizeAction('create', 'membuat pembelian');

        return view('purchase.create', $this->formData());
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $purchase = $this->purchaseService->create($request->validated(), $request->file('attachment'));

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', "Pembelian {$purchase->code} tersimpan sebagai Draft.");
    }

    public function show(Purchase $purchase): View
    {
        $this->authorizeAction('view', 'melihat data pembelian');

        $purchase->load([
            'vendor',
            'project:id,name',
            'creator:id,name',
            'receiver:id,name',
            'payer:id,name',
            'orderer:id,name',
            'fundTransferrer:id,name',
            'statusLogs.user:id,name',
            'attachments.uploader:id,name',
            'items.inventory:id,name',
            'approvalRequests' => fn ($q) => $q->with('decidedBy:id,name')->latest(),
        ]);

        return view('purchase.show', compact('purchase'));
    }

    public function edit(Purchase $purchase): View|RedirectResponse
    {
        $this->authorizeAction('edit', 'mengubah pembelian');

        if (!$purchase->isEditable()) {
            return redirect()->route('purchases.show', $purchase)->with('error', 'Pembelian ini sudah tidak bisa diubah.');
        }

        $purchase->load('items');

        return view('purchase.edit', array_merge($this->formData(), compact('purchase')));
    }

    public function update(PurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        try {
            $this->purchaseService->update($purchase, $request->validated(), $request->file('attachment'));
        } catch (Exception $e) {
            return redirect()->route('purchases.show', $purchase)->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.show', $purchase)->with('success', 'Pembelian berhasil diperbarui.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        $this->authorizeAction('delete', 'menghapus pembelian');

        try {
            $this->purchaseService->delete($purchase);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.index')->with('success', 'Draft pembelian dihapus.');
    }

    public function submit(Purchase $purchase): RedirectResponse
    {
        $this->authorizeAction('edit', 'mengajukan pembelian');

        return $this->run(
            fn () => $this->purchaseService->submit($purchase),
            'Pembelian diajukan, menunggu approval.'
        );
    }

    public function receive(PurchaseReceiveRequest $request, Purchase $purchase): RedirectResponse
    {
        $data = $request->validated();

        return $this->run(
            fn () => $this->purchaseService->receive($purchase, $data['qty'] ?? [], $data['serials'] ?? [], $data['price'] ?? []),
            'Penerimaan barang tercatat dan stok Inventory diperbarui.'
        );
    }

    public function returnItems(PurchaseReturnRequest $request, Purchase $purchase): RedirectResponse
    {
        $data = $request->validated();

        return $this->run(
            fn () => $this->purchaseService->returnItems(
                $purchase,
                $data['qty'],
                $data['reason'],
                isset($data['refund_amount']) ? (float) $data['refund_amount'] : null,
                $data['refunded_on'] ?? null
            ),
            'Retur tercatat, stok dan total pembelian diperbarui.'
        );
    }

    public function pdf(Purchase $purchase)
    {
        $this->authorizeAction('view', 'melihat data pembelian');

        abort_unless($purchase->canPrintPo(), 404);

        $purchase->load(['items', 'vendor', 'project:id,name', 'creator:id,name', 'approvalRequests.decidedBy:id,name']);

        return Pdf::loadView('purchase.pdf', ['purchase' => $purchase])
            ->setPaper('a4', 'portrait')
            ->stream($purchase->code . '.pdf');
    }

    public function pay(PurchasePaymentRequest $request, Purchase $purchase): RedirectResponse
    {
        return $this->run(
            fn () => $this->purchaseService->recordPayment($purchase, $request->validated(), $request->file('proof')),
            'Pembayaran tercatat.'
        );
    }

    public function order(PurchaseOrderRequest $request, Purchase $purchase): RedirectResponse
    {
        $first = $purchase->canOrder();

        return $this->run(
            fn () => $this->purchaseService->order($purchase, $request->validated(), $request->file('proof')),
            $first ? 'Pembelian ditandai dipesan.' : 'Info pesanan diperbarui.'
        );
    }

    public function fund(PurchaseFundRequest $request, Purchase $purchase): RedirectResponse
    {
        return $this->run(
            fn () => $this->purchaseService->recordFundTransfer($purchase, $request->validated(), $request->file('proof')),
            'Transfer dana ke finance tercatat.'
        );
    }

    public function storeFile(PurchaseAttachmentRequest $request, Purchase $purchase): RedirectResponse
    {
        return $this->run(
            fn () => $this->purchaseService->addAttachment($purchase, $request->validated()['stage'], $request->file('file')),
            'Lampiran ditambahkan.'
        );
    }

    public function showFile(Purchase $purchase, PurchaseAttachment $file)
    {
        $this->authorizeAction('view', 'melihat data pembelian');

        abort_unless($file->purchase_id === $purchase->id && Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->response($file->path, $file->original_name);
    }

    public function destroyFile(Purchase $purchase, PurchaseAttachment $file): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($file->purchase_id === $purchase->id, 404);
        abort_unless(
            ($user?->hasPermission('purchase', 'edit') || $user?->hasPermission('purchase', 'pay'))
                && ($file->uploaded_by === $user->id || $user->isSuperAdmin()),
            403,
            'Anda tidak memiliki hak akses untuk menghapus lampiran ini.'
        );

        return $this->run(
            fn () => $this->purchaseService->removeAttachment($file),
            'Lampiran dihapus.'
        );
    }

    public function cancel(PurchaseCancelRequest $request, Purchase $purchase): RedirectResponse
    {
        $data = $request->validated();

        return $this->run(
            fn () => $this->purchaseService->cancel(
                $purchase,
                $data['reason'] ?? null,
                isset($data['refund_amount']) ? (float) $data['refund_amount'] : null,
                $data['refunded_on'] ?? null
            ),
            'Pembelian dibatalkan.'
        );
    }

    public function attachment(Purchase $purchase)
    {
        $this->authorizeAction('view', 'melihat data pembelian');

        abort_unless($purchase->attachment && Storage::disk('local')->exists($purchase->attachment), 404);

        return Storage::disk('local')->response($purchase->attachment);
    }

    private function run(callable $action, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', $successMessage);
    }

    private function formData(): array
    {
        return [
            'vendors'     => Contact::vendors()->orderBy('name')->get(['id', 'name', 'company']),
            'projects'    => Project::orderByDesc('id')->limit(300)->get(['id', 'name']),
            'inventories' => Inventory::orderBy('name')->get(['id', 'name', 'serial_number']),
            // Biaya servis termasuk data inventory, jadi hanya untuk yang boleh melihat Inventory.
            'repairCosts' => Auth::user()?->hasPermission('inventory', 'view') ? $this->repairService->costComparison() : [],
        ];
    }

    private function authorizeAction(string $action, string $label): void
    {
        abort_unless(
            Auth::user()?->hasPermission('purchase', $action),
            403,
            "Anda tidak memiliki hak akses untuk {$label}."
        );
    }
}
