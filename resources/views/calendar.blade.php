@extends('layouts.master')

@section('title', 'Operations Calendar')
@section('page-title', 'Operations Calendar')

@section('css')
<link rel="stylesheet" href="{{ URL::asset('build/libs/@fullcalendar/core/main.min.css') }}" type="text/css">
<link rel="stylesheet" href="{{ URL::asset('build/libs/@fullcalendar/daygrid/main.min.css') }}" type="text/css">
<link rel="stylesheet" href="{{ URL::asset('build/libs/@fullcalendar/bootstrap/main.min.css') }}" type="text/css">
<link rel="stylesheet" href="{{ URL::asset('build/libs/@fullcalendar/timegrid/main.min.css') }}" type="text/css">
<style>
    .calendar-shell .legend-strip {
        border: 1px solid #e9edf4;
        border-radius: 12px;
        background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
        padding: .85rem 1rem;
    }

    .legend-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem 1rem;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        font-size: .87rem;
        color: #5a6270;
    }select#calendar-route-filter,select#calendar-driver-filter,select#calendar-status-filter{
        padding:12px;
    }

    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }

    .legend-badge {
        border-radius: 999px;
        font-size: .72rem;
        padding: .25rem .5rem;
        border: 1px solid transparent;
    }

    .legend-module-assignment { background: #e8f0ff; color: #0d6efd; border-color: #cfe2ff; }
    .legend-module-trip { background: #eef7ed; color: #198754; border-color: #d1e7dd; }
    .legend-status-upcoming { background: #fff3cd; color: #856404; border-color: #ffe69c; }
    .legend-status-ongoing { background: #d1e7ff; color: #084298; border-color: #b6d4fe; }
    .legend-status-completed { background: #d1e7dd; color: #0f5132; border-color: #badbcc; }

    #calendar {
        min-height: 560px;
    }

    .fc .fc-toolbar-title {
        font-size: 1rem;
    }

    .fc .fc-button {
        font-size: .74rem;
        padding: .16rem .34rem;
    }

    .fc .fc-col-header-cell-cushion {
        font-size: .72rem;
        padding: .28rem .15rem;
    }

    .fc .fc-daygrid-day-number {
        font-size: .74rem;
        padding: .2rem .25rem;
    }

    .fc .fc-daygrid-day-frame {
        min-height: 68px;
    }

    .fc .fc-daygrid-event {
        font-size: .66rem;
        padding: 0 2px;
    }

    .fc-event.event-assignment {
        border-color: transparent !important;
    }

    .fc-event.event-trip {
        border-color: transparent !important;
    }

    .fc-event.event-status-upcoming {
        background-color: #f59f00 !important;
    }

    .fc-event.event-status-ongoing {
        background-color: #0d6efd !important;
    }

    .fc-event.event-status-completed {
        background-color: #198754 !important;
    }

    .fc .fc-toolbar.fc-header-toolbar,
    .fc .fc-toolbar {
        display: flex;
        gap: .5rem;
        align-items: center;
    }

    .fc .fc-header-toolbar .fc-toolbar-chunk:first-child,
    .fc .fc-toolbar .fc-left {
        display: flex !important;
        align-items: center;
        flex-wrap: wrap;
        gap: .4rem .6rem;
    }

    .fc .fc-ops-legend {
        display: inline-flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: .4rem .75rem;
        margin-top: 0;
        margin-left: 0;
        margin-right: .55rem;
        padding: .2rem .4rem;
        border: 1px solid #e9edf4;
        border-radius: 8px;
        background: #fff;
    }

    .fc .fc-header-toolbar .fc-toolbar-chunk:first-child .fc-button-group,
    .fc .fc-toolbar .fc-left .fc-button-group { order: 1; }
    .fc .fc-header-toolbar .fc-toolbar-chunk:first-child .fc-ops-legend,
    .fc .fc-toolbar .fc-left .fc-ops-legend { order: 2; }
    .fc .fc-header-toolbar .fc-toolbar-chunk:first-child .fc-today-button,
    .fc .fc-toolbar .fc-left .fc-today-button { order: 3; }

    .fc .fc-ops-legend .legend-item {
        font-size: .78rem;
        margin: 0;
    }

    .calendar-filters .form-label {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: .35rem;
    }
</style>
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
@php
    $eventsCount = count($calendarEvents ?? []);
@endphp
<div class="container-fluid calendar-shell">
    <div class="card mb-0">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h4 class="mb-1">Operations Calendar</h4>
                    <p class="text-muted mb-0">
                        Calendar view of assignments and trips including upcoming, ongoing, and completed activities.
                    </p>
                </div>
                <div class="col-auto text-end">
                    <div class="mb-2">
                        <span class="badge bg-light text-dark border">Total Events: <span id="total-events-count">{{ $eventsCount }}</span></span>
                    </div>
                    <div id="calendar-legend-fallback" class="legend-row justify-content-end">
                        <span class="small text-muted fw-semibold text-uppercase">Legend</span>
                        <span class="legend-item"><span class="legend-dot" style="background:#f59f00;"></span> Upcoming</span>
                        <span class="legend-item"><span class="legend-dot" style="background:#0d6efd;"></span> Ongoing</span>
                        <span class="legend-item"><span class="legend-dot" style="background:#198754;"></span> Completed</span>
                        <span class="legend-item"><span class="legend-badge legend-module-assignment">Assignment</span></span>
                        <span class="legend-item"><span class="legend-badge legend-module-trip">Trip</span></span>
                    </div>
                </div>
            </div>
            <div class="row g-2 mt-2 calendar-filters">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label" for="calendar-route-filter">Route</label>
                    <select id="calendar-route-filter" class="form-select form-select-sm">
                        <option value="">All Routes</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label" for="calendar-driver-filter">Driver</label>
                    <select id="calendar-driver-filter" class="form-select form-select-sm">
                        <option value="">All Drivers</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label class="form-label" for="calendar-status-filter">Status</label>
                    <select id="calendar-status-filter" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="col-12 col-lg-2 d-flex align-items-end">
                    <button type="button" id="calendar-clear-filters" class="btn btn-sm btn-outline-primary w-50">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/libs/@fullcalendar/core/main.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/@fullcalendar/bootstrap/main.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/@fullcalendar/daygrid/main.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/@fullcalendar/timegrid/main.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/@fullcalendar/interaction/main.min.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>

<script>
    (function () {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        const allEvents = @json($calendarEvents ?? []);
        const routeFilterEl = document.getElementById('calendar-route-filter');
        const driverFilterEl = document.getElementById('calendar-driver-filter');
        const statusFilterEl = document.getElementById('calendar-status-filter');
        const clearFiltersEl = document.getElementById('calendar-clear-filters');
        const totalEventsCountEl = document.getElementById('total-events-count');

        function normalizeValue(value) {
            return String(value || '').trim().toLowerCase();
        }

        function setCount(count) {
            if (totalEventsCountEl) totalEventsCountEl.textContent = String(count);
        }

        function populateFilterOptions(selectEl, values) {
            if (!selectEl) return;
            const fragment = document.createDocumentFragment();

            values.forEach((value) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                fragment.appendChild(option);
            });

            selectEl.appendChild(fragment);
        }

        populateFilterOptions(
            routeFilterEl,
            [...new Set(allEvents
                .map((event) => event?.extendedProps?.route)
                .filter(Boolean)
                .map((value) => String(value).trim())
            )].sort((a, b) => a.localeCompare(b))
        );

        populateFilterOptions(
            driverFilterEl,
            [...new Set(allEvents
                .map((event) => event?.extendedProps?.driver)
                .filter(Boolean)
                .map((value) => String(value).trim())
            )].sort((a, b) => a.localeCompare(b))
        );

        function getFilteredEvents() {
            const selectedRoute = normalizeValue(routeFilterEl?.value);
            const selectedDriver = normalizeValue(driverFilterEl?.value);
            const selectedStatus = normalizeValue(statusFilterEl?.value);

            return allEvents.filter((event) => {
                const routeName = normalizeValue(event?.extendedProps?.route);
                const driverName = normalizeValue(event?.extendedProps?.driver);
                const status = normalizeValue(event?.extendedProps?.status);

                const routeMatches = !selectedRoute || routeName === selectedRoute;
                const driverMatches = !selectedDriver || driverName === selectedDriver;
                const statusMatches = !selectedStatus || status === selectedStatus;

                return routeMatches && driverMatches && statusMatches;
            });
        }

        function mountLegendInToolbar() {
            const fallbackLegend = document.getElementById('calendar-legend-fallback');
            const toolbar = calendarEl.querySelector('.fc-header-toolbar')
                || calendarEl.querySelector('.fc-toolbar.fc-header-toolbar')
                || calendarEl.querySelector('.fc-toolbar');
            if (!toolbar) {
                if (fallbackLegend) fallbackLegend.style.display = '';
                return;
            }

            const todayBtn = toolbar.querySelector('.fc-today-button')
                || toolbar.querySelector('.fc-button.fc-today-button');
            const leftChunk = (todayBtn && todayBtn.parentElement)
                || toolbar.querySelector('.fc-toolbar-chunk:first-child')
                || toolbar.querySelector('.fc-left');
            if (!leftChunk) {
                if (fallbackLegend) fallbackLegend.style.display = '';
                return;
            }

            toolbar.querySelectorAll('.fc-ops-legend').forEach((node) => node.remove());

            const legend = document.createElement('div');
            legend.className = 'fc-ops-legend';
            legend.innerHTML = `
                <span class="small text-muted fw-semibold text-uppercase">Legend</span>
                <span class="legend-item"><span class="legend-dot" style="background:#f59f00;"></span> Upcoming</span>
                <span class="legend-item"><span class="legend-dot" style="background:#0d6efd;"></span> Ongoing</span>
                <span class="legend-item"><span class="legend-dot" style="background:#198754;"></span> Completed</span>
                <span class="legend-item"><span class="legend-badge legend-module-assignment">Assignment</span></span>
                <span class="legend-item"><span class="legend-badge legend-module-trip">Trip</span></span>
            `;

            const todayBtnInChunk = leftChunk.querySelector('.fc-today-button')
                || leftChunk.querySelector('.fc-button.fc-today-button');
            if (todayBtnInChunk) {
                leftChunk.insertBefore(legend, todayBtnInChunk);
            } else {
                leftChunk.prepend(legend);
            }

            if (fallbackLegend) fallbackLegend.style.display = 'none';
        }

        function applyEventFilters(calendar) {
            const filteredEvents = getFilteredEvents();
            calendar.removeAllEvents();
            calendar.addEventSource(filteredEvents);
            setCount(filteredEvents.length);
        }

        const calendar = new FullCalendar.Calendar(calendarEl, {
            plugins: ['bootstrap', 'interaction', 'dayGrid', 'timeGrid'],
            themeSystem: 'bootstrap',
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay',
            },
            events: allEvents,
            editable: false,
            droppable: false,
            selectable: false,
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true,
            },
            datesSet: mountLegendInToolbar,
            viewDidMount: mountLegendInToolbar,
        });

        calendar.render();
        mountLegendInToolbar();
        setTimeout(mountLegendInToolbar, 120);
        setCount(allEvents.length);

        [routeFilterEl, driverFilterEl, statusFilterEl].forEach((el) => {
            if (!el) return;
            el.addEventListener('change', () => applyEventFilters(calendar));
        });

        if (clearFiltersEl) {
            clearFiltersEl.addEventListener('click', () => {
                if (routeFilterEl) routeFilterEl.value = '';
                if (driverFilterEl) driverFilterEl.value = '';
                if (statusFilterEl) statusFilterEl.value = '';
                applyEventFilters(calendar);
            });
        }
    })();
</script>
@endsection
