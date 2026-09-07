<?php

namespace App\Services\Contact;

use App\Models\Contact;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ContactService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Mengambil daftar Kontak dengan pencarian (nama, perusahaan, No.
     * HP/WA, atau email) & pagination.
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
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

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Menyimpan Kontak baru.
     */
    public function createContact(array $data): Contact
    {
        $contact = Contact::create([
            'name'       => $data['name'],
            'company'    => $data['company'] ?? null,
            'phone'      => $data['phone'],
            'email'      => $data['email'] ?? null,
            'address'    => $data['address'] ?? null,
            'created_by' => Auth::id(),
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
            'name'    => $data['name'],
            'company' => $data['company'] ?? null,
            'phone'   => $data['phone'],
            'email'   => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
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
