<?php

namespace App\Http\Resources\Api\V1\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'address'         => $this->address,
            'contact_person'  => $this->contact_person,
            'contact_number'  => $this->contact_number,
            'status'          => $this->status,

            'counts' => [
                'employees'   => $this->whenCounted('employees'),
                'routes'      => $this->whenCounted('routes'),
                'assignments' => $this->whenCounted('assignments'),
            ],

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
