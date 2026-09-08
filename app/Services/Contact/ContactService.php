<?php

namespace App\Services\Contact;

use App\Models\Contact;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Mengambil daftar Kontak dengan pencarian (nama, perusahaan, No.
     * HP/WA, atau email), filter huruf awal nama (A-Z), & pagination.
     * Setiap Kontak juga disertai `total_income` (Total Pendapatan dari
     * Project yang namanya cocok - lihat Contact::matchedProjects())
     * yang dihitung sekali lewat 1 query batch, BUKAN per-baris, supaya
     * tidak N+1 query walau daftar Kontak-nya banyak.
     */
    public function getAllPaginated(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Contact::with('creator')->latest();

        if (!empty($filters['search'])) {
            $keyword = trim($filters['search']);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('company', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if (!empty($filters['letter'])) {
            $query->where('name', 'like', $filters['letter'] . '%');
        }

        $contacts = $query->paginate($perPage)->withQueryString();

        $this->attachTotalIncome($contacts->getCollection());

        return $contacts;
    }

    /**
     * Menempelkan atribut sementara `total_income` ke setiap Contact
     * dalam koleksi, lewat 1 query batch (join projects + finance items,
     * dikelompokkan per nama client yang dinormalisasi).
     */
    protected function attachTotalIncome(\Illuminate\Support\Collection $contacts): void
    {
        if ($contacts->isEmpty()) {
            return;
        }

        $normalizedNames = $contacts->map(fn (Contact $c) => mb_strtolower(trim($c->name)))->unique()->values();

        $totals = DB::table('projects')
            ->join('project_finance_items', 'project_finance_items.project_id', '=', 'projects.id')
            ->where('project_finance_items.type', 'income')
            ->whereIn(DB::raw('LOWER(TRIM(projects.client))'), $normalizedNames)
            ->select(
                DB::raw('LOWER(TRIM(projects.client)) as client_key'),
                DB::raw('SUM(project_finance_items.amount) as total')
            )
            ->groupBy(DB::raw('LOWER(TRIM(projects.client))'))
            ->pluck('total', 'client_key');

        foreach ($contacts as $contact) {
            $key = mb_strtolower(trim($contact->name));
            $contact->setAttribute('total_income', (float) ($totals[$key] ?? 0));
        }
    }

    /**
     * Statistik ringkas untuk kartu di atas halaman Kontak.
     */
    public function getStats(): array
    {
        $normalizedNames = Contact::pluck('name')
            ->map(fn ($name) => mb_strtolower(trim($name)))
            ->unique()
            ->values();

        $totalRevenue = $normalizedNames->isEmpty() ? 0 : DB::table('projects')
            ->join('project_finance_items', 'project_finance_items.project_id', '=', 'projects.id')
            ->where('project_finance_items.type', 'income')
            ->whereIn(DB::raw('LOWER(TRIM(projects.client))'), $normalizedNames)
            ->sum('project_finance_items.amount');

        return [
            'total'            => Contact::count(),
            'with_whatsapp'    => Contact::where('has_whatsapp', true)->count(),
            'new_this_month'   => Contact::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'total_revenue'    => (float) $totalRevenue,
        ];
    }

    /**
     * Menyimpan Kontak baru.
     */
    public function createContact(array $data): Contact
    {
        $contact = Contact::create([
            'name'         => $data['name'],
            'company'      => $data['company'] ?? null,
            'phone'        => $data['phone'] ?? null,
            'has_whatsapp' => $data['has_whatsapp'] ?? false,
            'email'        => $data['email'] ?? null,
            'address'      => $data['address'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'created_by'   => Auth::id(),
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'Kontak',
            "Create Contact: {$contact->name}"
        );

        return $contact;
    }

    /**
     * Memperbarui data Kontak.
     */
    public function updateContact(Contact $contact, array $data): Contact
    {
        $contact->update([
            'name'         => $data['name'],
            'company'      => $data['company'] ?? null,
            'phone'        => $data['phone'] ?? null,
            'has_whatsapp' => $data['has_whatsapp'] ?? false,
            'email'        => $data['email'] ?? null,
            'address'      => $data['address'] ?? null,
            'notes'        => $data['notes'] ?? null,
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'Kontak',
            "Update Contact: {$contact->name}"
        );

        return $contact;
    }

    /**
     * Menghapus Kontak (hapus permanen - fitur ini belum terintegrasi ke
     * Trash, mengikuti keputusan awal fitur ini dibuat sederhana).
     */
    public function deleteContact(Contact $contact): bool
    {
        $name = $contact->name;

        $deleted = $contact->delete();

        $this->activityLogService->log(
            Auth::id(),
            'Kontak',
            "Delete Contact: {$name}"
        );

        return $deleted;
    }
}
