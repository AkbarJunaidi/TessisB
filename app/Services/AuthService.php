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
     * Lama penguncian (detik) setelah batas percobaan gagal tercapai -
     * untuk lapisan email + IP.
     */
    private const LOCKOUT_DECAY_SECONDS = 60;

    /**
     * Batas percobaan login gagal per IP saja (tanpa peduli email yang
     * dicoba) - lapisan kedua untuk menangkap serangan yang sengaja
     * berganti-ganti email dari 1 sumber yang sama, yang tidak akan
     * terjaring oleh limiter email + IP di atas.
     */
    private const MAX_IP_ATTEMPTS = 10;

    /**
     * Lama penguncian (detik) untuk lapisan IP saja - 1 menit, sama seperti
     * jendela waktu yang sebelumnya dipakai di middleware `throttle:10,1`
     * pada route (sekarang digantikan sepenuhnya oleh limiter ini, supaya
     * pesan errornya konsisten dengan lapisan email + IP, bukan halaman
     * error 429 generik bawaan Laravel).
     */
    private const IP_LOCKOUT_DECAY_SECONDS = 60;

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
     * Dilindungi rate limiting 2 lapis:
     * 1. Per IP saja (lebih longgar) - mencegah serangan yang berganti-ganti
     *    email dari 1 sumber yang sama.
     * 2. Per kombinasi email + IP (lebih ketat) - mencegah brute force ke
     *    1 akun spesifik.
     *
     * @param array $credentials
     * @param bool $remember
     * @return bool
     *
     * @throws ValidationException
     */
    public function login(array $credentials, bool $remember = false): bool
    {
        $ipKey = $this->ipThrottleKey();
        $emailIpKey = $this->throttleKey($credentials['email'] ?? '');

        // Lapisan 1: IP saja - dicek lebih dulu karena sifatnya lebih umum.
        if (RateLimiter::tooManyAttempts($ipKey, self::MAX_IP_ATTEMPTS)) {
            $this->throwLockoutException($ipKey, 'Terlalu banyak percobaan login dari perangkat ini.');
        }

        // Lapisan 2: kombinasi email + IP.
        if (RateLimiter::tooManyAttempts($emailIpKey, self::MAX_LOGIN_ATTEMPTS)) {
            $this->throwLockoutException($emailIpKey, 'Terlalu banyak percobaan login.');
        }

        // Verifikasi email dan password
        if (!Auth::attempt($credentials, $remember)) {

            RateLimiter::hit($ipKey, self::IP_LOCKOUT_DECAY_SECONDS);
            RateLimiter::hit($emailIpKey, self::LOCKOUT_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => [
                    'Email atau password yang Anda masukkan salah.',
                ],
            ]);
        }

        // Login berhasil - hapus catatan percobaan gagal sebelumnya (kedua lapisan).
        RateLimiter::clear($ipKey);
        RateLimiter::clear($emailIpKey);

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

    /**
     * Membentuk key rate limiter dari IP saja - lapisan kedua yang tidak
     * peduli email apa yang dicoba.
     */
    private function ipThrottleKey(): string
    {
        return 'login-ip|' . request()->ip();
    }

    /**
     * Melempar error validasi untuk kondisi terkunci, sekaligus menyimpan
     * sisa detiknya secara terpisah ke session (di luar pesan teks) supaya
     * halaman login bisa menghitung mundur secara live dan mengunci form
     * sampai waktunya habis - bukan cuma menampilkan teks statis sementara
     * form tetap bisa disubmit berulang kali.
     *
     * @throws ValidationException
     */
    private function throwLockoutException(string $throttleKey, string $prefix): never
    {
        $seconds = RateLimiter::availableIn($throttleKey);

        session()->flash('lockout_seconds', $seconds);

        throw ValidationException::withMessages([
            'email' => [
                "{$prefix} Silakan coba lagi dalam {$seconds} detik.",
            ],
        ]);
    }
}

