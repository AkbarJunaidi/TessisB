@php
    $old = fn ($field, $default = null) => old($field, $project?->{$field} ?? $default);

    // HTML <input type="date"> butuh format persis "Y-m-d". Kolom event_date di-cast
    // Carbon, jadi kalau di-echo langsung hasilnya "Y-m-d H:i:s" dan browser gagal isi otomatis.
    $oldDate = function ($field) use ($project) {
        $value = old($field, $project?->{$field});
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '';
    };

    // HTML <input type="time"> hanya boleh "H:i" (tanpa detik), kalau ada detik browser
    // otomatis memunculkan kolom detik tambahan sehingga terlihat "18.00.00".
    $oldTime = function ($field) use ($project) {
        $value = old($field, $project?->{$field});
        return $value ? \Carbon\Carbon::parse($value)->format('H:i') : '';
    };

    $categories = ['Wedding', 'Corporate', 'Graduation', 'Live Streaming', 'Product Launch', 'Lainnya'];
@endphp

<div class="row g-3">

    <div class="col-md-8">
        <label for="name" class="form-label fw-semibold small text-secondary">Nama Project <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" autocomplete="off"
               class="form-control @error('name') is-invalid @enderror"
               placeholder="Contoh: Wedding Arnold & Gita"
               value="{{ $old('name') }}" required autofocus maxlength="100">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="category" class="form-label fw-semibold small text-secondary">Kategori <span class="text-danger">*</span></label>
        <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
            <option value="">Pilih Kategori</option>
            @foreach($categories as $cat)
                <option value="{{ $cat }}" @selected($old('category') === $cat)>{{ $cat }}</option>
            @endforeach
        </select>
        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 position-relative">
        <label for="client" class="form-label fw-semibold small text-secondary">Client <span class="text-danger">*</span></label>
        <input type="text" name="client" id="client" autocomplete="off"
               class="form-control @error('client') is-invalid @enderror"
               placeholder="Nama client / instansi"
               value="{{ $old('client') }}" required>
        <input type="hidden" name="contact_id" id="contact_id" value="{{ $old('contact_id') }}">
        @error('client')<div class="invalid-feedback">{{ $message }}</div>@enderror

        {{-- Ditampilkan kalau Client sedang terhubung ke data Kontak (lihat
             app/Models/Contact.php - projects()/matchedProjects()) --}}
        <small id="clientLinkStatus" class="text-success d-none">
            <i class="bi bi-link-45deg"></i> Terhubung ke data Kontak
        </small>

        {{-- Dropdown saran autocomplete, muncul saat mengetik min. 2 huruf --}}
        <div id="clientSuggestions" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1050; top: 100%;"></div>
    </div>

    <div class="col-md-6">
        <label for="pic" class="form-label fw-semibold small text-secondary">PIC <span class="text-danger">*</span></label>
        <input type="text" name="pic" id="pic"
               class="form-control @error('pic') is-invalid @enderror"
               placeholder="Penanggung jawab project"
               value="{{ $old('pic') }}" required>
        @error('pic')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="company" class="form-label fw-semibold small text-secondary">Nama Perusahaan</label>
        <input type="text" name="company" id="company"
               class="form-control @error('company') is-invalid @enderror"
               placeholder="Contoh: PT Matahari Indonesia Jaya Abadi"
               value="{{ $old('company') }}">
        @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="email" class="form-label fw-semibold small text-secondary">Email</label>
        <input type="email" name="email" id="email"
               class="form-control @error('email') is-invalid @enderror"
               placeholder="Contoh: client@email.com"
               value="{{ $old('email') }}">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="phone" class="form-label fw-semibold small text-secondary">No. Telepon</label>
        <input type="text" name="phone" id="phone"
               class="form-control @error('phone') is-invalid @enderror"
               placeholder="Contoh: 081234567890"
               value="{{ $old('phone') }}">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="event_date" class="form-label fw-semibold small text-secondary">Tanggal Acara Mulai <span class="text-danger">*</span></label>
        <input type="date" name="event_date" id="event_date"
               class="form-control @error('event_date') is-invalid @enderror"
               value="{{ $oldDate('event_date') }}" required>
        @error('event_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="event_end_date" class="form-label fw-semibold small text-secondary">Tanggal Acara Selesai</label>
        <input type="date" name="event_end_date" id="event_end_date"
               class="form-control @error('event_end_date') is-invalid @enderror"
               value="{{ $oldDate('event_end_date') }}">
        <div class="form-text">Kosongkan jika acara berlangsung 1 hari saja.</div>
        @error('event_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="event_time_start" class="form-label fw-semibold small text-secondary">Jam Mulai <span class="text-danger">*</span></label>
        <input type="time" name="event_time_start" id="event_time_start"
               class="form-control @error('event_time_start') is-invalid @enderror"
               value="{{ $oldTime('event_time_start') }}" required>
        @error('event_time_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="event_time_end" class="form-label fw-semibold small text-secondary">Jam Selesai</label>
        <input type="time" name="event_time_end" id="event_time_end"
               class="form-control @error('event_time_end') is-invalid @enderror"
               value="{{ $oldTime('event_time_end') }}">
        @error('event_time_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="location" class="form-label fw-semibold small text-secondary">Lokasi / Venue <span class="text-danger">*</span></label>
        <input type="text" name="location" id="location"
               class="form-control @error('location') is-invalid @enderror"
               placeholder="Contoh: Arca Cottages & Resort"
               value="{{ $old('location') }}" required>
        @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="estimated_duration_minutes" class="form-label fw-semibold small text-secondary">Estimasi Durasi (menit) <span class="text-danger">*</span></label>
        <input type="number" min="1" name="estimated_duration_minutes" id="estimated_duration_minutes"
               class="form-control @error('estimated_duration_minutes') is-invalid @enderror"
               placeholder="Contoh: 840 (=14 jam)"
               value="{{ $old('estimated_duration_minutes') }}" required>
        @error('estimated_duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="address" class="form-label fw-semibold small text-secondary">Alamat Lengkap <span class="text-danger">*</span></label>
        <textarea name="address" id="address" rows="2"
                  class="form-control @error('address') is-invalid @enderror"
                  placeholder="Alamat lengkap lokasi acara">{{ $old('address') }}</textarea>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="priority" class="form-label fw-semibold small text-secondary">Prioritas <span class="text-danger">*</span></label>
        <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
            @foreach(['Rendah', 'Normal', 'Tinggi'] as $p)
                <option value="{{ $p }}" @selected($old('priority', 'Normal') === $p)>{{ $p }}</option>
            @endforeach
        </select>
        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="estimated_value" class="form-label fw-semibold small text-secondary">Estimasi Pendapatan (Rp)</label>
        <input type="number" min="0" step="1000" name="estimated_value" id="estimated_value"
               class="form-control @error('estimated_value') is-invalid @enderror"
               placeholder="Contoh: 15000000"
               value="{{ $old('estimated_value') }}">
        <small class="text-muted">Estimasi uang yang akan didapat dari project ini. Tampil di Data Keuangan &amp; Pipeline, tidak dihitung di laporan keuangan bulanan.</small>
        @error('estimated_value')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label fw-semibold small text-secondary">Deskripsi Project</label>
        <textarea name="description" id="description" rows="4"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Jelaskan ringkasan project ini...">{{ $old('description') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

</div>

<script>
    // Tanggal Acara Selesai tidak boleh lebih awal dari Tanggal Acara Mulai -
    // batas "min" disinkronkan di sisi client supaya user tidak salah pilih
    // (validasi sesungguhnya tetap di server lewat ProjectRequest).
    document.addEventListener('DOMContentLoaded', function () {
        const startInput = document.getElementById('event_date');
        const endInput   = document.getElementById('event_end_date');

        if (!startInput || !endInput) {
            return;
        }

        function syncEndDateMin() {
            if (!startInput.value) {
                return;
            }

            endInput.min = startInput.value;

            if (endInput.value && endInput.value < startInput.value) {
                endInput.value = startInput.value;
            }
        }

        syncEndDateMin();
        startInput.addEventListener('change', syncEndDateMin);
    });
</script>

<script>
    // Autocomplete field Client - cari dari daftar Kontak (lihat
    // ContactController::search()). Kalau user pilih salah satu saran,
    // field Client/Email/No. Telepon otomatis terisi DAN project ini
    // benar-benar ter-link ke Kontak itu (contact_id, bukan cuma tebak
    // nama - lihat Contact::matchedProjects() & ContactService::
    // attachTotalIncome() yang sekarang mengutamakan link ini).
    document.addEventListener('DOMContentLoaded', function () {
        const clientInput   = document.getElementById('client');
        const contactIdInput = document.getElementById('contact_id');
        const emailInput    = document.getElementById('email');
        const phoneInput    = document.getElementById('phone');
        const suggestionBox = document.getElementById('clientSuggestions');
        const linkStatus    = document.getElementById('clientLinkStatus');

        if (!clientInput || !suggestionBox) {
            return;
        }

        const searchUrl = @json(route('contacts.search'));

        let debounceTimer = null;
        let lastSelectedName = clientInput.value; // isi awal (mode Edit) dianggap "sudah sesuai"

        // Mode Edit: kalau project ini sudah punya contact_id dari awal,
        // langsung tampilkan badge terhubung tanpa perlu action apapun.
        if (contactIdInput.value) {
            linkStatus.classList.remove('d-none');
        }

        function hideSuggestions() {
            suggestionBox.classList.add('d-none');
            suggestionBox.innerHTML = '';
        }

        function renderSuggestions(contacts) {
            if (contacts.length === 0) {
                hideSuggestions();
                return;
            }

            suggestionBox.innerHTML = contacts.map((c) => `
                <button type="button" class="list-group-item list-group-item-action py-2 client-suggestion-item"
                        data-id="${c.id}" data-name="${escapeHtml(c.name)}"
                        data-phone="${escapeHtml(c.phone ?? '')}" data-email="${escapeHtml(c.email ?? '')}">
                    <div class="fw-semibold">${escapeHtml(c.name)}</div>
                    <div class="small text-muted">${escapeHtml(c.phone ?? '-')}${c.email ? ' &middot; ' + escapeHtml(c.email) : ''}</div>
                </button>
            `).join('');

            suggestionBox.classList.remove('d-none');

            suggestionBox.querySelectorAll('.client-suggestion-item').forEach((btn) => {
                btn.addEventListener('click', function () {
                    clientInput.value = this.dataset.name;
                    contactIdInput.value = this.dataset.id;
                    lastSelectedName = this.dataset.name;

                    if (emailInput && !emailInput.value) {
                        emailInput.value = this.dataset.email || '';
                    }
                    if (phoneInput && !phoneInput.value) {
                        phoneInput.value = this.dataset.phone || '';
                    }

                    linkStatus.classList.remove('d-none');
                    hideSuggestions();
                });
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text ?? '';
            return div.innerHTML;
        }

        clientInput.addEventListener('input', function () {
            const keyword = this.value.trim();

            // Nama diketik ulang beda dari yang terakhir dipilih -> link lama
            // sudah tidak relevan lagi, lepas (biar tidak salah nyambung ke
            // Kontak yang sebenarnya beda).
            if (keyword !== lastSelectedName) {
                contactIdInput.value = '';
                linkStatus.classList.add('d-none');
            }

            clearTimeout(debounceTimer);

            if (keyword.length < 2) {
                hideSuggestions();
                return;
            }

            debounceTimer = setTimeout(function () {
                fetch(`${searchUrl}?q=${encodeURIComponent(keyword)}`, {
                    headers: { 'Accept': 'application/json' },
                })
                    .then((res) => res.json())
                    .then((data) => renderSuggestions(data.contacts || []))
                    .catch(() => hideSuggestions());
            }, 300);
        });

        // Klik di luar input/dropdown -> tutup saran
        document.addEventListener('click', function (e) {
            if (!clientInput.contains(e.target) && !suggestionBox.contains(e.target)) {
                hideSuggestions();
            }
        });
    });
</script>
