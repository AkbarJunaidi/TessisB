<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') - @yield('title')</title>
    {{ \App\Support\VendorAsset::style('bootstrap-css') }}
    {{ \App\Support\VendorAsset::style('bootstrap-icons') }}
    <link href="{{ \App\Support\AppAsset::url('css/theme.css') }}" rel="stylesheet">
    <link href="{{ \App\Support\AppAsset::url('css/errors.css') }}" rel="stylesheet">
</head>
<body class="error-page">

    {{-- Layout mandiri (tanpa query/sesi) agar tetap tampil walau error berasal dari aplikasi. --}}
    <main class="error-card" role="main">

        <div class="error-icon error-icon--@yield('tone', 'danger')" aria-hidden="true">
            <i class="bi bi-@yield('icon') error-anim--@yield('anim', 'shake')"></i>
        </div>

        <div class="error-code">@yield('code')</div>
        <h1 class="error-title">@yield('title')</h1>
        <p class="error-message">@yield('message')</p>

        <div class="error-actions">
            <button type="button" class="btn btn-outline-secondary" id="errorBack">
                <i class="bi bi-arrow-left me-2"></i>Kembali
            </button>
            @hasSection('reload')
                <button type="button" class="btn btn-outline-primary" id="errorReload">
                    <i class="bi bi-arrow-clockwise me-2"></i>Muat Ulang
                </button>
            @endif
            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="bi bi-house me-2"></i>Ke Beranda
            </a>
        </div>

    </main>

    <script>
        document.getElementById('errorBack').addEventListener('click', function () {
            if (history.length > 1) { history.back(); } else { location.href = '{{ url('/') }}'; }
        });
        var reloadBtn = document.getElementById('errorReload');
        if (reloadBtn) { reloadBtn.addEventListener('click', function () { location.reload(); }); }
    </script>
</body>
</html>
