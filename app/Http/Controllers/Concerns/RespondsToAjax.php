<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Respons ganda untuk aksi form: JSON bila diminta AJAX (form data-ajax),
 * redirect + flash seperti biasa bila tidak (fallback tanpa JavaScript).
 */
trait RespondsToAjax
{
    /**
     * Sukses. $extra masuk ke JSON; 'reload' => true membuat halaman dimuat ulang
     * dan pesan ditampilkan lewat flash setelah reload.
     */
    protected function done(Request $request, string $message, array $extra = []): RedirectResponse|JsonResponse
    {
        if (!$request->expectsJson()) {
            return redirect()->back()->with('success', $message);
        }

        if (!empty($extra['reload'])) {
            session()->flash('success', $message);
        }

        return response()->json(['message' => $message] + $extra);
    }

    protected function failed(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 422)
            : redirect()->back()->with('error', $message);
    }
}
