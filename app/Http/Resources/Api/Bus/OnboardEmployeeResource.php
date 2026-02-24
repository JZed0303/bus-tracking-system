<?php

namespace App\Http\Resources\Api\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardEmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'employee_id'   => $this->employee->id,
            'employee_code' => $this->employee->employee_code,

            'name' => $this->employee->user->full_name,
            'department' => $this->employee->department,
            'position'   => $this->employee->position,

            'company' => [
                'id'   => $this->employee->company->id,
                'name' => $this->employee->company->name,
            ],

            'checked_in_at' => $this->scan_time->toDateTimeString(),
        ];
    }
}
