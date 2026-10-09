<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login') - Management Information System</title>
    {{ \App\Support\VendorAsset::style('bootstrap-css') }}
    {{ \App\Support\VendorAsset::style('bootstrap-icons') }}
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
</head>
<body class="d-flex align-items-center py-4 u-minh-100vh u-bg-var--bg-page">

    <main class="container">
        @yield('content')
    </main>

    {{ \App\Support\VendorAsset::script('bootstrap-js') }}
</body>
</html>
