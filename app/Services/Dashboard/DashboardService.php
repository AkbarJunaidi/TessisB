<?php

namespace App\Services\Dashboard;

use App\Models\Contact;
use App\Models\File;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Project;
use App\Models\SuratJalan;
use App\Models\Task;
use App\Models\User;
use App\Services\Finance\FinanceSummaryService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(
        protected FinanceSummaryService $financeSummaryService
    ) {}

    /**
     * Cache angka global dashboard (detik). Isinya sama untuk semua role;
     * siapa boleh melihat apa disaring di Controller/View lewat hasPermission().
     */
    private const CACHE_SECONDS = 60;

    /** Urutan dan label status unit pada kartu Kondisi Alat. */
    private const UNIT_STATES = ['Tersedia', 'Dipinjam', 'Perbaikan', 'Rusak', 'Hilang'];

    public function getStatistics(): array
    {
        return Cache::remember(
            'dashboard.statistics',
            self::CACHE_SECONDS,
            fn () => [
                'total_inventory'          => Inventory::count(),
                'total_project'            => Project::count(),
                'total_task'               => Task::count(),
                'total_files'              => File::count(),
                'total_user'               => User::count(),
                'total_contact'            => Contact::count(),
                'new_users_this_month'     => $this->createdThisMonth(User::query()),
                'new_projects_this_month'  => $this->createdThisMonth(Project::query()),
                'new_inventory_this_month' => $this->createdThisMonth(Inventory::query()),
                'new_contacts_this_month'  => $this->createdThisMonth(Contact::query()),
            ]
        );
    }

    private function createdThisMonth(\Illuminate\Database\Eloquent\Builder $query): int
    {
        return $query
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
    }

    /**
     * Ringkasan keuangan dari buku kas yang sama dengan halaman Keuangan, dalam tiga periode
     * (minggu, bulan, tahun) supaya dropdown di Dashboard berganti tanpa permintaan baru.
     */
    public function getFinanceSummary(): array
    {
        return Cache::remember('dashboard.finance', self::CACHE_SECONDS, function () {
            $today = now()->startOfDay();

            return [
                'periods' => [
                    'week'  => $this->financePeriod($today->copy()->subDays(6), $today, false),
                    'month' => $this->financePeriod($today->copy()->subDays(29), $today, false),
                    'year'  => $this->financePeriod($today->copy()->startOfMonth()->subMonthsNoOverflow(11), $today->copy()->endOfMonth(), true),
                ],
                'receivables' => $this->financeSummaryService->receivables(1),
                'payables'    => $this->financeSummaryService->payables(1),
            ];
        });
    }

    /**
     * Total dan grafik satu periode, dibanding periode sebelumnya yang sama panjang.
     * Periode tahunan digambar per bulan, lainnya per hari.
     */
    private function financePeriod(Carbon $from, Carbon $to, bool $monthly): array
    {
        $days     = (int) $from->diffInDays($to) + 1;
        $prevTo   = $from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1);

        $cur  = $this->financeSummaryService->totals($from, $to);
        $prev = $this->financeSummaryService->totals($prevFrom, $prevTo);

        $delta = fn (float $now, float $before): ?int => $before > 0 ? (int) round((($now - $before) / $before) * 100) : null;

        $series = $monthly
            ? $this->financeSummaryService->trend($to)
            : $this->financeSummaryService->daily($from, $to);

        return [
            'income'        => $cur['income'],
            'expense'       => $cur['expense'],
            'net'           => $cur['net'],
            'income_delta'  => $delta($cur['income'], $prev['income']),
            'expense_delta' => $delta($cur['expense'], $prev['expense']),
            'trend'         => [
                'labels'  => $series['labels'],
                'income'  => $series['income'],
                'expense' => $series['expense'],
            ],
        ];
    }

    /**
     * Jumlah dan nilai estimasi project per status, urut sesuai papan Pipeline.
     *
     * @return array<int, array{status: string, label: string, count: int, value: float}>
     */
    public function getPipeline(): array
    {
        return Cache::remember('dashboard.pipeline', self::CACHE_SECONDS, function () {
            $rows = Project::query()
                ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(estimated_value), 0) as value')
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            $stages = [];
            foreach (Project::STATUS_LABELS as $status => $label) {
                $row = $rows->get($status);

                $stages[] = [
                    'status' => $status,
                    'label'  => $label,
                    'count'  => (int) ($row->total ?? 0),
                    'value'  => (float) ($row->value ?? 0),
                ];
            }

            return $stages;
        });
    }

    /**
     * Jumlah unit alat per kondisi; unit yang sedang di Surat Jalan dihitung Dipinjam.
     *
     * @return array{total: int, items: array<int, array{label: string, count: int}>}
     */
    public function getEquipmentStatus(): array
    {
        return Cache::remember('dashboard.equipment_status', self::CACHE_SECONDS, function () {
            $state = "CASE WHEN surat_jalan_item_id IS NOT NULL THEN 'Dipinjam' ELSE status END";

            $rows = InventoryUnit::query()
                ->whereHas('inventory')
                ->selectRaw("{$state} as state, COUNT(*) as total")
                ->groupByRaw($state)
                ->pluck('total', 'state')
                ->all();

            $items = [];
            $known = 0;
            foreach (self::UNIT_STATES as $label) {
                $count = (int) ($rows[$label] ?? 0);
                $known += $count;
                $items[] = ['label' => $label, 'count' => $count];
            }

            $total  = (int) array_sum($rows);
            $others = $total - $known;
            if ($others > 0) {
                $items[] = ['label' => 'Lainnya', 'count' => $others];
            }

            return ['total' => $total, 'items' => $items];
        });
    }

    /**
     * Task milik user, deadline terdekat dulu. Kolom board bisa diganti nama
     * per project, jadi "selesai" dikenali dari label umum (done/selesai).
     */
    public function getMyTasks(User $user, int $limit = 5): Collection
    {
        return Task::query()
            ->where('assigned_to', $user->id)
            ->whereRaw('LOWER(status) NOT IN (?, ?)', ['done', 'selesai'])
            ->with('project:id,name')
            ->orderBy('deadline')
            ->take($limit)
            ->get();
    }

    /**
     * Acara project untuk $days hari ke depan (mulai hari ini), dikelompokkan per hari.
     * Project multi-hari muncul di tiap harinya.
     *
     * @return array{days: array<int, array<string, mixed>>, total: int}
     */
    public function getWeekSchedule(int $days = 7): array
    {
        $start = now()->startOfDay();
        $end   = $start->copy()->addDays($days - 1);

        $projects = Project::query()
            ->whereNotNull('event_date')
            ->whereDate('event_date', '<=', $end->toDateString())
            ->whereRaw('DATE(COALESCE(event_end_date, event_date)) >= ?', [$start->toDateString()])
            ->orderBy('event_date')
            ->orderBy('event_time_start')
            ->limit(80)
            ->get(['id', 'name', 'client', 'event_date', 'event_end_date', 'event_time_start', 'status']);

        $names  = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $result = [];

        for ($i = 0; $i < $days; $i++) {
            $date    = $start->copy()->addDays($i);
            $dateStr = $date->toDateString();

            $result[] = [
                'date'     => $date,
                'name'     => $names[$date->dayOfWeek],
                'is_today' => $i === 0,
                'projects' => $projects->filter(function (Project $p) use ($dateStr) {
                    $from = Carbon::parse($p->event_date)->toDateString();
                    $to   = $p->event_end_date ? Carbon::parse($p->event_end_date)->toDateString() : $from;

                    return $from <= $dateStr && $to >= $dateStr;
                })->values(),
            ];
        }

        return ['days' => $result, 'total' => $projects->count()];
    }

    /** Surat Jalan terbaru lintas project; yang belum selesai tampil lebih dulu. */
    public function getRecentSuratJalan(int $limit = 5): Collection
    {
        return SuratJalan::query()
            ->with(['project:id,name', 'dipinjamOleh:id,name'])
            ->orderByRaw("CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END")
            ->latest()
            ->take($limit)
            ->get();
    }
}
