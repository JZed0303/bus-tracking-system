<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendWebRTCOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'offer' => ['required', 'array'],
            'offer.type' => ['required', 'string', Rule::in(['offer'])],
            'offer.sdp' => ['required', 'string', 'min:64'],
        ];
    }
}
