<?php

namespace App\Services\Approval;

use App\Support\PerPage;
use App\Models\ApprovalRequest;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\Project\KwitansiService;
use App\Services\Purchase\PurchaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class ApprovalService
{
    /**
     * Registry jenis approval -> [Service class, method] yang dijalankan
     * SAAT disetujui. Tambah jenis baru di sini setiap ada fitur lain
     * yang perlu approval - lihat KwitansiService::requestVoid() sebagai
     * contoh cara submit().
     */
    private const HANDLERS = [
        'kwitansi_void'    => [KwitansiService::class, 'markVoided'],
        'purchase_approve' => [PurchaseService::class, 'markApproved'],
    ];

    /**
     * Opsional: jenis approval yang perlu bereaksi saat DITOLAK. Jenis yang
     * tidak terdaftar di sini cukup berubah status permintaannya saja.
     */
    private const REJECT_HANDLERS = [
        'purchase_approve' => [PurchaseService::class, 'markRejected'],
    ];

    public function __construct(protected ActivityLogService $activityLogService)
    {
    }

    /**
     * Ajukan permintaan baru. Kalau sudah ada permintaan PENDING untuk
     * requestable+type yang sama, kembalikan yang lama (bukan duplikat)
     * supaya tidak ada 2 permintaan menumpuk untuk 1 hal yang sama.
     */
    public function submit(string $type, Model $requestable, ?string $reason, array $payload = []): ApprovalRequest
    {
        $existing = ApprovalRequest::where('type', $type)
            ->where('requestable_type', $requestable::class)
            ->where('requestable_id', $requestable->getKey())
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return $existing;
        }

        $request = ApprovalRequest::create([
            'type'             => $type,
            'requestable_type' => $requestable::class,
            'requestable_id'   => $requestable->getKey(),
            'payload'          => $payload,
            'status'           => 'pending',
            'requested_by'     => Auth::id(),
            'reason'           => $reason,
        ]);

        $this->activityLogService->log(Auth::id(), 'Approval', "Mengajukan: {$request->display_title}");

        return $request;
    }

    public function approve(ApprovalRequest $request, ?string $note): ApprovalRequest
    {
        if ($request->status !== 'pending') {
            throw new Exception('Permintaan ini sudah diproses.');
        }

        DB::transaction(function () use ($request, $note) {
            // Container resolve (bukan constructor injection) supaya tidak
            // circular dependency dengan service yang memanggil submit()
            // di atas (mis. KwitansiService).
            $handler = self::HANDLERS[$request->type] ?? null;

            if ($handler && $request->requestable) {
                app($handler[0])->{$handler[1]}($request->requestable);
            }

            $request->update([
                'status'        => 'approved',
                'decided_by'    => Auth::id(),
                'decided_at'    => now(),
                'decision_note' => $note,
            ]);
        });

        $this->activityLogService->log(Auth::id(), 'Approval', "Menyetujui: {$request->display_title}");

        return $request->refresh();
    }

    public function reject(ApprovalRequest $request, ?string $note): ApprovalRequest
    {
        if ($request->status !== 'pending') {
            throw new Exception('Permintaan ini sudah diproses.');
        }

        DB::transaction(function () use ($request, $note) {
            $handler = self::REJECT_HANDLERS[$request->type] ?? null;

            if ($handler && $request->requestable) {
                app($handler[0])->{$handler[1]}($request->requestable);
            }

            $request->update([
                'status'        => 'rejected',
                'decided_by'    => Auth::id(),
                'decided_at'    => now(),
                'decision_note' => $note,
            ]);
        });

        $this->activityLogService->log(Auth::id(), 'Approval', "Menolak: {$request->display_title}");

        return $request;
    }

    public function listPending(): Collection
    {
        return ApprovalRequest::where('status', 'pending')
            ->with('requestedBy')
            ->latest()
            ->get();
    }

    public function listHistory(int $perPage = 15): LengthAwarePaginator
    {
        return ApprovalRequest::whereIn('status', ['approved', 'rejected'])
            ->with('requestedBy', 'decidedBy')
            ->latest('decided_at')
            ->paginate(PerPage::resolve($perPage));
    }

    public function pendingCount(): int
    {
        return ApprovalRequest::where('status', 'pending')->count();
    }
}
