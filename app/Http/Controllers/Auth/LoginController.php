<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth; // Wajib di-import untuk membaca method check()
use Illuminate\View\View;

class LoginController extends Controller  
{
    /**
     * Menyuntikkan AuthService ke dalam Controller via Constructor Injection.
     */
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Menampilkan halaman login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        // PERBAIKAN: Menggunakan Facade Auth::check() agar terbaca sempurna oleh sistem & IDE
        if (Auth::check()) {
            return redirect()->intended('/dashboard');
        }

        return view('auth.login');
    }

    /**
     * Memproses kiriman request login.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        // Mengambil kiriman data yang hanya divalidasi oleh LoginRequest
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        // Panggil business logic yang berada di Service Layer
        $this->authService->login($credentials, $remember);

        // Sukses masuk, arahkan ke route dashboard terproteksi
        return redirect()->intended('/dashboard')->with('success', 'Selamat datang kembali!');
    }

    /**
     * Memproses request logout.
     */
    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect('/login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
