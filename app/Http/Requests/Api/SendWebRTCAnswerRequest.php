<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendWebRTCAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'answer' => ['required', 'array'],
            'answer.type' => ['required', 'string', Rule::in(['answer'])],
            'answer.sdp' => ['required', 'string', 'min:64'],
        ];
    }
}
