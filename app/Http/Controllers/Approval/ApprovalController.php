<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\ApprovalDecisionRequest;
use App\Models\ApprovalRequest;
use App\Services\Approval\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Exception;

class ApprovalController extends Controller
{
    public function __construct(protected ApprovalService $approvalService)
    {
    }

    public function index(): View
    {
        abort_unless(
            Auth::user()?->hasPermission('approval', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat Approval.'
        );

        $pending = $this->approvalService->listPending();
        $history = $this->approvalService->listHistory();

        return view('approval.index', compact('pending', 'history'));
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
