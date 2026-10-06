@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="container-fluid p-0">

    @php
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
        $isAdminOrSuperAdmin = auth()->user()->hasRole('super_admin', 'admin');
        // Sekarang delegable ke Admin lewat Permission Override (lihat
        // config/permissions.php notifikasi_sistem.kirim) - TIDAK lagi
        // 1:1 sama $isSuperAdmin seperti sebelumnya.
        $canKirimPengumuman = auth()->user()->hasPermission('notifikasi_sistem', 'kirim');
    @endphp

    <div class="page-heading">
        <h3>Notifikasi</h3>
        {{-- Disembunyikan; JS memunculkannya hanya jika browser dan server mendukung Web Push. --}}
        <button type="button" id="btnEnablePush" class="btn btn-sm btn-outline-primary d-none">
            <i class="bi bi-bell me-1"></i>Aktifkan notifikasi browser
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
                <div class="app-panel">
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
                                <i class="bi bi-send me-1"></i>Kirim ke semua user
                            </button>
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
            <div class="app-panel">
                <div class="app-panel-header">
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
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                        <p class="mb-0">Belum ada notifikasi.</p>
                    </div>
                @else
                    <div class="d-flex gap-2 flex-wrap px-3 py-2 border-bottom" id="notifFilters" role="group" aria-label="Filter notifikasi">
                        <button type="button" class="btn btn-sm btn-primary" data-filter="all">Semua</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-filter="important">Penting</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-filter="unread">Belum dibaca</button>
                    </div>
                    <div class="list-group list-group-flush" id="notificationInboxList">
                        {{-- Notifikasi Sistem dulu (butuh tindakan admin -
                             reset password, deadline, dst), baru Pengumuman
                             di bawahnya. --}}
                        @if($isAdminOrSuperAdmin)
                            @foreach($systemNotifications as $item)
                                <div class="list-group-item p-3 system-notif-item" data-notif-key="{{ $item['id'] }}" data-important="1" data-unread="0">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <a href="{{ $item['url'] }}" class="d-flex align-items-start gap-3 text-decoration-none text-reset flex-grow-1">
                                            <i class="bi {{ $item['icon'] }} mt-1"></i>
                                            <div>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($item['pinned'] ?? false)
                                                        <i class="bi bi-pin-angle-fill text-primary" title="Disematkan"></i>
                                                    @endif
                                                    <span class="fw-semibold">{{ $item['title'] }}</span>
                                                    <span class="badge-soft-primary">Penting</span>
                                                </div>
                                                <div class="text-secondary small">{{ $item['message'] }}</div>
                                            </div>
                                        </a>
                                        <div class="btn-group btn-group-sm flex-shrink-0">
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-toggle-system-pin" aria-label="Sematkan atau lepas sematan"
                                                    data-pinned="{{ ($item['pinned'] ?? false) ? '1' : '0' }}"
                                                    title="{{ ($item['pinned'] ?? false) ? 'Lepas sematan' : 'Sematkan' }}">
                                                <i class="bi {{ ($item['pinned'] ?? false) ? 'bi-pin-angle-fill' : 'bi-pin-angle' }}"></i>
                                            </button>
                                            @if($canDeleteSystemNotification)
                                                <button type="button" class="btn btn-outline-danger btn-delete-system-notif" title="Hapus untuk semua user" aria-label="Hapus untuk semua user">
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
                            <div class="list-group-item p-3 notif-item {{ $item->read_at ? '' : 'notif-unread' }}"
                                 data-notif-id="{{ $item->id }}" data-important="{{ $isPinned ? '1' : '0' }}" data-unread="{{ $item->read_at ? '0' : '1' }}">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            @if(!$item->read_at)
                                                <span class="notif-dot" role="img" aria-label="Belum dibaca"></span>
                                            @endif
                                            @if($isPinned)
                                                <i class="bi bi-pin-angle-fill text-primary" title="Disematkan"></i>
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
                                                    class="btn btn-outline-secondary btn-toggle-pin" aria-label="Sematkan atau lepas sematan"
                                                    data-pinned="{{ $isPinned ? '1' : '0' }}"
                                                    title="{{ $isPinned ? 'Lepas sematan' : 'Sematkan' }}">
                                                <i class="bi {{ $isPinned ? 'bi-pin-angle-fill' : 'bi-pin-angle' }}"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-delete-notif" title="Hapus" aria-label="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="notifFilterEmpty" class="empty-state d-none">
                        <p class="mb-0">Tidak ada notifikasi.</p>
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
                <div class="app-panel">
                    <div class="app-panel-header">
                        <h6 class="fw-bold m-0"><i class="bi bi-sliders me-2 text-primary"></i>Kelola Notifikasi Otomatis</h6>
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
        // Filter daftar di sisi browser: Semua / Penting / Belum dibaca.
        let activeFilter = 'all';
        const filterBox = document.getElementById('notifFilters');

        function applyFilter() {
            const items = document.querySelectorAll('#notificationInboxList > [data-important]');
            let shown = 0;
            items.forEach(function (el) {
                const ok = activeFilter === 'all'
                    || (activeFilter === 'important' && el.dataset.important === '1')
                    || (activeFilter === 'unread' && el.dataset.unread === '1');
                el.classList.toggle('d-none', !ok);
                if (ok) shown++;
            });
            const empty = document.getElementById('notifFilterEmpty');
            if (empty) empty.classList.toggle('d-none', shown > 0);
        }

        if (filterBox) {
            filterBox.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-filter]');
                if (!btn) return;
                activeFilter = btn.dataset.filter;
                filterBox.querySelectorAll('[data-filter]').forEach(function (b) {
                    const on = b === btn;
                    b.classList.toggle('btn-primary', on);
                    b.classList.toggle('btn-outline-primary', !on);
                });
                applyFilter();
            });
        }

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
                        item.classList.remove('notif-unread');
                        item.dataset.unread = '0';
                        btn.remove();
                        item.querySelector('.notif-dot')?.remove();
                        applyFilter();
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
                    item.dataset.important = currentlyPinned ? '0' : '1';
                    applyFilter();
                });
            });
        });

        // Hapus notifikasi: konfirmasi dulu, baris hilang setelah server sukses.
        document.querySelectorAll('.btn-delete-notif').forEach(function (btn) {
            btn.addEventListener('click', function () {
                AppUI.confirm('Hapus notifikasi ini?', { label: 'Hapus', danger: true }).then(function (ok) {
                    if (!ok) return;

                    const item = btn.closest('.notif-item');
                    const id = item.dataset.notifId;

                    fetch(`/announcements/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    }).then(function (res) { return res.json(); }).then(function (data) {
                        if (data.success) {
                            item.remove();
                            applyFilter();
                        }
                    });
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
                AppUI.confirm('Hapus untuk semua user? Bisa muncul lagi jika kondisinya terjadi lagi.', { label: 'Hapus', danger: true }).then(function (ok) {
                    if (!ok) return;

                    const item = btn.closest('.system-notif-item');
                    const key = encodeURIComponent(item.dataset.notifKey);

                    fetch(`/system-notifications/${key}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    }).then(function (res) { return res.json(); }).then(function (data) {
                        if (data.success) {
                            item.remove();
                            applyFilter();
                        }
                    });
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
                            notifSettingsStatus.textContent = result.data.message;
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
