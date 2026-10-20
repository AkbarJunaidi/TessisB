<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\PurchaseAttachmentRequest;
use App\Http\Requests\Purchase\PurchaseCancelRequest;
use App\Http\Requests\Purchase\PurchaseFundRequest;
use App\Http\Requests\Purchase\PurchaseOrderRequest;
use App\Http\Requests\Purchase\PurchaseReturnRequest;
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
            'funder:id,name',
            'items.inventory:id,name,serial_pending',
            'histories.user:id,name',
            'attachments.uploader:id,name',
            'approvalRequests' => fn ($q) => $q->with('decidedBy:id,name')->latest(),
        ]);

        $nextSteps = $this->purchaseService->nextSteps($purchase, Auth::user());

        return view('purchase.show', compact('purchase', 'nextSteps'));
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
        return $this->run(
            fn () => $this->purchaseService->receive($purchase, $request->validated()['lines']),
            'Penerimaan barang tercatat dan stok Inventory diperbarui.'
        );
    }

    public function returnGoods(PurchaseReturnRequest $request, Purchase $purchase): RedirectResponse
    {
        return $this->run(
            fn () => $this->purchaseService->returnGoods(
                $purchase,
                $request->validated()['lines'],
                $request->validated()['note'],
                isset($request->validated()['refund']) ? (float) $request->validated()['refund'] : null,
                $request->validated()['refunded_on'] ?? null
            ),
            'Retur barang tercatat dan stok Inventory dikurangi.'
        );
    }

    public function po(Purchase $purchase)
    {
        $this->authorizeAction('view', 'melihat data pembelian');

        try {
            return $this->purchaseService->generatePoPdf($purchase);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function order(PurchaseOrderRequest $request, Purchase $purchase): RedirectResponse
    {
        return $this->run(
            fn () => $this->purchaseService->order(
                $purchase,
                $request->validated(),
                $request->file('order_proof'),
                $request->file('receipt_file')
            ),
            'Pembelian ditandai dipesan.'
        );
    }

    public function fund(PurchaseFundRequest $request, Purchase $purchase): RedirectResponse
    {
        return $this->run(
            fn () => $this->purchaseService->fund($purchase, $request->validated(), $request->file('proof')),
            'Transfer dana ke finance tercatat.'
        );
    }

    public function pay(PurchasePaymentRequest $request, Purchase $purchase): RedirectResponse
    {
        return $this->run(
            fn () => $this->purchaseService->recordPayment($purchase, $request->validated(), $request->file('proof')),
            'Pembayaran tercatat.'
        );
    }

    public function storeAttachment(PurchaseAttachmentRequest $request, Purchase $purchase): RedirectResponse
    {
        return $this->run(
            fn () => $this->purchaseService->addAttachment($purchase, $request->file('file'), $request->validated()['stage']),
            'Lampiran ditambahkan.'
        );
    }

    public function destroyAttachment(PurchaseAttachment $attachment): RedirectResponse
    {
        $this->authorizeAction('edit', 'menghapus lampiran pembelian');

        return $this->run(
            fn () => $this->purchaseService->removeAttachment($attachment),
            'Lampiran dihapus.'
        );
    }

    public function showAttachment(PurchaseAttachment $attachment)
    {
        $this->authorizeAction('view', 'melihat data pembelian');

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->response($attachment->path, $attachment->original_name);
    }

    public function cancel(PurchaseCancelRequest $request, Purchase $purchase): RedirectResponse
    {
        return $this->run(
            fn () => $this->purchaseService->cancel($purchase, $request->validated()['reason'] ?? null),
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
