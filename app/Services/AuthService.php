<?php

namespace App\Services;

use App\Services\ActivityLog\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Service Activity Log.
     */
    protected ActivityLogService $activityLogService;

    /**
     * Constructor.
     */
    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Melakukan proses autentikasi login pengguna.
     *
     * @param array $credentials
     * @param bool $remember
     * @return bool
     *
     * @throws ValidationException
     */
    public function login(array $credentials, bool $remember = false): bool
    {
        // Verifikasi email dan password
        if (!Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => [
                    'Email atau password yang Anda masukkan salah.',
                ],
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Pastikan akun masih aktif
        if ($user->status !== 'active') {

            Auth::logout();

            request()->session()->invalidate();
            request()->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => [
                    'Akun Anda telah dinonaktifkan. Silakan hubungi Super Admin.',
                ],
            ]);
        }

        // Perbarui waktu login terakhir
        $user->update([
            'last_login_at' => Carbon::now(),
        ]);

        // Regenerasi Session
        request()->session()->regenerate();

        // Catat Activity Log
        $this->activityLogService->log(
            $user->id,
            'Authentication',
            'Login'
        );

        return true;
    }

    /**
     * Mengeluarkan pengguna dari sistem (Logout).
     */
    public function logout(): void
    {
        // Simpan ID user sebelum logout
        $userId = Auth::id();

        // Catat Activity Log
        $this->activityLogService->log(
            $userId,
            'Authentication',
            'Logout'
        );

        // Logout
        Auth::logout();

        // Menghancurkan session
        request()->session()->invalidate();

        // Regenerasi CSRF Token
        request()->session()->regenerateToken();
    }
}

