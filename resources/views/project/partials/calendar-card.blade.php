{{-- Kartu Kalender Project. Dipakai index dan dikirim ulang saat ganti bulan via AJAX. --}}
<div id="projectCalendarCard" class="card border-0 shadow-sm rounded-3 h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold m-0"><i class="bi bi-calendar3 me-1"></i> Kalender Project</h6>
            <div class="d-flex align-items-center gap-2">
                @php
                    $prevMonth = $calendarMonth - 1; $prevYear = $calendarYear;
                    if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
                    $nextMonth = $calendarMonth + 1; $nextYear = $calendarYear;
                    if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
                @endphp
                <a href="{{ request()->fullUrlWithQuery(['cal_month' => $prevMonth, 'cal_year' => $prevYear]) }}" class="btn btn-sm btn-light js-calendar-nav"><i class="bi bi-chevron-left"></i></a>
                <span class="fw-semibold small">{{ \Carbon\Carbon::createFromDate($calendarYear, $calendarMonth, 1)->translatedFormat('F Y') }}</span>
                <a href="{{ request()->fullUrlWithQuery(['cal_month' => $nextMonth, 'cal_year' => $nextYear]) }}" class="btn btn-sm btn-light js-calendar-nav"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>

        @php
            $firstDay = \Carbon\Carbon::createFromDate($calendarYear, $calendarMonth, 1);
            $daysInMonth = $firstDay->daysInMonth;
            $startWeekday = $firstDay->dayOfWeek; // 0 = Minggu
            $today = now()->format('Y-m-d');
        @endphp

        <table class="table table-borderless text-center small mb-2">
            <thead>
                <tr class="text-muted">
                    <th>Min</th><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    @for($i = 0; $i < $startWeekday; $i++)
                        <td></td>
                    @endfor
                    @for($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $dateStr = sprintf('%04d-%02d-%02d', $calendarYear, $calendarMonth, $day);
                            $count = $calendarData[$dateStr] ?? 0;
                            $isToday = $dateStr === $today;
                        @endphp
                        <td class="p-1">
                            <a href="{{ request()->fullUrlWithQuery(['date' => $dateStr]) }}"
                               class="d-inline-flex flex-column align-items-center justify-content-center rounded-circle {{ $isToday ? 'bg-primary text-white' : ($count > 0 ? 'bg-light fw-semibold' : '') }} u-w-32px u-h-32px u-td-none u-c-inherit"
                              >
                                {{ $day }}
                            </a>
                            @if($count > 0)
                                <div class="{{ $count > 4 ? 'text-danger' : ($count >= 3 ? 'text-warning' : 'text-primary') }} u-fs-8px">&#9679;</div>
                            @endif
                        </td>
                        @if(($startWeekday + $day) % 7 === 0 && $day !== $daysInMonth)
                            </tr><tr>
                        @endif
                    @endfor
                </tr>
            </tbody>
        </table>
        <div class="small text-muted">
            <span class="text-primary">&#9679;</span> 1-2 Project &nbsp;
            <span class="text-warning">&#9679;</span> 3-4 Project &nbsp;
            <span class="text-danger">&#9679;</span> &gt; 4 Project
        </div>

        @if(auth()->user()->hasPermission('finance', 'export_report'))
            <hr class="my-3">
            <button type="button" id="exportMonthlyReportBtn" class="btn btn-outline-primary btn-sm w-100 d-flex align-items-center justify-content-center gap-2"
                    data-month="{{ $calendarMonth }}" data-year="{{ $calendarYear }}">
                <span class="btn-text"><i class="bi bi-file-earmark-pdf me-1"></i> Ekspor Laporan Bulanan</span>
                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
        @endif
    </div>
</div>
