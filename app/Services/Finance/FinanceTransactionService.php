<?php

namespace App\Services\Finance;

use App\Support\PerPage;
use App\Models\ProjectFinanceItem;
use App\Services\ActivityLog\ActivityLogService;
use App\Support\FinanceLock;
use App\Support\Money;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Buku kas semua transaksi (tabel project_finance_items): daftar berfilter, total, dan input manual.
 */
class FinanceTransactionService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /** @param array<string, mixed> $f from, to, type, category, project_id, contact_id, search */
    public function query(array $f): Builder
    {
        $search = trim((string) ($f['search'] ?? ''));

        return ProjectFinanceItem::visible()
            ->when(!empty($f['from']), fn ($q) => $q->whereDate('tanggal', '>=', $f['from']))
            ->when(!empty($f['to']), fn ($q) => $q->whereDate('tanggal', '<=', $f['to']))
            ->when(in_array($f['type'] ?? '', ['income', 'expense'], true), fn ($q) => $q->where('type', $f['type']))
            ->when(!empty($f['category']), fn ($q) => $q->where('category', $f['category']))
            ->when(!empty($f['project_id']), fn ($q) => $q->where('project_id', (int) $f['project_id']))
            // Pihak: kontak langsung di baris (vendor/penerima) atau client milik project-nya.
            ->when(!empty($f['contact_id']), fn ($q) => $q->where(
                fn ($w) => $w->where('contact_id', (int) $f['contact_id'])
                    ->orWhereHas('project', fn ($p) => $p->where('contact_id', (int) $f['contact_id']))
            ))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';

                $q->where(fn ($w) => $w->where('description', 'like', $like)->orWhere('recipient', 'like', $like));
            });
    }

    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->query($filters)
            ->with(['project:id,name,client,contact_id', 'contact:id,name'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(PerPage::resolve($perPage))
            ->withQueryString();
    }

    /** @return array{income: float, expense: float, net: float} */
    public function totals(array $filters): array
    {
        $row = $this->query($filters)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount END), 0) as income, COALESCE(SUM(CASE WHEN type = 'expense' THEN amount END), 0) as expense")
            ->first();

        $income  = (float) $row->income;
        $expense = (float) $row->expense;

        return ['income' => $income, 'expense' => $expense, 'net' => $income - $expense];
    }

    /**
     * @param array<string, mixed> $data
     * @throws Exception
     */
    public function create(array $data): ProjectFinanceItem
    {
        FinanceLock::assertOpen($data['tanggal'] ?? now());

        $item = ProjectFinanceItem::create($data + ['created_by' => Auth::id()]);

        $this->activityLogService->log(
            Auth::id(),
            'Data Keuangan',
            'Mencatat ' . ($item->type === 'income' ? 'pemasukan' : 'pengeluaran') . ' ' . Money::formatRupiah($item->amount) . " ({$item->category})"
        );

        return $item;
    }

    /**
     * @param array<string, mixed> $data
     * @throws Exception
     */
    public function update(ProjectFinanceItem $item, array $data): ProjectFinanceItem
    {
        $this->ensureEditable($item);
        FinanceLock::assertOpen($item->tanggal);
        FinanceLock::assertOpen($data['tanggal'] ?? $item->tanggal);

        $item->update($data);

        $this->activityLogService->log(Auth::id(), 'Data Keuangan', "Mengubah transaksi #{$item->id} ({$item->category})");

        return $item;
    }

    /** @throws Exception */
    public function delete(ProjectFinanceItem $item): void
    {
        $this->ensureEditable($item);
        FinanceLock::assertOpen($item->tanggal);

        $label = ($item->type === 'income' ? 'pemasukan' : 'pengeluaran') . ' ' . Money::formatRupiah($item->amount) . " ({$item->category})";
        $item->delete();

        $this->activityLogService->log(Auth::id(), 'Data Keuangan', "Menghapus {$label}");
    }

    /** @throws Exception */
    private function ensureEditable(ProjectFinanceItem $item): void
    {
        if ($item->isAuto()) {
            throw new Exception('Transaksi otomatis diubah lewat modul asalnya (Pembelian atau Perbaikan Barang).');
        }
    }
}
