<div class="card filter-card mb-4">
    <div class="card-body">
        <form
            action="{{ route('activity-logs.index') }}"
            method="GET">

            <div class="row g-3">

                {{-- Search --}}
                <div class="col-md-6">

                    <label class="form-label">
                        Cari
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control @error('search') is-invalid @enderror"
                        placeholder="Cari user, modul, aksi..."
                        value="{{ request('search') }}">

                </div>

                {{-- Module (DIPERBAIKI) --}}
                <div class="col-md-4">

                    <label class="form-label">
                        Modul
                    </label>

                    <select
                        class="form-select @error('module') is-invalid @enderror"
                        name="module">

                        <option value="">
                            Semua Modul
                        </option>

                        {{-- Menggunakan $value => $label untuk mendukung associative array --}}
                        @foreach($modules as $value => $label)

                            <option
                                value="{{ $value }}"
                                @selected(request('module') == $value)>

                                {{ \App\Support\LogLabel::module($label) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- User --}}
                <div class="col-md-4">

                    <label class="form-label">
                        User
                    </label>

                    <select
                        class="form-select @error('user_id') is-invalid @enderror"
                        name="user_id">

                        <option value="">
                            Semua User
                        </option>

                        @foreach($users as $user)
                            <option
                                value="{{ $user->id }}"
                                @selected(request('user_id') == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Action --}}
                <div class="col-md-4">

                    <label class="form-label">
                        Aksi
                    </label>

                    <select
                        class="form-select @error('action') is-invalid @enderror"
                        name="action">

                        <option value="">
                            Semua Aksi
                        </option>

                        @foreach($actions as $group => $items)
                            <optgroup label="{{ $group }}">
                                @foreach($items as $action)

                                    <option
                                        value="{{ $action }}"
                                        @selected(request('action') == $action)>

                                        {{ \App\Support\LogLabel::action($action) }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div class="col-md-6">

                    <label class="form-label">
                        Dari Tanggal
                    </label>

                    <input
                        type="date"
                        class="form-control @error('date_from') is-invalid @enderror"
                        name="date_from"
                        value="{{ request('date_from') }}">
                </div>

                {{-- Date To --}}
                <div class="col-md-6">

                    <label class="form-label">
                        Sampai Tanggal
                    </label>

                    <input
                        type="date"
                        class="form-control @error('date_to') is-invalid @enderror"
                        name="date_to"
                        value="{{ request('date_to') }}">

                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="d-flex justify-content-end gap-2 mt-3">

                <a
                    href="{{ route('activity-logs.index') }}"
                    class="btn btn-outline-secondary px-3">
                    Reset
                </a>

                <button
                    type="submit"
                    class="btn btn-primary px-3">
                    Cari
                </button>
            </div>
        </form>
    </div>
</div>
