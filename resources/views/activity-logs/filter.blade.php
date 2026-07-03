<div class="card shadow-sm mb-4 border-0">

    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="m-0 fw-bold text-primary">
            <i class="fas fa-filter me-2"></i>
            Filter Activity Logs
        </h6>
    </div>

    <div class="card-body bg-light-50">

        <form
            action="{{ route('activity-logs.index') }}"
            method="GET">

            <div class="row g-3">

                {{-- Search --}}
                <div class="col-md-6">

                    <label class="form-label small fw-semibold text-muted">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search user, module, action..."
                        value="{{ request('search') }}">

                </div>

                {{-- Show --}}
                <div class="col-md-2">

                    <label class="form-label small fw-semibold text-muted">
                        Show
                    </label>

                    <select
                        name="per_page"
                        class="form-select">

                        <option value="10" @selected(request('per_page', 10) == 10)>10</option>
                        <option value="25" @selected(request('per_page') == 25)>25</option>
                        <option value="50" @selected(request('per_page') == 50)>50</option>
                        <option value="100" @selected(request('per_page') == 100)>100</option>

                    </select>

                </div>

                {{-- Module --}}
                <div class="col-md-4">

                    <label class="form-label small fw-semibold text-muted">
                        Module
                    </label>

                    <select
                        class="form-select"
                        name="module">

                        <option value="">
                            All Modules
                        </option>

                        @foreach($modules as $module)

                            <option
                                value="{{ $module }}"
                                @selected(request('module') == $module)>

                                {{ $module }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- User --}}
                <div class="col-md-4">

                    <label class="form-label small fw-semibold text-muted">
                        User
                    </label>

                    <select
                        class="form-select"
                        name="user_id">

                        <option value="">
                            All Users
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

                    <label class="form-label small fw-semibold text-muted">
                        Action
                    </label>

                    <select
                        class="form-select"
                        name="action">

                        <option value="">
                            All Actions
                        </option>

                        @foreach($actions as $group => $items)
                            <optgroup label="{{ $group }}">
                                @foreach($items as $action)

                                    <option
                                        value="{{ $action }}"
                                        @selected(request('action') == $action)>

                                        {{ $action }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div class="col-md-6">

                    <label class="form-label small fw-semibold text-muted">
                        Date From
                    </label>

                    <input
                        type="date"
                        class="form-control"
                        name="date_from"
                        value="{{ request('date_from') }}">
                </div>

                {{-- Date To --}}
                <div class="col-md-6">

                    <label class="form-label small fw-semibold text-muted">
                        Date To
                    </label>

                    <input
                        type="date"
                        class="form-control"
                        name="date_to"
                        value="{{ request('date_to') }}">

                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">

                <a
                    href="{{ route('activity-logs.index') }}"
                    class="btn btn-outline-secondary px-4">
                    Reset
                </a>all

                <button
                    type="submit"
                    class="btn btn-primary px-4">
                    Search
                </button>
            </div>
        </form>
    </div>
</div>
