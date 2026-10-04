<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\SettingUpdateRequest;
use App\Services\Setting\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Pengaturan Pemilik - HANYA Super Admin (role gate di route, dicek ulang di FormRequest).
 */
class SettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    public function index(): View
    {
        return view('settings.index', [
            'settings' => $this->settingService->formValues(),
            'images'   => $this->settingService->imageInfo(),
        ]);
    }

    public function update(SettingUpdateRequest $request): RedirectResponse
    {
        try {
            $this->settingService->update(
                $request->validated(),
                array_filter([
                    'logo_pdf'  => $request->file('logo_pdf'),
                    'kop_atas'  => $request->file('kop_atas'),
                    'kop_bawah' => $request->file('kop_bawah'),
                ]),
                $request->input('reset_images', [])
            );
        } catch (\Throwable $e) {
            Log::error('Update Pengaturan gagal: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Pengaturan gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan.');
    }
}
