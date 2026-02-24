<?php

namespace App\Http\Resources\Api\V1\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'employee_code' => $this->employee_code,
            'department'    => $this->department,
            'position'      => $this->position,
            'status'        => $this->status,

            'user' => [
                'id'         => $this->user?->id,
                'first_name' => $this->user?->first_name,
                'last_name'  => $this->user?->last_name,
                'full_name'  => $this->user?->full_name,
                'email'      => $this->user?->email,
            ],

            'company' => [
                'id'   => $this->company?->id,
                'name' => $this->company?->name,
            ],

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
