@csrf

<div class="row">

    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">
            Nama Client <span class="text-danger">*</span>
        </label>

        <input
            type="text"
            name="name"
            id="name"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $contact->name ?? '') }}"
            placeholder="Contoh: Budi Santoso"
            required
        >

        @error('name')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="company" class="form-label">
            Nama Perusahaan
        </label>

        <input
            type="text"
            name="company"
            id="company"
            class="form-control @error('company') is-invalid @enderror"
            value="{{ old('company', $contact->company ?? '') }}"
            placeholder="Contoh: PT Matahari Indonesia Jaya Abadi"
        >

        @error('company')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

</div>

<div class="row">

    <div class="col-md-6 mb-3">
        <label for="phone" class="form-label">
            No. HP/WA <span class="text-danger">*</span>
        </label>

        <input
            type="text"
            name="phone"
            id="phone"
            class="form-control @error('phone') is-invalid @enderror"
            value="{{ old('phone', $contact->phone ?? '') }}"
            placeholder="Contoh: 081234567890"
            required
        >

        @error('phone')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">
            Email
        </label>

        <input
            type="email"
            name="email"
            id="email"
            class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $contact->email ?? '') }}"
            placeholder="Contoh: client@email.com"
        >

        @error('email')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

</div>

<div class="row">

    <div class="col-12 mb-3">
        <label for="address" class="form-label">
            Alamat
        </label>

        <textarea
            name="address"
            id="address"
            rows="3"
            class="form-control @error('address') is-invalid @enderror"
            placeholder="Alamat lengkap client"
        >{{ old('address', $contact->address ?? '') }}</textarea>

        @error('address')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">
        Batal
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i> Simpan
    </button>
</div>
