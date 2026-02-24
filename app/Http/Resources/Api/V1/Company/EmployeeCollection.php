<?php

namespace App\Http\Resources\Api\V1\Company;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmployeeCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => EmployeeResource::collection($this->collection),
        ];
    }
}
