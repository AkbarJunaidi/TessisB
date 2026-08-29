<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Services\PasswordResetRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordResetRequestController extends Controller
{
    protected PasswordResetRequestService $passwordResetRequestService;

    public function __construct(PasswordResetRequestService $passwordResetRequestService)
    {
        $this->passwordResetRequestService = $passwordResetRequestService;
    }

    /**
     * Menampilkan halaman form lupa password.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Memproses permintaan lupa password.
     *
     * Pesan yang ditampilkan ke pengunjung SENGAJA selalu sama baik email
     * cocok maupun tidak, supaya halaman ini tidak bisa dipakai untuk
     * menebak email mana saja yang terdaftar di sistem. Perbedaan hasilnya
     * hanya terjadi di belakang layar: kalau email cocok, Super Admin akan
     * menerima notifikasi dan permintaan muncul di halaman User Management.
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $this->passwordResetRequestService->requestReset(
            $request->validated('email')
        );

        return redirect()
            ->route('forgot-password')
            ->with(
                'success',
                'Jika email tersebut terdaftar di sistem, permintaan Anda sudah diteruskan ke Super Admin untuk ditindaklanjuti.'
            );
    }
}
