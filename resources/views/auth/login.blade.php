<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,-apple-system,sans-serif}
        body{background-color:#f9fafb;color:#1f2937;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
        .container{display:flex;min-height:100vh}
        .left-side{display:none;width:50%;background:linear-gradient(to bottom right,#0d84fc,#035eb9,#0d84fc);justify-content:center;align-items:center;position:relative;overflow:hidden}
        @media (min-width:1024px){.left-side{display:flex}}
        .shape{position:absolute;width:24rem;height:24rem;border-radius:50%;mix-blend-mode:multiply;filter:blur(40px);opacity:.3}
        .shape-top{top:-10%;left:-10%;background-color:#3b82f6}
        .shape-bottom{bottom:-10%;right:-10%;background-color:#6366f1}
        .brand-content{position:relative;z-index:10;text-align:center;animation:fadeIn .6s ease-out forwards}
        .logo-box{display:inline-flex;align-items:center;justify-content:center;width:6rem;height:6rem;border-radius:50%;background-color:rgba(255,255,255,.1);backdrop-filter:blur(4px);box-shadow:0 20px 25px -5px rgba(0,0,0,.1),0 10px 10px -5px rgba(0,0,0,.04);margin-bottom:1.5rem;border:1px solid rgba(255,255,255,.2)}
        .logo-box svg{width:3rem;height:3rem;color:#fff}
        .brand-title{font-size:2.25rem;font-weight:800;color:#fff;letter-spacing:-.025em;margin-bottom:.5rem}
        .brand-subtitle{color:#bfdbfe;font-size:1.125rem;font-weight:500}
        .right-side{width:100%;display:flex;align-items:center;justify-content:center;padding:1.5rem}
        @media (min-width:640px){.right-side{padding:3rem}}
        @media (min-width:1024px){.right-side{width:50%}}
        .form-container{width:100%;max-width:28rem;animation:fadeIn .6s ease-out forwards;animation-delay:.2s;opacity:0}
        .form-header{margin-bottom:2.5rem;text-align:center}
        @media (min-width:1024px){.form-header{text-align:left}}
        .mobile-logo{display:inline-flex;align-items:center;justify-content:center;width:4rem;height:4rem;border-radius:50%;background-color:#dbeafe;color:#0d84fc;margin-bottom:1rem}
        @media (min-width:1024px){.mobile-logo{display:none}}
        .mobile-logo svg{width:2rem;height:2rem}
        .welcome-title{font-size:1.875rem;font-weight:700;color:#111827}
        .welcome-subtitle{color:#6b7280;margin-top:.5rem;font-size:.875rem}
        .input-group{margin-bottom:1.25rem}
        .label-wrapper{display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem}
        .input-label{display:block;font-size:.875rem;font-weight:600;color:#374151}
        .forgot-link{font-size:.875rem;font-weight:500;color:#0d84fc;text-decoration:none;transition:color .2s}
        .forgot-link:hover{color:#025eb9}
        .input-wrapper{position:relative}
        .input-icon-left{position:absolute;top:0;bottom:0;left:0;padding-left:.75rem;display:flex;align-items:center;pointer-events:none}
        .input-icon-left svg{height:1.25rem;width:1.25rem;color:#9ca3af}
        .input-field{display:block;width:100%;padding:.75rem .75rem .75rem 2.5rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.875rem;color:#1f2937;outline:none;transition:all .2s}
        .input-field:focus{border-color:#0d84fc;box-shadow:0 0 0 2px rgba(13,132,252,.2)}
        .input-field.has-right-icon{padding-right:2.5rem}
        .input-field.is-invalid{border-color:#ef4444}
        .input-field.is-invalid:focus{box-shadow:0 0 0 2px rgba(239,68,68,.2)}
        .input-icon-right{position:absolute;top:0;bottom:0;right:0;padding-right:.75rem;display:flex;align-items:center;cursor:pointer;color:#9ca3af}
        .input-icon-right:hover{color:#4b5563}
        .input-icon-right svg{height:1.25rem;width:1.25rem}
        .checkbox-group{display:flex;align-items:center;margin-bottom:1.5rem}
        .checkbox-input{height:1rem;width:1rem;cursor:pointer;accent-color:#0d84fc}
        .checkbox-label{margin-left:.5rem;display:block;font-size:.875rem;color:#4b5563;cursor:pointer}
        .btn-primary{width:100%;display:flex;justify-content:center;padding:.75rem 1rem;border:1px solid transparent;border-radius:.5rem;box-shadow:0 1px 2px 0 rgba(0,0,0,.05);font-size:.875rem;font-weight:700;color:#fff;background-color:#0d84fc;cursor:pointer;transition:all .2s}
        .btn-primary:hover{background-color:#025eb9;transform:translateY(-2px)}
        .btn-primary:focus{outline:none;box-shadow:0 0 0 2px #fff,0 0 0 4px #0d84fc}
        .form-footer{margin-top:2rem;text-align:center;font-size:.75rem;color:#9ca3af}
        .error-message{color:#ef4444;font-size:0.75rem;margin-top:0.375rem;display:block;font-weight:500}
        .alert-danger-custom{background-color:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:0.75rem 1rem;border-radius:0.5rem;font-size:0.875rem;margin-bottom:1.5rem;font-weight:500}
        @keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
    </style>
</head>
<body>

    <div class="container">

        <div class="left-side">
            <div class="shape shape-top"></div>
            <div class="shape shape-bottom"></div>

            <div class="brand-content">
                <div>
                    <img src="{{ asset('image/logo.png') }}" alt="logo" class="me-2" style="height: 180px; width: auto; object-fit: contain;">
                </div>
            </div>
        </div>

        <div class="right-side">
            <div class="form-container">

                <div class="form-header">
                    <div class="mobile-logo">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
                        </svg>
                    </div>
                    <h2 class="welcome-title">Selamat Datang</h2>
                    <p class="welcome-subtitle">Silakan masukkan akun kredensial Anda untuk masuk ke sistem manajemen.</p>
                </div>

                @if ($errors->any() && !$errors->has('email') && !$errors->has('password'))
                    <div class="alert-danger-custom">
                        Terjadi kesalahan saat memproses login Anda. Silakan coba lagi.
                    </div>
                @endif

                <form action="{{ route('login.store') }}" method="POST">
                    @csrf

                    <div class="input-group">
                        <div class="label-wrapper">
                            <label for="email" class="input-label">Alamat Email</label>
                        </div>
                        <div class="input-wrapper">
                            <div class="input-icon-left">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                class="input-field @error('email') is-invalid @enderror"
                                value="{{ old('email') }}"
                                placeholder="admin@perusahaan.com">
                        </div>
                        @error('email')
                            <span class="error-message" id="emailErrorMessage">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="input-group">
                        <div class="label-wrapper">
                            <label for="password" class="input-label">Password</label>
                            <a href="{{ route('forgot-password') }}" class="forgot-link">Lupa password?</a>
                        </div>
                        <div class="input-wrapper">
                            <div class="input-icon-left">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                class="input-field has-right-icon @error('password') is-invalid @enderror"
                                placeholder="••••••••">
                            <div class="input-icon-right" id="togglePassword">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" id="eyeIcon">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                        </div>
                        @error('password')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="checkbox-group">
                        <input id="remember" name="remember" type="checkbox" value="1" class="checkbox-input" {{ old('remember') ? 'checked' : '' }}>
                        <label for="remember" class="checkbox-label">
                            Ingat saya di perangkat ini
                        </label>
                    </div>

                    <div>
                        <button type="submit" class="btn-primary" id="loginSubmitBtn">
                            Masuk ke Dashboard
                        </button>
                    </div>
                </form>

                <p class="form-footer">
                    &copy; 2026 CV Arindra Production. Seluruh hak cipta dilindungi.
                </p>
            </div>
        </div>

    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.style.color = type === 'text' ? '#0d84fc' : '#9ca3af';
        });

        // Saat akun/perangkat sedang terkunci (rate limit login aktif),
        // form dikunci total dan dihitung mundur otomatis - bukan cuma
        // menampilkan teks statis sementara form tetap bisa disubmit.
        @if(session('lockout_seconds'))
            (function () {
                let remaining = {{ (int) session('lockout_seconds') }};

                const emailInput = document.getElementById('email');
                const passwordInput = document.getElementById('password');
                const rememberInput = document.getElementById('remember');
                const submitBtn = document.getElementById('loginSubmitBtn');
                const errorMessageEl = document.getElementById('emailErrorMessage');
                const originalPrefix = errorMessageEl
                    ? errorMessageEl.textContent.split('Silakan coba lagi')[0].trim()
                    : '';

                function lockForm() {
                    emailInput.disabled = true;
                    passwordInput.disabled = true;
                    rememberInput.disabled = true;
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.6';
                    submitBtn.style.cursor = 'not-allowed';
                }

                function unlockForm() {
                    emailInput.disabled = false;
                    passwordInput.disabled = false;
                    rememberInput.disabled = false;
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.style.cursor = 'pointer';
                }

                lockForm();

                const interval = setInterval(function () {
                    remaining--;

                    if (errorMessageEl) {
                        errorMessageEl.textContent = remaining > 0
                            ? `${originalPrefix} Silakan coba lagi dalam ${remaining} detik.`
                            : 'Anda sudah bisa mencoba login lagi.';
                    }

                    if (remaining <= 0) {
                        clearInterval(interval);
                        unlockForm();
                    }
                }, 1000);
            })();
        @endif
    </script>
</body>
</html>
