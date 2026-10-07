<?php

namespace App\Services\Finance;

use App\Models\Contact;
use App\Models\Kwitansi;
use App\Models\Project;
use App\Models\ProjectFinanceItem;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan keuangan perusahaan dari buku kas (project_finance_items): laba/rugi, tren, komposisi, peringkat.
 * Dasar kas: dihitung menurut tanggal transaksi; item milik project yang sudah dihapus tidak ikut.
 */
class FinanceSummaryService
{
    public const PRESETS = [
        'month'     => 'Bulan Ini',
        'lastmonth' => 'Bulan Lalu',
        'quarter'   => '3 Bulan',
        'year'      => 'Tahun Ini',
    ];

    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** @return array{from: Carbon, to: Carbon, preset: string} */
    public function resolvePeriod(?string $preset, ?string $from, ?string $to): array
    {
        $today = Carbon::today();

        if ($preset === 'custom') {
            $f = $this->parseDate($from);
            $t = $this->parseDate($to);

            if ($f && $t) {
                return $f->gt($t)
                    ? ['from' => $t, 'to' => $f, 'preset' => 'custom']
                    : ['from' => $f, 'to' => $t, 'preset' => 'custom'];
            }
        }

        return match ($preset) {
            'lastmonth' => [
                'from'   => $today->copy()->subMonthNoOverflow()->startOfMonth(),
                'to'     => $today->copy()->subMonthNoOverflow()->endOfMonth()->startOfDay(),
                'preset' => 'lastmonth',
            ],
            'quarter' => ['from' => $today->copy()->subMonthsNoOverflow(2)->startOfMonth(), 'to' => $today, 'preset' => 'quarter'],
            'year'    => ['from' => $today->copy()->startOfYear(), 'to' => $today, 'preset' => 'year'],
            default   => ['from' => $today->copy()->startOfMonth(), 'to' => $today, 'preset' => 'month'],
        };
    }

    /**
     * Total periode ini dibanding periode sebelumnya yang sama panjang.
     *
     * @return array<string, mixed>
     */
    public function overview(Carbon $from, Carbon $to): array
    {
        $days     = (int) $from->diffInDays($to) + 1;
        $prevTo   = $from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1);

        $cur  = $this->totals($from, $to);
        $prev = $this->totals($prevFrom, $prevTo);

