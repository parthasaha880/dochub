<?php

namespace App\Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPasswordResetOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'otp' => ['required', 'string', 'size:6'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $email = strtolower(trim((string) $this->input('email')));
            $email = preg_replace('/\.(ocm|con|cpm|comm)$/i', '.com', $email) ?: $email;

            $this->merge(['email' => $email]);
        }

        if ($this->has('otp')) {
            $this->merge(['otp' => preg_replace('/\s+/', '', (string) $this->input('otp'))]);
        }
    }
}
