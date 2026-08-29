<?php

namespace App\Services;

use App\Services\ActivityLog\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Service Activity Log.
     */
    protected ActivityLogService $activityLogService;

    /**
     * Batas percobaan login gagal (per kombinasi email + IP) sebelum
     * akun dikunci sementara. Mengikuti pola RateLimiter standar Laravel.
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Lama penguncian (detik) setelah batas percobaan gagal tercapai.
     */
    private const LOCKOUT_DECAY_SECONDS = 60;

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
     * Dilindungi rate limiting per kombinasi email + IP address - mencegah
     * brute force baik dari 1 IP yang mencoba banyak password untuk 1 email,
     * maupun percobaan berulang dari IP yang sama untuk email yang sama.
     *
     * @param array $credentials
     * @param bool $remember
     * @return bool
     *
     * @throws ValidationException
     */
    public function login(array $credentials, bool $remember = false): bool
    {
        $throttleKey = $this->throttleKey($credentials['email'] ?? '');

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => [
                    "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
                ],
            ]);
        }

        // Verifikasi email dan password
        if (!Auth::attempt($credentials, $remember)) {

            RateLimiter::hit($throttleKey, self::LOCKOUT_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => [
                    'Email atau password yang Anda masukkan salah.',
                ],
            ]);
        }

        // Login berhasil - hapus catatan percobaan gagal sebelumnya.
        RateLimiter::clear($throttleKey);

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

    /**
     * Membentuk key rate limiter dari email + IP, supaya penguncian berlaku
     * per kombinasi keduanya (bukan per IP saja atau per email saja).
     */
    private function throttleKey(string $email): string
    {
        return Str::lower($email) . '|' . request()->ip();
    }
}

