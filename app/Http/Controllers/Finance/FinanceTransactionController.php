<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FinanceTransactionRequest;
use App\Models\Contact;
use App\Models\Project;
use App\Models\ProjectFinanceItem;
use App\Services\Finance\FinanceTransactionService;
use App\Support\FinanceCategory;
use App\Support\FinanceLock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Tab "Transaksi" halaman Keuangan: buku kas semua pemasukan dan pengeluaran.
 * Lihat = finance.view, ubah = finance.manage (dicek di FormRequest/aksi).
 */
class FinanceTransactionController extends Controller
{
    private const FILTER_KEYS = ['from', 'to', 'type', 'category', 'project_id', 'contact_id', 'search'];

    public function __construct(
        protected FinanceTransactionService $transactionService
    ) {}

    public function index(Request $request): View
    {
        abort_unless(
            Auth::user()?->hasPermission('finance', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data keuangan.'
        );

        $filters = $request->only(self::FILTER_KEYS);

        // Tanpa filter sama sekali: tampilkan bulan berjalan. Tombol "Semua" mengirim tanggal kosong.
        if (!$request->hasAny(self::FILTER_KEYS)) {
            $filters['from'] = now()->startOfMonth()->toDateString();
            $filters['to']   = now()->toDateString();
        }

        return view('finance.transactions.index', [
            'transactions' => $this->transactionService->paginate($filters),
            'totals'       => $this->transactionService->totals($filters),
            'filters'      => $filters,
            'categoryMap'  => FinanceCategory::map(),
            'manualCategories' => [
                'income'  => FinanceCategory::manual('income'),
                'expense' => FinanceCategory::manual('expense'),
            ],
            'projects'  => Project::orderBy('name')->get(['id', 'name']),
            'contacts'  => Contact::orderBy('name')->get(['id', 'name']),
            'canManage' => Auth::user()->hasPermission('finance', 'manage'),
            'lockDate'  => FinanceLock::date(),
        ]);
    }

    public function store(FinanceTransactionRequest $request): RedirectResponse
    {
        try {
            $this->transactionService->create($this->payload($request));
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transaksi berhasil dicatat.');
    }

    public function update(FinanceTransactionRequest $request, ProjectFinanceItem $transaction): RedirectResponse
    {
        try {
            $this->transactionService->update($transaction, $this->payload($request));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(ProjectFinanceItem $transaction): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('finance', 'manage'), 403, 'Anda tidak memiliki hak akses untuk menghapus transaksi.');

        try {
            $this->transactionService->delete($transaction);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transaksi berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function payload(FinanceTransactionRequest $request): array
    {
        $data = $request->validated();

        foreach (['project_id', 'contact_id', 'recipient', 'payment_method', 'description'] as $key) {
            $data[$key] = ($data[$key] ?? '') === '' ? null : $data[$key];
        }

        return $data;
    }
}
