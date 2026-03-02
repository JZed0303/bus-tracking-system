<?php

namespace App\Http\Resources\Api\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class EmployeeTripStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'employee' => [
                'id'            => $this['employee']->id,
                'employee_code' => $this['employee']->employee_code,
                'full_name'     => $this['employee']->user?->full_name,
                'profile_image' => $this['employee']->photo_url,
                'department'    => $this['employee']->department, // ✅ ADD THIS
                'company'       => $this['employee']->company?->name,
            ],

            'checkin' => $this['checkin'] ? [
                'time'      => $this['checkin']->scan_time,
                'latitude'  => $this['checkin']->scan_lat,
                'longitude' => $this['checkin']->scan_lng,
            ] : null,

            'checkout' => $this['checkout'] ? [
                'time'      => $this['checkout']->scan_time,
                'latitude'  => $this['checkout']->scan_lat,
                'longitude' => $this['checkout']->scan_lng,
            ] : null,

            'status' => $this['status'],
        ];
    }
}
