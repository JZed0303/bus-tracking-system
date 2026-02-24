<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusLoginRequest extends FormRequest
{
    /**
     * Determine if the bus device is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Bus login does not require prior authentication
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * These MUST match what the mobile app sends.
     */
    public function rules(): array
    {
        return [
            'plate_number' => ['required', 'string'],
            'bus_code'     => ['required', 'string'],
        ];
    }

    /**
     * Optional: Custom error messages (recommended for API clarity)
     */
    public function messages(): array
    {
        return [
            'plate_number.required' => 'Plate number is required',
            'bus_code.required'     => 'Bus code is required',
        ];
    }
}
