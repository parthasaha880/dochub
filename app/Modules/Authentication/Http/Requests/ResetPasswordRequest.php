<?php

namespace App\Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['nullable', 'string'],
            'otp' => ['required_without:token', 'nullable', 'string', 'size:6'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $email = strtolower(trim((string) $this->input('email')));
            $email = preg_replace('/\.(ocm|con|cpm|comm)$/i', '.com', $email) ?: $email;

            $this->merge([
                'email' => $email,
            ]);
        }

        if ($this->has('otp')) {
            $this->merge([
                'otp' => preg_replace('/\s+/', '', (string) $this->input('otp')),
            ]);
        }
    }
}
