<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateVideoCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'caller_type' => ['required', 'string', Rule::in(['user', 'bus'])],
            'caller_id' => ['required', 'integer', 'min:1'],
            'callee_type' => ['required', 'string', Rule::in(['user', 'bus'])],
            'callee_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
