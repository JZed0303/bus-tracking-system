<?php

namespace App\Http\Resources\Api\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class EmployeeTripStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $employee = $this['employee'];
        $checkin = $this['checkin'];
        $checkout = $this['checkout'];

        return [
            'employee' => [
                'id' => (int) $employee->id,
                'employee_code' => (string) ($employee->employee_code ?? 'N/A'),
                'full_name' => (string) ($employee->user?->full_name ?? 'N/A'),
                'profile_image' => $employee->photo_url,
                'department' => (string) ($employee->department ?? 'N/A'),
                'company' => (string) ($employee->company?->name ?? 'N/A'),
            ],

            'checkin' => $checkin ? [
                'time' => optional($checkin->scan_time)?->toIso8601String(),
                'latitude' => $checkin->scan_lat !== null ? (float) $checkin->scan_lat : null,
                'longitude' => $checkin->scan_lng !== null ? (float) $checkin->scan_lng : null,
            ] : null,

            'checkout' => $checkout ? [
                'time' => optional($checkout->scan_time)?->toIso8601String(),
                'latitude' => $checkout->scan_lat !== null ? (float) $checkout->scan_lat : null,
                'longitude' => $checkout->scan_lng !== null ? (float) $checkout->scan_lng : null,
            ] : null,

            'status' => (string) ($this['status'] ?? 'not_scanned'),
        ];
    }
}
