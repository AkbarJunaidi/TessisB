@extends('layouts.app')

@section('title', 'Approval')

@section('content')
<div class="container-fluid p-0">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-3" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Approval</h3>
        <p class="text-muted mb-0">Kotak masuk permintaan persetujuan lintas modul.</p>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-pending" type="button">Menunggu ({{ $pending->count() + $pendingPasswordResets->count() }})</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-riwayat" type="button">Riwayat</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-pending">

            {{-- "Lupa Password" adalah alur LAMA yang terpisah dari tabel
                 approval_requests (lihat komentar di ApprovalController) -
                 ditampilkan di sini, dan bisa langsung direset lewat modal
                 (memakai endpoint users.reset-password yang sama dengan
                 halaman Detail User) - khusus Super Admin. --}}
            @if($pendingPasswordResets->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-white fw-semibold small text-muted">Permintaan Lupa Password</div>
                    <div class="list-group list-group-flush">
                        @foreach($pendingPasswordResets as $pr)
                            <div class="list-group-item p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $pr->user->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $pr->email }} - {{ $pr->created_at->translatedFormat('d M Y H:i') }}</div>
                                </div>
                                @if($pr->user && auth()->user()->isSuperAdmin())
                                    <button type="button" class="btn btn-sm btn-primary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#resetPwModal{{ $pr->user->id }}">
                                        <i class="bi bi-key"></i> Reset Password
                                    </button>
                                @else
                                    <span class="small text-muted">Hanya Super Admin yang dapat reset password.</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-3">
                <div class="list-group list-group-flush">
                    @forelse($pending as $req)
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $req->display_title }}</div>
                                    <div class="text-muted small">{{ $req->display_detail }}</div>
                                    <div class="text-muted small mt-1">
                                        Diajukan oleh {{ $req->requestedBy->name ?? '-' }} - {{ $req->created_at->translatedFormat('d M Y H:i') }}
                                    </div>
                                    @if($req->reason)
                                        <div class="small mt-1"><span class="text-muted">Alasan:</span> {{ $req->reason }}</div>
                                    @endif
                                </div>
                                <div class="d-flex gap-2 flex-shrink-0">
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $req->id }}">
                                        <i class="bi bi-check-lg"></i> Setujui
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $req->id }}">
                                        <i class="bi bi-x-lg"></i> Tolak
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        @if($pendingPasswordResets->isEmpty())
                            <p class="text-center text-muted py-5 mb-0">Tidak ada permintaan approval yang menunggu.</p>
                        @endif
                    @endforelse
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-riwayat">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>Permintaan</th>
                                <th>Diajukan Oleh</th>
                                <th>Diputuskan Oleh</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $req)
                                <tr class="border-top">
                                    <td>
                                        <div class="fw-semibold">{{ $req->display_title }}</div>
                                        <div class="text-muted small">{{ $req->display_detail }}</div>
                                    </td>
                                    <td>{{ $req->requestedBy->name ?? '-' }}</td>
                                    <td>
                                        {{ $req->decidedBy->name ?? '-' }}
                                        <span class="text-muted small d-block">{{ $req->decided_at?->translatedFormat('d M Y H:i') }}</span>
                                    </td>
                                    <td><span class="badge {{ $req->status === 'approved' ? 'bg-success' : 'bg-secondary' }}">{{ $req->status === 'approved' ? 'Disetujui' : 'Ditolak' }}</span></td>
                                    <td>{{ $req->decision_note ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body">{{ $history->links() }}</div>
            </div>
        </div>
    </div>

    @foreach($pending as $req)
        <div class="modal fade" id="approveModal{{ $req->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('approval.approve', $req) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h6 class="modal-title">Setujui: {{ $req->display_title }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label small">Catatan (opsional)</label>
                            <textarea name="note" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Setujui</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="rejectModal{{ $req->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('approval.reject', $req) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h6 class="modal-title">Tolak: {{ $req->display_title }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label small">Catatan (opsional)</label>
                            <textarea name="note" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Tolak</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
    {{-- Modal Reset Password per user (khusus Super Admin). reset_user_id
         ikut ter-flash saat validasi gagal, dipakai script di bawah untuk
         membuka ulang modal yang sama. --}}
    @if(auth()->user()->isSuperAdmin())
        @foreach($pendingPasswordResets as $pr)
            @if($pr->user)
                <div class="modal fade" id="resetPwModal{{ $pr->user->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('users.reset-password', $pr->user) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="reset_user_id" value="{{ $pr->user->id }}">
                                <div class="modal-header">
                                    <h6 class="modal-title fw-bold">Reset Password - {{ $pr->user->name }}</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-secondary py-2 small mb-3">
                                        <div class="text-muted">Password saat ini (hasil reset terakhir):</div>
                                        @if($pr->user->temp_password_plain)
                                            <code>{{ $pr->user->temp_password_plain }}</code>
                                        @else
                                            <span class="text-muted">Belum pernah di-reset lewat sistem ini.</span>
                                        @endif
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Password Baru</label>
                                        <input type="password" name="password" class="form-control" minlength="8" required>
                                        <div class="form-text">Minimal 8 karakter.</div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Konfirmasi Password Baru</label>
                                        <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-repeat"></i> Reset Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach

        @if(old('reset_user_id'))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const el = document.getElementById('resetPwModal{{ (int) old('reset_user_id') }}');
                    if (el) new bootstrap.Modal(el).show();
                });
            </script>
        @endif
    @endif

</div>
@endsection