        return [
            'current'  => $cur,
            'previous' => $prev,
            'prevFrom' => $prevFrom,
            'prevTo'   => $prevTo,
            'margin'   => $cur['income'] > 0 ? ($cur['net'] / $cur['income']) * 100 : null,
            'delta'    => [
                'income'  => $this->delta($cur['income'], $prev['income']),
                'expense' => $this->delta($cur['expense'], $prev['expense']),
                'net'     => $this->delta($cur['net'], $prev['net']),
            ],
        ];
    }

    /** @return array{income: float, expense: float, net: float} */
    public function totals(Carbon $from, Carbon $to): array
    {
        $row = $this->base($from, $to)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount END), 0) as income, COALESCE(SUM(CASE WHEN type = 'expense' THEN amount END), 0) as expense")
            ->first();

        $income  = (float) $row->income;
        $expense = (float) $row->expense;

        return ['income' => $income, 'expense' => $expense, 'net' => $income - $expense];
    }

    /**
     * 12 bulan berakhir di bulan tanggal akhir periode.
     *
     * @return array{labels: array<int, string>, income: array<int, float>, expense: array<int, float>, net: array<int, float>}
     */
    public function trend(Carbon $end): array
    {
        $start = $end->copy()->startOfMonth()->subMonthsNoOverflow(11);

        $buckets = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $start->copy()->addMonthsNoOverflow($i);
            $buckets[$m->format('Y-m')] = ['label' => self::MONTHS[$m->month - 1] . ' ' . $m->format('y'), 'income' => 0.0, 'expense' => 0.0];
        }

        $rows = $this->base($start, $end->copy()->endOfMonth())
            ->selectRaw('tanggal, type, SUM(amount) as total')
            ->groupBy('tanggal', 'type')
            ->get();

        foreach ($rows as $row) {
            $key = $row->tanggal->format('Y-m');

            if (isset($buckets[$key])) {
                $buckets[$key][$row->type] += (float) $row->total;
            }
        }

        $buckets = array_values($buckets);

        return [
            'labels'  => array_column($buckets, 'label'),
            'income'  => array_column($buckets, 'income'),
            'expense' => array_column($buckets, 'expense'),
            'net'     => array_map(fn ($b) => $b['income'] - $b['expense'], $buckets),
        ];
    }

    /** @return array<int, array{name: string, total: float, percent: float}> */
    public function expenseByCategory(Carbon $from, Carbon $to, int $limit = 8): array
    {
        $rows = $this->base($from, $to)
            ->where('type', 'expense')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $sum   = (float) $rows->sum('total');
        $items = $rows->map(fn ($r) => ['name' => $r->category ?: 'Tanpa kategori', 'total' => (float) $r->total]);

        if ($items->count() > $limit) {
            $items = $items->take($limit - 1)->push([
                'name'  => 'Kategori lain',
                'total' => (float) $items->slice($limit - 1)->sum('total'),
            ]);
        }

        return $items->map(fn ($i) => $i + ['percent' => $sum > 0 ? ($i['total'] / $sum) * 100 : 0.0])->values()->all();
    }

    /**
     * Laba per project pada periode: lima tertinggi (laba) dan lima terendah (rugi).
     *
     * @return array{top: array<int, array<string, mixed>>, loss: array<int, array<string, mixed>>}
     */
    public function projectProfits(Carbon $from, Carbon $to): array
    {
        $rows = $this->base($from, $to)
            ->whereNotNull('project_id')
            ->selectRaw("project_id, COALESCE(SUM(CASE WHEN type = 'income' THEN amount END), 0) as income, COALESCE(SUM(CASE WHEN type = 'expense' THEN amount END), 0) as expense")
            ->groupBy('project_id')
            ->get();

        $names = Project::whereIn('id', $rows->pluck('project_id'))->pluck('name', 'id');

        $list = $rows->map(function ($r) use ($names) {
            $income = (float) $r->income;
            $net    = $income - (float) $r->expense;

            return [
                'id'     => (int) $r->project_id,
                'name'   => $names[$r->project_id] ?? 'Project',
                'income' => $income,
                'net'    => $net,
                'margin' => $income > 0 ? ($net / $income) * 100 : null,
            ];
        });

        return [
            'top'  => $list->where('net', '>', 0)->sortByDesc('net')->take(5)->values()->all(),
            'loss' => $list->where('net', '<', 0)->sortBy('net')->take(5)->values()->all(),
        ];
    }

    /** @return array<int, array{name: string, total: float}> */
    public function topClients(Carbon $from, Carbon $to, int $limit = 5): array
    {
        $clientExpr = 'COALESCE(project_finance_items.contact_id, projects.contact_id)';

        $rows = ProjectFinanceItem::query()
            ->leftJoin('projects', 'projects.id', '=', 'project_finance_items.project_id')
            ->where('project_finance_items.type', 'income')
            ->whereBetween('project_finance_items.tanggal', [$from->toDateString(), $to->toDateString()])
            ->where(fn ($q) => $q->whereNull('project_finance_items.project_id')->orWhereNull('projects.deleted_at'))
            ->selectRaw("{$clientExpr} as client_id, SUM(project_finance_items.amount) as total")
            ->groupBy(DB::raw($clientExpr))
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $names = Contact::whereIn('id', $rows->pluck('client_id')->filter())->pluck('name', 'id');

        return $rows->map(fn ($r) => [
            'name'  => $r->client_id ? ($names[$r->client_id] ?? 'Client') : 'Tanpa data client',
            'total' => (float) $r->total,
        ])->all();
    }

    /**
     * Pembelian yang sudah disetujui/diterima tetapi belum dibayar, terlama dulu.
     *
     * @return array{total: float, count: int, items: array<int, array<string, mixed>>}
     */
    public function payables(int $limit = 5): array
    {
        $q = Purchase::query()
            ->whereIn('status', [Purchase::STATUS_APPROVED, Purchase::STATUS_RECEIVED])
            ->where('payment_status', Purchase::PAYMENT_UNPAID);

        $items = (clone $q)
            ->orderByRaw('COALESCE(approved_at, purchase_date) asc')
            ->limit($limit)
            ->get(['id', 'code', 'vendor_name', 'total', 'approved_at', 'purchase_date'])
            ->map(fn ($p) => [
                'id'     => $p->id,
                'code'   => $p->code,
                'vendor' => $p->vendor_name,
                'total'  => (float) $p->total,
                'age'    => (int) Carbon::parse($p->approved_at ?? $p->purchase_date)->startOfDay()->diffInDays(Carbon::today()),
            ])->all();

        return ['total' => (float) (clone $q)->sum('total'), 'count' => (int) $q->count(), 'items' => $items];
    }

    /**
     * Sisa tagihan client: Estimasi Pendapatan dikurangi total diterima, rumus yang sama dengan tab Per Project.
     *
     * @return array{total: float, count: int, items: array<int, array<string, mixed>>}
     */
    public function receivables(int $limit = 5): array
    {
        $rows = Project::query()
            ->where('estimated_value', '>', 0)
            ->withSum(['kwitansis as kwitansi_total' => fn ($q) => $q->where('status', 'Aktif')], 'jumlah')
            ->withSum(['financeItems as income_total' => fn ($q) => $q->where('type', 'income')], 'amount')
            ->get(['id', 'name', 'client', 'estimated_value'])
            ->map(fn ($p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'client' => $p->client,
                'sisa'   => max(0, (float) $p->estimated_value - Project::combinePayments((float) $p->income_total, (float) $p->kwitansi_total)),
            ])
            ->where('sisa', '>', 0);

        return [
            'total' => (float) $rows->sum('sisa'),
            'count' => $rows->count(),
            'items' => $rows->sortByDesc('sisa')->take($limit)->values()->all(),
        ];
    }

    /**
     * Project yang total pengeluarannya (seluruh waktu) melebihi anggaran biaya.
     *
     * @return array<int, array{id: int, name: string, budget: float, spent: float, over: float}>
     */
    public function overBudget(int $limit = 5): array
    {
        return Project::query()
            ->where('budget', '>', 0)
            ->withSum(['financeItems as spent_total' => fn ($q) => $q->where('type', 'expense')], 'amount')
            ->get(['id', 'name', 'budget'])
            ->map(fn ($p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'budget' => (float) $p->budget,
                'spent'  => (float) $p->spent_total,
                'over'   => (float) $p->spent_total - (float) $p->budget,
            ])
            ->where('over', '>', 0)
            ->sortByDesc('over')
            ->take($limit)
            ->values()
            ->all();
    }

    /** Kwitansi aktif pada periode, hanya informasi (tidak dihitung ke laba/rugi). */
    public function kwitansiTotal(Carbon $from, Carbon $to): float
    {
        return (float) Kwitansi::where('status', 'Aktif')
            ->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()])
            ->sum('jumlah');
    }

    private function base(Carbon $from, Carbon $to): Builder
    {
        return ProjectFinanceItem::visible()
            ->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()]);
    }

    private function delta(float $current, float $previous): ?float
    {
        return abs($previous) < 0.005 ? null : (($current - $previous) / abs($previous)) * 100;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
