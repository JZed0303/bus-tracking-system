<?php

namespace App\Support;

use App\Exports\TripReportExport;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class TripReportService
{
    public function prepareTrip(Trip $trip): Trip
    {
        $trip->load([
            'assignment.company',
            'assignment.bus',
            'assignment.route',
            'assignment.route.stops',
            'assignment.driver.user',
            'locations' => fn ($q) => $q->orderBy('tracked_at', 'asc'),
            'checkins' => fn ($q) => $q
                ->with('employee.user', 'voidedByUser')
                ->orderBy('scan_time', 'asc'),
        ]);

        return $trip;
    }

    public function buildExpectedEmployeeStatuses(Trip $trip, int $companyId): Collection
    {
        $routeId = (int) optional($trip->assignment)->route_id;
        $tripDate = $trip->trip_date?->toDateString();

        $scanGroups = $trip->checkins
            ->whereNull('voided_at')
            ->sortBy('scan_time')
            ->groupBy('employee_id');

        $scheduledEmployees = collect();

        if ($companyId > 0 && $routeId > 0 && $tripDate) {
            $scheduledEmployees = EmployeeSchedule::query()
                ->with(['employee.user', 'employee.pickupStop.stop'])
                ->where('company_id', $companyId)
                ->where('route_id', $routeId)
                ->whereDate('schedule_date', $tripDate)
                ->where('status', '!=', 'cancelled')
                ->orderBy('expected_pickup_time')
                ->get();
        }

        if ($scheduledEmployees->isEmpty() && $companyId > 0 && $routeId > 0) {
            return Employee::query()
                ->with(['user', 'pickupStop.stop'])
                ->where('company_id', $companyId)
                ->whereHas('pickupStop.stop', fn ($q) => $q->where('route_id', $routeId))
                ->orderBy('employee_code')
                ->get()
                ->map(function (Employee $employee) use ($scanGroups) {
                    $scans = $scanGroups->get($employee->id, collect());
                    $checkin = $scans->where('scan_type', 'checkin')->last();
                    $checkout = $scans->where('scan_type', 'checkout')->last();

                    return [
                        'employee' => $employee,
                        'expected_pickup_time' => null,
                        'pickup_stop' => $employee->pickupStop?->stop?->address,
                        'schedule_status' => 'scheduled',
                        'checkin' => $checkin,
                        'checkout' => $checkout,
                        'status' => $checkout ? 'checked_out' : ($checkin ? 'checked_in' : 'pending'),
                    ];
                })
                ->values();
        }

        return $scheduledEmployees
            ->groupBy('employee_id')
            ->map(function ($rows) use ($scanGroups) {
                $schedule = $rows->sortBy('expected_pickup_time')->first();
                $employee = $schedule->employee;
                $scans = $scanGroups->get($employee->id, collect());
                $checkin = $scans->where('scan_type', 'checkin')->last();
                $checkout = $scans->where('scan_type', 'checkout')->last();

                $status = match (true) {
                    (bool) $checkout => 'checked_out',
                    (bool) $checkin => 'checked_in',
                    $schedule->status === 'missed' => 'missed',
                    default => 'pending',
                };

                return [
                    'employee' => $employee,
                    'expected_pickup_time' => $schedule->expected_pickup_time,
                    'pickup_stop' => $employee->pickupStop?->stop?->address,
                    'schedule_status' => $schedule->status ?? 'scheduled',
                    'checkin' => $checkin,
                    'checkout' => $checkout,
                    'status' => $status,
                ];
            })
            ->values()
            ->sortBy(fn ($row) => $row['expected_pickup_time'] ? $row['expected_pickup_time']->format('H:i') : '99:99')
            ->values();
    }

    public function buildReport(Trip $trip, int $companyId): array
    {
        $this->prepareTrip($trip);

        $tz = 'Asia/Manila';
        $expectedEmployees = $this->buildExpectedEmployeeStatuses($trip, $companyId);
        $locations = $trip->locations;
        $allScans = $trip->checkins->sortBy('scan_time')->values();
        $activeScans = $allScans->whereNull('voided_at')->values();

        $latestScanByEmployee = $activeScans
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->last());

        $onboardNowCount = $latestScanByEmployee
            ->filter(fn ($scan) => $scan && $scan->scan_type === 'checkin')
            ->count();

        $completedRideCount = $latestScanByEmployee
            ->filter(fn ($scan) => $scan && $scan->scan_type === 'checkout')
            ->count();

        $distanceKm = 0.0;
        try {
            $distanceKm = round($trip->totalDistanceKm(), 2);
        } catch (\Throwable) {
            $distanceKm = 0.0;
        }

        $durationMinutes = $trip->actual_start_time && $trip->actual_end_time
            ? $trip->actual_start_time->diffInMinutes($trip->actual_end_time)
            : null;

        $employeeStatusCounts = [
            'Checked In' => $expectedEmployees->where('status', 'checked_in')->count(),
            'Checked Out' => $expectedEmployees->where('status', 'checked_out')->count(),
            'Pending' => $expectedEmployees->where('status', 'pending')->count(),
            'Missed' => $expectedEmployees->where('status', 'missed')->count(),
        ];

        $scanTimeline = $activeScans
            ->groupBy(fn ($scan) => optional($scan->scan_time)->timezone($tz)->format('h:i A'))
            ->map(function (Collection $rows, string $timeLabel) {
                return [
                    'label' => $timeLabel,
                    'checkins' => $rows->where('scan_type', 'checkin')->count(),
                    'checkouts' => $rows->where('scan_type', 'checkout')->count(),
                ];
            })
            ->values();

        $stopPerformance = $expectedEmployees
            ->groupBy(fn ($row) => $row['pickup_stop'] ?: 'Unassigned Stop')
            ->map(function (Collection $rows, string $stop) {
                return [
                    'stop' => $stop,
                    'expected' => $rows->count(),
                    'boarded' => $rows->whereIn('status', ['checked_in', 'checked_out'])->count(),
                    'completed' => $rows->where('status', 'checked_out')->count(),
                ];
            })
            ->sortBy('stop')
            ->values();

        $employeeRows = $expectedEmployees->map(function (array $row, int $index) use ($tz) {
            $employee = $row['employee'];

            return [
                'no' => $index + 1,
                'employee' => $employee->user?->full_name ?? '—',
                'employee_code' => $employee->employee_code ?? '—',
                'department' => $employee->department ?? '—',
                'position' => $employee->position ?? '—',
                'pickup_stop' => $row['pickup_stop'] ?? '—',
                'expected_pickup' => $row['expected_pickup_time']?->format('h:i A') ?? '—',
                'status' => $this->statusLabel($row['status']),
                'checkin_time' => $row['checkin']?->scan_time?->timezone($tz)->format('M d, Y h:i A') ?? '—',
                'checkout_time' => $row['checkout']?->scan_time?->timezone($tz)->format('M d, Y h:i A') ?? '—',
            ];
        })->values();

        $scanRows = $allScans->map(function ($scan, int $index) use ($tz) {
            return [
                'no' => $index + 1,
                'employee' => $scan->employee?->user?->full_name ?? '—',
                'employee_code' => $scan->employee?->employee_code ?? '—',
                'scan_type' => ucfirst((string) $scan->scan_type),
                'record_status' => $scan->voided_at ? 'Voided' : 'Active',
                'scan_time' => $scan->scan_time?->timezone($tz)->format('M d, Y h:i A') ?? '—',
                'void_reason' => $scan->void_reason ?: '—',
                'voided_by' => $scan->voided_at ? ($scan->voidedByUser?->full_name ?? $scan->voidedByUser?->email ?? 'System') : '—',
            ];
        })->values();

        $stopRows = collect(optional($trip->assignment->route)->stops)
            ->sortBy('order')
            ->values()
            ->map(function ($stop, int $index) use ($stopPerformance) {
                $performance = $stopPerformance->firstWhere('stop', $stop->address);

                return [
                    'no' => $index + 1,
                    'stop_name' => $stop->name ?: ('Stop ' . ($index + 1)),
                    'address' => $stop->address ?? '—',
                    'expected' => (int) ($performance['expected'] ?? 0),
                    'boarded' => (int) ($performance['boarded'] ?? 0),
                    'completed' => (int) ($performance['completed'] ?? 0),
                ];
            });

        $summary = [
            ['label' => 'Expected Employees', 'value' => $expectedEmployees->count()],
            ['label' => 'On Board Now', 'value' => $onboardNowCount],
            ['label' => 'Completed Rides', 'value' => $completedRideCount],
            ['label' => 'Total Scan Records', 'value' => $allScans->count()],
            ['label' => 'GPS Points', 'value' => $locations->count()],
            ['label' => 'Distance Travelled', 'value' => number_format($distanceKm, 2) . ' km'],
        ];

        return [
            'title' => 'Trip Report',
            'filename' => 'trip-report-' . ($trip->id ?? 'na') . '-' . now()->format('Ymd_His'),
            'trip' => $trip,
            'company' => optional($trip->assignment)->company,
            'overview' => [
                'Trip ID' => '#' . $trip->id,
                'Trip Reference' => optional($trip->assignment?->bus)->plate_number ?? 'Unassigned Bus',
                'Company' => optional($trip->assignment?->company)->name ?? config('app.name', 'Bus Tracking System'),
                'Route' => optional($trip->assignment?->route)->name ?? '—',
                'Driver' => optional($trip->assignment?->driver?->user)->full_name ?? '—',
                'Direction' => $trip->direction_label,
                'Status' => ucfirst((string) $trip->status),
                'Trip Date' => $trip->trip_date?->timezone($tz)->format('M d, Y') ?? '—',
                'Scheduled Start' => $trip->scheduled_start_time?->timezone($tz)->format('M d, Y h:i A') ?? '—',
                'Actual Start' => $trip->actual_start_time?->timezone($tz)->format('M d, Y h:i A') ?? '—',
                'Actual End' => $trip->actual_end_time?->timezone($tz)->format('M d, Y h:i A') ?? '—',
                'Duration' => $durationMinutes === null ? '—' : (intdiv($durationMinutes, 60) . 'h ' . ($durationMinutes % 60) . 'm'),
                'First GPS Ping' => $locations->first()?->tracked_at?->timezone($tz)->format('M d, Y h:i A') ?? '—',
                'Last GPS Ping' => $locations->last()?->tracked_at?->timezone($tz)->format('M d, Y h:i A') ?? '—',
                'Incident Reason' => $trip->incident_reason ?: '—',
                'Ended Reason' => $trip->ended_reason ? Str::headline((string) $trip->ended_reason) : '—',
            ],
            'summary' => $summary,
            'charts' => [
                'employee_status' => [
                    'labels' => array_keys($employeeStatusCounts),
                    'series' => array_values($employeeStatusCounts),
                ],
                'scan_activity' => [
                    'categories' => $scanTimeline->pluck('label')->all(),
                    'checkins' => $scanTimeline->pluck('checkins')->all(),
                    'checkouts' => $scanTimeline->pluck('checkouts')->all(),
                ],
                'stop_performance' => [
                    'categories' => $stopPerformance->pluck('stop')->all(),
                    'expected' => $stopPerformance->pluck('expected')->all(),
                    'boarded' => $stopPerformance->pluck('boarded')->all(),
                ],
            ],
            'tables' => [
                'employees' => $employeeRows,
                'scans' => $scanRows,
                'stops' => $stopRows->values(),
            ],
            'excel_rows' => $this->buildExcelRows($trip, $summary, $employeeRows, $scanRows, $stopRows),
        ];
    }

    public function exportExcel(array $report, string $writerType = ExcelWriter::XLSX)
    {
        $extension = $writerType === ExcelWriter::CSV ? 'csv' : 'xlsx';

        return Excel::download(
            new TripReportExport($report['excel_rows']),
            ($report['filename'] ?? 'trip-report') . '.' . $extension,
            $writerType
        );
    }

    public function exportPdf(array $report, bool $inline = false)
    {
        $trip = $report['trip'];
        $company = $report['company'] instanceof Company ? $report['company'] : null;
        $companyName = $company?->name ?? config('app.name', 'Bus Tracking System');
        $companyAddress = $company?->address ?: 'Transport Operations and Administration';
        $logoPath = $this->resolveCompanyLogoPath($company);

        $pdf = app('fpdf');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage('L', 'A4');
        $pdf->SetTitle($this->pdfText('Trip Report #' . $trip->id));
        $pdf->SetAuthor($this->pdfText(config('app.name', 'Bus Tracking System')));

        $startY = 10;
        $textX = 10;
        if ($logoPath !== null) {
            $pdf->Image($logoPath, 10, $startY, 24);
            $textX = 38;
        }

        $pdf->SetXY($textX, $startY);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 6, $this->pdfText($companyName), 0, 1, 'L');
        $pdf->SetX($textX);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, $this->pdfText($companyAddress), 0, 1, 'L');
        $pdf->SetX($textX);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 6, $this->pdfText('Detailed Trip Report'), 0, 1, 'L');
        $pdf->SetX($textX);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, $this->pdfText('Trip #' . $trip->id . ' - ' . (optional($trip->assignment?->route)->name ?? 'No Route')), 0, 1, 'L');

        $pdf->Ln(3);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(10, $pdf->GetY(), 287, $pdf->GetY());
        $pdf->Ln(4);

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(245, 247, 250);
        $pdf->Cell(90, 7, $this->pdfText('Trip Reference: ' . (optional($trip->assignment?->bus)->plate_number ?? '—')), 1, 0, 'L', true);
        $pdf->Cell(90, 7, $this->pdfText('Trip Date: ' . ($trip->trip_date?->format('Y-m-d') ?? '—')), 1, 0, 'L', true);
        $pdf->Cell(97, 7, $this->pdfText('Generated: ' . now()->format('Y-m-d H:i:s')), 1, 1, 'L', true);
        $pdf->Ln(3);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->pdfText('Trip Overview'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 8.5);

        $overviewChunks = array_chunk($report['overview'], 2, true);
        foreach ($overviewChunks as $chunk) {
            foreach ($chunk as $label => $value) {
                $pdf->SetFillColor(248, 250, 252);
                $pdf->Cell(40, 7, $this->pdfText($label), 1, 0, 'L', true);
                $pdf->Cell(103.5, 7, $this->pdfText((string) $value), 1, 0, 'L');
            }
            $pdf->Ln();
        }

        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->pdfText('Summary Metrics'), 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 8.5);
        foreach (array_chunk($report['summary'], 3) as $summaryRow) {
            foreach ($summaryRow as $item) {
                $pdf->SetFillColor(231, 239, 255);
                $line = $item['label'] . ': ' . $item['value'];
                $pdf->Cell(92.33, 7, $this->pdfText(Str::limit($line, 44, '')), 1, 0, 'L', true);
            }
            $pdf->Ln();
        }

        $this->renderPdfTable($pdf, 'Expected Employees', [
            'Employee', 'Code', 'Pickup Stop', 'Expected', 'Status', 'Check-in', 'Check-out'
        ], collect($report['tables']['employees'])->map(fn ($row) => [
            $row['employee'],
            $row['employee_code'],
            $row['pickup_stop'],
            $row['expected_pickup'],
            $row['status'],
            $row['checkin_time'],
            $row['checkout_time'],
        ])->all(), [44, 22, 56, 24, 24, 48, 48]);

        $this->renderPdfTable($pdf, 'Scan Log', [
            'Employee', 'Type', 'Status', 'Scanned At', 'Void Reason'
        ], collect($report['tables']['scans'])->map(fn ($row) => [
            $row['employee'],
            $row['scan_type'],
            $row['record_status'],
            $row['scan_time'],
            $row['void_reason'],
        ])->all(), [55, 22, 24, 48, 128]);

        $filename = ($report['filename'] ?? 'trip-report') . '.pdf';
        $content = $pdf->Output('S');

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . $filename . '"',
        ]);
    }

    private function buildExcelRows(Trip $trip, array $summary, Collection $employeeRows, Collection $scanRows, Collection $stopRows): array
    {
        $rows = [
            ['Trip Report', 'Trip #' . $trip->id],
            ['Generated At', now()->format('Y-m-d H:i:s')],
            [],
            ['Overview'],
            ['Trip Reference', optional($trip->assignment?->bus)->plate_number ?? '—'],
            ['Route', optional($trip->assignment?->route)->name ?? '—'],
            ['Driver', optional($trip->assignment?->driver?->user)->full_name ?? '—'],
            ['Status', ucfirst((string) $trip->status)],
            ['Trip Date', $trip->trip_date?->format('Y-m-d') ?? '—'],
            [],
            ['Summary'],
        ];

        foreach ($summary as $item) {
            $rows[] = [$item['label'], (string) $item['value']];
        }

        $rows[] = [];
        $rows[] = ['Expected Employees'];
        $rows[] = ['#', 'Employee', 'Code', 'Department', 'Position', 'Pickup Stop', 'Expected Pickup', 'Status', 'Check-in', 'Check-out'];
        foreach ($employeeRows as $row) {
            $rows[] = [
                $row['no'],
                $row['employee'],
                $row['employee_code'],
                $row['department'],
                $row['position'],
                $row['pickup_stop'],
                $row['expected_pickup'],
                $row['status'],
                $row['checkin_time'],
                $row['checkout_time'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Scan Log'];
        $rows[] = ['#', 'Employee', 'Code', 'Type', 'Record Status', 'Scanned At', 'Void Reason', 'Voided By'];
        foreach ($scanRows as $row) {
            $rows[] = [
                $row['no'],
                $row['employee'],
                $row['employee_code'],
                $row['scan_type'],
                $row['record_status'],
                $row['scan_time'],
                $row['void_reason'],
                $row['voided_by'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Route Stop Summary'];
        $rows[] = ['#', 'Stop Name', 'Address', 'Expected', 'Boarded', 'Completed'];
        foreach ($stopRows as $row) {
            $rows[] = [
                $row['no'],
                $row['stop_name'],
                $row['address'],
                $row['expected'],
                $row['boarded'],
                $row['completed'],
            ];
        }

        return $rows;
    }

    private function renderPdfTable($pdf, string $title, array $headers, array $rows, array $widths): void
    {
        $pdf->Ln(4);
        if ($pdf->GetY() > 180) {
            $pdf->AddPage('L', 'A4');
        }

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->pdfText($title), 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(52, 73, 94);

        foreach ($headers as $index => $header) {
            $pdf->Cell($widths[$index], 7, $this->pdfText(Str::limit($header, 24, '')), 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(20, 20, 20);
        $alternate = false;
        foreach ($rows as $row) {
            if ($pdf->GetY() > 195) {
                $pdf->AddPage('L', 'A4');
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFillColor(52, 73, 94);
                foreach ($headers as $index => $header) {
                    $pdf->Cell($widths[$index], 7, $this->pdfText(Str::limit($header, 24, '')), 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetFont('Arial', '', 7.5);
                $pdf->SetTextColor(20, 20, 20);
            }

            $pdf->SetFillColor($alternate ? 249 : 255, $alternate ? 251 : 255, 255);
            foreach ($row as $index => $cell) {
                $pdf->Cell($widths[$index], 6, $this->pdfText(Str::limit((string) $cell, 42, '')), 1, 0, 'L', true);
            }
            $pdf->Ln();
            $alternate = !$alternate;
        }
    }

    private function resolveCompanyLogoPath(?Company $company): ?string
    {
        if ($company && !empty($company->logo)) {
            $storedLogo = storage_path('app/public/' . ltrim((string) $company->logo, '/'));
            if (is_file($storedLogo)) {
                return $storedLogo;
            }
        }

        foreach ([
            public_path('images/logo-header.png'),
            public_path('images/sidebar-logo.png'),
            public_path('favicon.ico'),
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function pdfText(string $value): string
    {
        $normalized = trim(strip_tags($value));
        $encoded = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $normalized);

        return $encoded !== false ? $encoded : preg_replace('/[^\\x20-\\x7E]/', '', $normalized);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'checked_in' => 'Checked In',
            'checked_out' => 'Checked Out',
            'missed' => 'Missed',
            default => 'Pending',
        };
    }
}
