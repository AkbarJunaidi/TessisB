{{-- Kartu filter Log Aktivitas, dipakai activity-log/index. --}}
<div class="card filter-card mb-4">
    <div class="card-body">
        <form action="{{ route('activity-logs.index') }}" method="GET">
            <div class="row g-3">
                <div class="col-12">
                    <label for="logSearch" class="form-label">Cari</label>
                    <div class="search-field">
                        <i class="bi bi-search"></i>
                        <input type="text" id="logSearch" name="search"
                               class="form-control @error('search') is-invalid @enderror"
                               placeholder="Cari user, modul, aksi..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label for="logModule" class="form-label">Modul</label>
                    <select id="logModule" name="module" class="form-select @error('module') is-invalid @enderror">
                        <option value="">Semua Modul</option>
                        {{-- $modules bisa asosiatif: $value => $label --}}
                        @foreach($modules as $value => $label)
                            <option value="{{ $value }}" @selected(request('module') == $value)>{{ \App\Support\LogLabel::module($label) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label for="logUser" class="form-label">User</label>
                    <select id="logUser" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                        <option value="">Semua User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label for="logAction" class="form-label">Aksi</label>
                    <select id="logAction" name="action" class="form-select @error('action') is-invalid @enderror">
                        <option value="">Semua Aksi</option>
                        @foreach($actions as $group => $items)
                            <optgroup label="{{ $group }}">
                                @foreach($items as $action)
                                    <option value="{{ $action }}" @selected(request('action') == $action)>{{ \App\Support\LogLabel::action($action) }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label for="logDateFrom" class="form-label">Dari Tanggal</label>
                    <input type="date" id="logDateFrom" name="date_from"
                           class="form-control @error('date_from') is-invalid @enderror" value="{{ request('date_from') }}">
                </div>

                <div class="col-12 col-md-6">
                    <label for="logDateTo" class="form-label">Sampai Tanggal</label>
                    <input type="date" id="logDateTo" name="date_to"
                           class="form-control @error('date_to') is-invalid @enderror" value="{{ request('date_to') }}">
                </div>
            </div>

            <div class="filter-actions filter-actions-end mt-3">
                <a href="{{ route('activity-logs.index') }}" class="btn btn-outline-secondary px-3">Reset</a>
                <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search me-1"></i>Cari</button>
            </div>
        </form>
    </div>
</div>
