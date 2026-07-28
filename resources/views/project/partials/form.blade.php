@php
    $old = fn ($field, $default = null) => old($field, $project?->{$field} ?? $default);
    $categories = ['Wedding', 'Corporate', 'Graduation', 'Live Streaming', 'Product Launch', 'Lainnya'];
@endphp

<div class="row g-3">

    <div class="col-md-8">
        <label for="name" class="form-label fw-semibold small text-secondary">Nama Project <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name"
               class="form-control @error('name') is-invalid @enderror"
               placeholder="Contoh: Wedding Arnold & Gita"
               value="{{ $old('name') }}" required autofocus>
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

    <div class="col-md-6">
        <label for="client" class="form-label fw-semibold small text-secondary">Client <span class="text-danger">*</span></label>
        <input type="text" name="client" id="client"
               class="form-control @error('client') is-invalid @enderror"
               placeholder="Nama client / instansi"
               value="{{ $old('client') }}" required>
        @error('client')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
        <label for="event_date" class="form-label fw-semibold small text-secondary">Tanggal Acara <span class="text-danger">*</span></label>
        <input type="date" name="event_date" id="event_date"
               class="form-control @error('event_date') is-invalid @enderror"
               value="{{ $old('event_date') }}" required>
        @error('event_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="event_time_start" class="form-label fw-semibold small text-secondary">Jam Mulai <span class="text-danger">*</span></label>
        <input type="time" name="event_time_start" id="event_time_start"
               class="form-control @error('event_time_start') is-invalid @enderror"
               value="{{ $old('event_time_start') }}" required>
        @error('event_time_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="event_time_end" class="form-label fw-semibold small text-secondary">Jam Selesai</label>
        <input type="time" name="event_time_end" id="event_time_end"
               class="form-control @error('event_time_end') is-invalid @enderror"
               value="{{ $old('event_time_end') }}">
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

    <div class="col-12">
        <label for="description" class="form-label fw-semibold small text-secondary">Deskripsi Project</label>
        <textarea name="description" id="description" rows="4"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Jelaskan ringkasan project ini...">{{ $old('description') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

</div>
