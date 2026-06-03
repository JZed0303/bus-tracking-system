<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendIceCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'sender_type' => ['required', 'string', Rule::in(['user', 'bus'])],
            'sender_id' => ['required', 'integer', 'min:1'],
            'candidate' => ['required', 'array'],
            'candidate.candidate' => ['required', 'string'],
            'candidate.sdpMid' => ['nullable', 'string'],
            'candidate.sdpMLineIndex' => ['nullable', 'integer'],
        ];
    }
}
