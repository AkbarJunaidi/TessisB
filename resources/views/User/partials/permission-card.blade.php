@php
    // $permissionCatalog, $roleDefaults, $effectivePermissions,
    // $permissionSummary, $hasCustomPermission dikirim dari UserController.
@endphp

<div
    id="permission-section"
    data-role-defaults="{{ json_encode($roleDefaults) }}"
    data-catalog="{{ json_encode(array_map(fn ($m) => array_keys($m['actions']), $permissionCatalog)) }}"
>

    <div class="alert alert-info d-flex flex-wrap align-items-center gap-4 mb-3">

        <i class="bi bi-info-circle fs-5"></i>

        <div class="me-auto">
            <div class="small">
                Role Bawaan
                <span id="summary-role-badge" class="badge bg-primary ms-1">
                    {{ ucwords(str_replace('_', ' ', old('role', $user->role ?? 'employee'))) }}
                </span>
            </div>
            <div class="small mt-1">
                Izin Kustom
                <span id="summary-custom-badge" class="badge {{ $hasCustomPermission ? 'bg-warning text-dark' : 'bg-secondary' }} ms-1">
                    {{ $hasCustomPermission ? 'Aktif' : 'Tidak' }}
                </span>
            </div>
        </div>

        <div class="d-flex gap-2">
            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-2">
                <span id="summary-granted">{{ $permissionSummary['granted'] }}</span> Diberikan
            </span>
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2">
                <span id="summary-readonly">{{ $permissionSummary['read_only'] }}</span> Hanya Baca
            </span>
            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle px-3 py-2">
                <span id="summary-noaccess">{{ $permissionSummary['no_access'] }}</span> Tidak Diakses
            </span>
        </div>

    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Hak Akses Pengguna</h6>

        <button
            type="button"
            id="btn-reset-permission"
            class="btn btn-outline-secondary btn-sm"
        >
            <i class="bi bi-arrow-counterclockwise me-1"></i>
            Reset ke Default Role
        </button>
    </div>

    <div class="accordion" id="accordionPermission">

        @foreach ($permissionCatalog as $moduleKey => $module)

            <div class="accordion-item">

                <h2 class="accordion-header">
                    <button
                        class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#module-{{ $moduleKey }}"
                    >
                        <i class="bi {{ $module['icon'] }} me-2"></i>
                        {{ $module['label'] }}
                        <span
                            class="badge bg-light text-dark border ms-2"
                            id="module-count-{{ $moduleKey }}"
                        >
                            0 aktif
                        </span>
                    </button>
                </h2>

                <div
                    id="module-{{ $moduleKey }}"
                    class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                    data-bs-parent="#accordionPermission"
                >
                    <div class="accordion-body">

                        <div class="row g-2">

                            @foreach ($module['actions'] as $actionKey => $actionLabel)

                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            class="form-check-input permission-checkbox"
                                            data-module="{{ $moduleKey }}"
                                            data-action="{{ $actionKey }}"
                                            name="permissions[{{ $moduleKey }}][{{ $actionKey }}]"
                                            id="perm-{{ $moduleKey }}-{{ $actionKey }}"
                                            value="1"
                                            @checked(old("permissions.{$moduleKey}.{$actionKey}", $effectivePermissions[$moduleKey][$actionKey] ?? false))
                                        >
                                        <label
                                            class="form-check-label"
                                            for="perm-{{ $moduleKey }}-{{ $actionKey }}"
                                        >
                                            {{ ucwords(str_replace('_', ' ', $actionKey)) }}
                                        </label>
                                        <div class="form-text small">
                                            {{ $actionLabel }}
                                        </div>
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
        Hak akses di atas berlaku khusus untuk user ini dan dapat meng-override hak akses default dari role.
    </p>

</div>

<script src="{{ \App\Support\AppAsset::url('js/user/partials/permission-card.js') }}"></script>
