<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Management Information System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }  
        #wrapper {
            display: flex;
            width: 100vw;
            height: 100vh;
        }
        #sidebar-wrapper {
            min-width: 260px;
            max-width: 260px;
            background-color: #212529;
            transition: all 0.3s ease;
        }
        #page-content-wrapper {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .main-content {
            padding: 1.5rem;
            flex: 1;
        }
        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            #sidebar-wrapper {
                margin-left: -260px;
            }
            #sidebar-wrapper.toggled {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

    <div id="wrapper">
        <div id="sidebar-wrapper" id="sidebarComponent">
            @include('layouts.sidebar')
        </div>

        <div id="page-content-wrapper">
            @include('layouts.navbar')

            <main class="main-content">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Toast notifikasi global (auto-hilang, mengambang, tidak mendorong konten halaman).
         Dipakai di SELURUH halaman - jangan tambahkan alert session('success')/session('error')
         lokal lagi di masing-masing view, cukup andalkan ini. --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
        @if(session('success'))
            <div class="toast align-items-center text-bg-success border-0 shadow" role="alert" data-bs-autohide="true" data-bs-delay="4000" id="globalToastSuccess">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="toast align-items-center text-bg-danger border-0 shadow" role="alert" data-bs-autohide="true" data-bs-delay="6000" id="globalToastError">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const toggleBtn = document.getElementById("menu-toggle");
            if(toggleBtn) {
                toggleBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    document.getElementById("sidebar-wrapper").classList.toggle("toggled");
                });
            }

            // Tampilkan toast notifikasi global (kalau ada) - auto-hilang sendiri
            document.querySelectorAll('.toast-container .toast').forEach(function (toastEl) {
                const toast = new bootstrap.Toast(toastEl);
                toast.show();
            });
        });
    </script>
</body>
</html>
