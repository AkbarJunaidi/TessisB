<?php

namespace App\Http\Controllers\Signature;

use App\Http\Controllers\Controller;
use App\Http\Requests\Signature\SignatureRequest;
use App\Models\Signature;
use App\Services\Signature\SignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Exception;

class SignatureController extends Controller
{
    public function __construct(protected SignatureService $signatureService)
    {
    }

    /**
     * Halaman "Tanda Tangan Saya" - murni personal (bukan modul bisnis),
     * jadi tidak digating permission khusus, cukup harus login.
     */
    public function index(): View
    {
        $signatures = Auth::user()->signatures()->latest()->get();

        return view('signature.index', compact('signatures'));
    }

    public function store(SignatureRequest $request): RedirectResponse
    {
        try {
            $this->signatureService->store(
                Auth::user(),
                $request->validated()['label'],
                $request->file('file'),
                $request->input('canvas_data')
            );
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('signature.index')->with('success', 'Tanda tangan berhasil disimpan.');
    }

    public function setDefault(Signature $signature): RedirectResponse
    {
        abort_unless($signature->user_id === Auth::id(), 403, 'Tanda tangan ini bukan milik Anda.');

        $this->signatureService->setDefault($signature);

        return redirect()->route('signature.index')->with('success', 'Tanda tangan default diperbarui.');
    }

    public function destroy(Signature $signature): RedirectResponse
    {
        abort_unless($signature->user_id === Auth::id(), 403, 'Tanda tangan ini bukan milik Anda.');

        $this->signatureService->delete($signature);

        return redirect()->route('signature.index')->with('success', 'Tanda tangan dihapus.');
    }
}
