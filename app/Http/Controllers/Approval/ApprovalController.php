<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\ApprovalDecisionRequest;
use App\Models\ApprovalRequest;
use App\Services\Approval\ApprovalService;
use App\Services\Auth\PasswordResetRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Exception;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService,
        protected PasswordResetRequestService $passwordResetRequestService
    ) {
    }

    /**
     * "Lupa Password" adalah alur LAMA (beda tabel/service, sudah ada
     * sebelum Approval dibuat) yang penyelesaiannya bukan approve/reject
     * biasa - Super Admin harus benar-benar SET password baru user itu
     * lewat halaman User Management, bukan sekadar klik setuju. Jadi
     * TIDAK dipindah ke tabel approval_requests (biar tidak mengubah alur
     * yang sudah berjalan), cukup DITAMPILKAN bersama di halaman ini
     * dengan tombol yang mengarah ke halaman Edit User yang sudah ada,
     * supaya "kotak masuk lintas modul" ini benar-benar lengkap.
     */
    public function index(): View
    {
        abort_unless(
            Auth::user()?->hasPermission('approval', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat Approval.'
        );

        $pending = $this->approvalService->listPending();
        $history = $this->approvalService->listHistory();
        $pendingPasswordResets = $this->passwordResetRequestService->pendingWithUser();

        return view('approval.index', compact('pending', 'history', 'pendingPasswordResets'));
    }

    public function approve(ApprovalDecisionRequest $request, ApprovalRequest $approvalRequest): RedirectResponse
    {
        try {
            $this->approvalService->approve($approvalRequest, $request->validated()['note'] ?? null);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Permintaan disetujui.');
    }

    public function reject(ApprovalDecisionRequest $request, ApprovalRequest $approvalRequest): RedirectResponse
    {
        try {
            $this->approvalService->reject($approvalRequest, $request->validated()['note'] ?? null);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Permintaan ditolak.');
    }
}
