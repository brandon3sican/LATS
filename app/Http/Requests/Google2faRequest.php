<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Google2faRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The verification code is required.',
        ];
    }

    public function isRecoveryCode(): bool
    {
        $code = $this->input('code');
        return strlen($code) === 8 && ctype_upper($code) && ctype_alnum($code);
    }
}