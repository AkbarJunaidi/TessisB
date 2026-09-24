@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="container-fluid px-4 py-3">

    @php
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
        $isAdminOrSuperAdmin = auth()->user()->hasRole('super_admin', 'admin');
        // Sekarang delegable ke Admin lewat Permission Override (lihat
        // config/permissions.php notifikasi_sistem.kirim) - TIDAK lagi
        // 1:1 sama $isSuperAdmin seperti sebelumnya.
        $canKirimPengumuman = auth()->user()->hasPermission('notifikasi_sistem', 'kirim');
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Notifikasi</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Notifikasi</li>
                </ol>
            </nav>
        </div>

        {{-- Tombol aktifkan Web Push - disembunyikan default lewat class
             d-none, dimunculkan JS di layouts/app.blade.php HANYA kalau
             browser mendukung Push API DAN server sudah setup VAPID key
             (config('webpush.vapid.public_key') tidak kosong). Kalau
             paket belum di-install sama sekali, tombol ini permanen
             tersembunyi - tidak ada yang rusak, fitur kirim pengumuman
             tetap jalan normal lewat channel database saja. --}}
        <button type="button" id="btnEnablePush" class="btn btn-sm btn-outline-primary fw-medium d-none">
            <i class="bi bi-bell me-1"></i>Aktifkan Notifikasi Browser
        </button>
    </div>

    <div class="row g-4">

        {{-- FORM KIRIM PENGUMUMAN - tampil kalau punya permission
             notifikasi_sistem.kirim (default cuma Super Admin, bisa
             didelegasikan ke Admin lewat Permission Override). Matriks
             izin per-user lain ("user A boleh X, user B tidak") di luar
             kirim/hapus masih belum didesain, belum ada di versi ini. --}}
        @if($canKirimPengumuman)
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-3 bg-white">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-megaphone me-2 text-primary"></i>Kirim Pengumuman</h6>

                        <form method="POST" action="{{ route('announcements.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="title" class="form-label small fw-semibold text-muted">Judul</label>
                                <input type="text" name="title" id="title" maxlength="150"
                                       class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title') }}" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label small fw-semibold text-muted">Isi Pengumuman</label>
                                <textarea name="message" id="message" rows="5" maxlength="2000"
                                          class="form-control @error('message') is-invalid @enderror"
                                          required>{{ old('message') }}</textarea>
                                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                                <i class="bi bi-send me-1"></i>Kirim ke Semua User Aktif
                            </button>
                            <p class="text-muted small mt-2 mb-0">
                                Terkirim ke semua user aktif.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- SEMUA NOTIFIKASI - gabungan Pengumuman (dulu bernama "Kotak
             Masuk", punya baris tersimpan, SEMUA role lihat punyanya
             sendiri) DAN Notifikasi Sistem otomatis (4 jenis dihitung
             dari data saat ini, HANYA tampil untuk Admin/Super Admin,
             ditaruh duluan di daftar - lihat NotificationService).
             Sematkan Notifikasi Sistem = preferensi pribadi, siapa saja
             boleh. Hapus Notifikasi Sistem = GLOBAL untuk semua user,
             tombol cuma muncul kalau $canDeleteSystemNotification
             (default hanya Super Admin, Admin lewat Permission Override). --}}
        <div class="{{ $canKirimPengumuman ? 'col-lg-8' : 'col-12' }}">
            <div class="card shadow-sm border-0 rounded-3 bg-white">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0">
                        <i class="bi bi-bell me-2 text-primary"></i>Semua Notifikasi
                    </h6>
                    @if($inbox->isNotEmpty())
                        <form method="POST" action="{{ route('announcements.read-all') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-link text-decoration-none">
                                Tandai semua dibaca
                            </button>
                        </form>
                    @endif
                </div>

                @if($inbox->isEmpty() && empty($systemNotifications))
                    <div class="card-body p-5 text-center text-muted">
                        <i class="bi bi-inbox" style="font-size: 2.5rem;"></i>
                        <p class="mt-2 mb-0">Belum ada notifikasi.</p>
                    </div>
                @else
                    <div class="list-group list-group-flush" id="notificationInboxList">
                        {{-- Notifikasi Sistem dulu (butuh tindakan admin -
                             reset password, deadline, dst), baru Pengumuman
                             di bawahnya. --}}
                        @if($isAdminOrSuperAdmin)
                            @foreach($systemNotifications as $item)
                                <div class="list-group-item p-3 system-notif-item" data-notif-key="{{ $item['id'] }}">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <a href="{{ $item['url'] }}" class="d-flex align-items-start gap-3 text-decoration-none text-reset flex-grow-1">
                                            <i class="bi {{ $item['icon'] }} mt-1"></i>
                                            <div>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($item['pinned'] ?? false)
                                                        <i class="bi bi-pin-angle-fill text-warning" title="Disematkan"></i>
                                                    @endif
                                                    <span class="fw-semibold">{{ $item['title'] }}</span>
                                                </div>
                                                <div class="text-secondary small">{{ $item['message'] }}</div>
                                                <div class="text-muted small mt-1">Notifikasi sistem (otomatis)</div>
                                            </div>
                                        </a>
                                        <div class="btn-group btn-group-sm flex-shrink-0">
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-toggle-system-pin"
                                                    data-pinned="{{ ($item['pinned'] ?? false) ? '1' : '0' }}"
                                                    title="{{ ($item['pinned'] ?? false) ? 'Lepas sematan' : 'Sematkan' }}">
                                                <i class="bi {{ ($item['pinned'] ?? false) ? 'bi-pin-angle-fill' : 'bi-pin-angle' }}"></i>
                                            </button>
                                            @if($canDeleteSystemNotification)
                                                <button type="button" class="btn btn-outline-danger btn-delete-system-notif" title="Hapus untuk semua user">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        @foreach($inbox as $item)
                            @php $isPinned = !is_null($item->pinned_at); @endphp
                            <div class="list-group-item p-3 notif-item {{ $item->read_at ? '' : 'bg-primary-subtle bg-opacity-10' }}"
                                 data-notif-id="{{ $item->id }}">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            @if(!$item->read_at)
                                                <span class="badge bg-primary rounded-pill" style="width:8px;height:8px;padding:0;"></span>
                                            @endif
                                            @if($isPinned)
                                                <i class="bi bi-pin-angle-fill text-warning" title="Disematkan"></i>
                                            @endif
                                            <span class="fw-semibold">{{ $item->data['title'] ?? '(Tanpa judul)' }}</span>
                                        </div>
                                        <div class="text-secondary small">{{ $item->data['message'] ?? '' }}</div>
                                        <div class="text-muted small mt-1">
                                            Dikirim oleh {{ $item->data['sent_by'] ?? 'Sistem' }} &middot;
                                            {{ $item->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column gap-1 flex-shrink-0 align-items-end">
                                        @if(!$item->read_at)
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-mark-read">
                                                Tandai dibaca
                                            </button>
                                        @endif
                                        <div class="btn-group btn-group-sm">
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-toggle-pin"
                                                    data-pinned="{{ $isPinned ? '1' : '0' }}"
                                                    title="{{ $isPinned ? 'Lepas sematan' : 'Sematkan' }}">
                                                <i class="bi {{ $isPinned ? 'bi-pin-angle-fill' : 'bi-pin-angle' }}"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-delete-notif" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($inbox->isNotEmpty())
                        <div class="card-body border-top">
                            {{ $inbox->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>

    </div>

    {{-- PANEL KELOLA NOTIFIKASI OTOMATIS - HANYA Super Admin --}}
    @if($isSuperAdmin)
        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold m-0"><i class="bi bi-sliders me-2 text-primary"></i>Kelola Notifikasi Otomatis</h6>
                        <p class="text-muted small mb-0 mt-1">
                            Aktif/nonaktifkan & atur urutan jenis notifikasi otomatis.
                        </p>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('notification-settings.update') }}" id="notifSettingsForm">
                            @csrf

                            <div class="list-group mb-3" id="notifTypeList">
                                @foreach($manageableTypes as $item)
                                    <div class="list-group-item d-flex align-items-center gap-3 py-3" data-type="{{ $item['type'] }}">
                                        <div class="d-flex flex-column gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-move-up py-0 px-1" title="Naikkan urutan">
                                                <i class="bi bi-chevron-up"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-move-down py-0 px-1" title="Turunkan urutan">
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                        </div>

                                        <div class="form-check form-switch flex-grow-1 m-0">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                   name="enabled_types[]" value="{{ $item['type'] }}"
                                                   id="notif-enable-{{ $item['type'] }}"
                                                   {{ $item['enabled'] ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="notif-enable-{{ $item['type'] }}">
                                                {{ $item['label'] }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Diisi ulang oleh JS tepat sebelum submit, sesuai urutan
                                 DOM terkini (setelah user pakai tombol naik/turun) --}}
                            <div id="orderedTypeInputs"></div>

                            <button type="submit" id="btnSaveNotifSettings" class="btn btn-primary fw-semibold">
                                <i class="bi bi-check-lg me-1"></i>Simpan Pengaturan
                            </button>
                            <span id="notifSettingsStatus" class="ms-2 small fw-semibold"></span>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
    // Konversi VAPID public key (base64url, dari server) ke Uint8Array -
    // format yang dibutuhkan PushManager.subscribe(). Boilerplate standar,
    // sama di semua implementasi Web Push (tidak spesifik TessisB).
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Tandai 1 notifikasi dibaca lewat AJAX (tanpa reload halaman) -
        // menyamarkan highlight biru begitu ditandai dibaca.
        document.querySelectorAll('.btn-mark-read').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const item = btn.closest('.notif-item');
                const id = item.dataset.notifId;

                fetch(`/announcements/${id}/read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                }).then(function (res) {
                    if (res.ok) {
                        item.classList.remove('bg-primary-subtle', 'bg-opacity-10');
                        btn.remove();
                        item.querySelector('.badge')?.remove();
                    }
                });
            });
        });

        // Sematkan / lepas sematan - toggle ikon di tombol itu sendiri,
        // tidak perlu reload/re-render seluruh daftar.
        document.querySelectorAll('.btn-toggle-pin').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const item = btn.closest('.notif-item');
                const id = item.dataset.notifId;
                const currentlyPinned = btn.dataset.pinned === '1';
                const action = currentlyPinned ? 'unpin' : 'pin';

                fetch(`/announcements/${id}/${action}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                }).then(function (res) { return res.json(); }).then(function (data) {
                    if (!data.success) return;

                    btn.dataset.pinned = currentlyPinned ? '0' : '1';
                    btn.title = currentlyPinned ? 'Sematkan' : 'Lepas sematan';
                    btn.querySelector('i').className = currentlyPinned ? 'bi bi-pin-angle' : 'bi bi-pin-angle-fill';
                });
            });
        });

        // Hapus notifikasi - konfirmasi dulu (aksi tidak bisa dibatalkan),
        // baru hilangkan barisnya dari tampilan begitu server konfirmasi sukses.
        document.querySelectorAll('.btn-delete-notif').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!confirm('Hapus notifikasi ini? Tindakan ini tidak bisa dibatalkan.')) {
                    return;
                }

                const item = btn.closest('.notif-item');
                const id = item.dataset.notifId;

                fetch(`/announcements/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                }).then(function (res) { return res.json(); }).then(function (data) {
                    if (data.success) {
                        item.remove();
                    }
                });
            });
        });

        // --- Notifikasi Sistem (otomatis) - pola sama persis dengan 2
        // handler Pengumuman di atas, cuma endpoint & data attribute-nya
        // beda (data-notif-key, bukan data-notif-id, dan URL-nya
        // /system-notifications/... bukan /announcements/...). Sengaja
        // ditulis sejajar (bukan digabung jadi 1 fungsi generik) supaya
        // gampang dibandingkan baris-per-baris kalau salah satunya nanti
        // perlu diubah - dua sumber data ini memang beda mekanisme
        // (lihat komentar di NotificationService/AnnouncementService).
        document.querySelectorAll('.btn-toggle-system-pin').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const item = btn.closest('.system-notif-item');
                const key = encodeURIComponent(item.dataset.notifKey);
                const currentlyPinned = btn.dataset.pinned === '1';
                const action = currentlyPinned ? 'unpin' : 'pin';

                fetch(`/system-notifications/${key}/${action}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                }).then(function (res) { return res.json(); }).then(function (data) {
                    if (!data.success) return;

                    btn.dataset.pinned = currentlyPinned ? '0' : '1';
                    btn.title = currentlyPinned ? 'Sematkan' : 'Lepas sematan';
                    btn.querySelector('i').className = currentlyPinned ? 'bi bi-pin-angle' : 'bi bi-pin-angle-fill';
                });
            });
        });

        document.querySelectorAll('.btn-delete-system-notif').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!confirm('Hapus notifikasi ini untuk SEMUA user? Bisa muncul lagi kalau kondisinya terjadi lagi di masa depan.')) {
                    return;
                }

                const item = btn.closest('.system-notif-item');
                const key = encodeURIComponent(item.dataset.notifKey);

                fetch(`/system-notifications/${key}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                }).then(function (res) { return res.json(); }).then(function (data) {
                    if (data.success) {
                        item.remove();
                    }
                });
            });
        });

        // --- Tombol "Aktifkan Notifikasi Browser" ---
        const vapidPublicKey = @json($vapidPublicKey);
        const btnEnablePush = document.getElementById('btnEnablePush');

        if (btnEnablePush) {
            const pushSupported = 'serviceWorker' in navigator && 'PushManager' in window;

            // Tombol HANYA dimunculkan kalau browser mendukung Push API DAN
            // server sudah punya VAPID key (paket webpush sudah di-setup) DAN
            // user belum pernah mengizinkan sebelumnya (Notification.permission
            // !== 'granted') - kalau sudah granted, tidak perlu tombol lagi.
            if (pushSupported && vapidPublicKey && Notification.permission !== 'granted') {
                btnEnablePush.classList.remove('d-none');
            }

            btnEnablePush.addEventListener('click', function () {
                Notification.requestPermission().then(function (permission) {
                    if (permission !== 'granted') {
                        return;
                    }

                    navigator.serviceWorker.ready.then(function (registration) {
                        registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
                        }).then(function (subscription) {
                            const rawKeys = subscription.toJSON();

                            fetch(@json(route('push-subscription.store')), {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken(),
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify(rawKeys),
                            }).then(function (res) {
                                if (res.ok) {
                                    btnEnablePush.classList.add('d-none');
                                }
                            });
                        });
                    });
                });
            });
        }

        // --- Panel Kelola Notifikasi Otomatis: reorder + submit AJAX ---
        const notifTypeList = document.getElementById('notifTypeList');
        const notifSettingsForm = document.getElementById('notifSettingsForm');
        const orderedTypeInputs = document.getElementById('orderedTypeInputs');

        if (notifTypeList && notifSettingsForm) {
            notifTypeList.addEventListener('click', function (e) {
                const upBtn = e.target.closest('.btn-move-up');
                const downBtn = e.target.closest('.btn-move-down');
                if (!upBtn && !downBtn) {
                    return;
                }

                const row = e.target.closest('.list-group-item');
                if (upBtn && row.previousElementSibling) {
                    notifTypeList.insertBefore(row, row.previousElementSibling);
                } else if (downBtn && row.nextElementSibling) {
                    notifTypeList.insertBefore(row.nextElementSibling, row);
                }
            });

            const btnSaveNotifSettings = document.getElementById('btnSaveNotifSettings');
            const notifSettingsStatus = document.getElementById('notifSettingsStatus');

            // Urutan DOM saat ini (setelah user pakai tombol naik/turun) ditulis
            // ke hidden input tepat sebelum submit, LALU dikirim lewat fetch
            // (bukan submit form native) - supaya halaman tidak reload.
            notifSettingsForm.addEventListener('submit', function (e) {
                e.preventDefault();

                orderedTypeInputs.innerHTML = '';
                notifTypeList.querySelectorAll('.list-group-item').forEach(function (row) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ordered_types[]';
                    input.value = row.dataset.type;
                    orderedTypeInputs.appendChild(input);
                });

                btnSaveNotifSettings.disabled = true;
                notifSettingsStatus.textContent = '';
                notifSettingsStatus.className = 'ms-2 small fw-semibold';

                fetch(notifSettingsForm.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    body: new FormData(notifSettingsForm),
                })
                    .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
                    .then(function (result) {
                        if (result.ok && result.data.success) {
                            notifSettingsStatus.textContent = '✓ ' + result.data.message;
                            notifSettingsStatus.classList.add('text-success');
                        } else {
                            notifSettingsStatus.textContent = result.data.message || 'Gagal menyimpan pengaturan.';
                            notifSettingsStatus.classList.add('text-danger');
                        }
                    })
                    .catch(function () {
                        notifSettingsStatus.textContent = 'Gagal menyimpan pengaturan (koneksi bermasalah).';
                        notifSettingsStatus.classList.add('text-danger');
                    })
                    .finally(function () {
                        btnSaveNotifSettings.disabled = false;
                        if (notifSettingsStatus.classList.contains('text-success')) {
                            setTimeout(function () { notifSettingsStatus.textContent = ''; }, 3000);
                        }
                    });
            });
        }
    });
</script>
@endpush
@endsection
