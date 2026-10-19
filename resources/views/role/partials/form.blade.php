@php
    // $role, $permissionCatalog, $roleDefaults, $selected, $copySources dikirim dari RoleController.
    $baseRole = old('base_role', $role->base_role ?: 'employee');
@endphp

<div id="role-form" data-role-defaults="{{ json_encode($roleDefaults) }}">

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label for="name" class="form-label">Nama Role <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" maxlength="60" required
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $role->name) }}" placeholder="Mis. Finance, Gudang">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="description" class="form-label">Keterangan</label>
            <input type="text" name="description" id="description" maxlength="255"
                   class="form-control @error('description') is-invalid @enderror"
                   value="{{ old('description', $role->description) }}" placeholder="Opsional, mis. mengurus pembayaran dan keuangan">
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-4">
        <div class="form-label">Tingkat Dasar <span class="text-danger">*</span></div>
        <div class="row g-2">
            @foreach([
                'employee' => ['Staf (Employee)', 'Hanya melihat menu dan halaman yang izinnya dicentang di bawah.'],
                'admin'    => ['Setara Admin', 'Selain izin di bawah, juga melihat notifikasi otomatis dan kartu "Perlu perhatian" di dashboard. Cocok untuk Finance atau Kepala Gudang.'],
            ] as $value => [$title, $hint])
                <div class="col-md-6">
                    <label class="border rounded-3 p-3 d-flex gap-2 h-100 {{ $baseRole === $value ? 'border-primary bg-primary-subtle' : '' }}" for="base-{{ $value }}">
                        <input type="radio" class="form-check-input mt-1" name="base_role" id="base-{{ $value }}" value="{{ $value }}" @checked($baseRole === $value) required>
                        <span>
                            <span class="fw-semibold d-block">{{ $title }}</span>
                            <span class="small text-muted">{{ $hint }}</span>
                        </span>
                    </label>
                </div>
            @endforeach
        </div>
        @error('base_role')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div>
            <h6 class="fw-bold mb-1">Hak Akses</h6>
            <div class="small text-muted"><span id="role-total-active">0</span> izin aktif. Centang yang boleh dilakukan role ini.</div>
        </div>
        <div>
            <label for="copy-from" class="form-label small mb-1">Salin izin dari role lain</label>
            <select id="copy-from" class="form-select form-select-sm">
                <option value="">-- Pilih role --</option>
                @foreach($copySources as $slug => $label)
                    <option value="{{ $slug }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="accordion" id="accordionRolePermission">
        @foreach($permissionCatalog as $moduleKey => $module)
            <div class="accordion-item" data-module-group="{{ $moduleKey }}">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#role-module-{{ $moduleKey }}">
                        <i class="bi {{ $module['icon'] }} me-2"></i>
                        {{ $module['label'] }}
                        <span class="badge bg-light text-dark border ms-2 module-count">0 aktif</span>
                    </button>
                </h2>

                <div id="role-module-{{ $moduleKey }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}">
                    <div class="accordion-body">
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-toggle-module="{{ $moduleKey }}" data-state="all">Pilih semua</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle-module="{{ $moduleKey }}" data-state="none">Kosongkan</button>
                        </div>

                        <div class="row g-2">
                            @foreach($module['actions'] as $actionKey => $actionLabel)
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input role-permission"
                                               data-module="{{ $moduleKey }}" data-action="{{ $actionKey }}"
                                               name="permissions[{{ $moduleKey }}][{{ $actionKey }}]"
                                               id="role-perm-{{ $moduleKey }}-{{ $actionKey }}" value="1"
                                               @checked(old("permissions.{$moduleKey}.{$actionKey}", $selected[$moduleKey][$actionKey] ?? false))>
                                        <label class="form-check-label" for="role-perm-{{ $moduleKey }}-{{ $actionKey }}">
                                            {{ ucwords(str_replace('_', ' ', $actionKey)) }}
                                        </label>
                                        <div class="form-text small">{{ $actionLabel }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <p class="text-muted small mt-2">
        Izin ini berlaku untuk semua user yang memakai role ini. Izin satu user tetap bisa disesuaikan sendiri di halaman Edit User.
    </p>
</div>

<script src="{{ \App\Support\AppAsset::url('js/role/form.js') }}"></script>
