<?php

namespace App\Http\Resources\Api\V1\Company;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CompanyCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => CompanyResource::collection($this->collection),
        ];
    }
}
