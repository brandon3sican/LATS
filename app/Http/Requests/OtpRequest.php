<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'otp' => 'required|digits:6',
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'The OTP code is required.',
            'otp.digits' => 'The OTP code must be exactly 6 digits.',
        ];
    }
}