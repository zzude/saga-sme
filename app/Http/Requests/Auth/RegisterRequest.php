<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'company_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Nama diperlukan.',
            'email.required'        => 'Email diperlukan.',
            'email.unique'          => 'Email ini telah didaftarkan.',
            'password.required'     => 'Kata laluan diperlukan.',
            'password.min'          => 'Kata laluan mesti sekurang-kurangnya 8 aksara.',
            'password.confirmed'    => 'Pengesahan kata laluan tidak sepadan.',
            'company_name.required' => 'Nama syarikat diperlukan.',
        ];
    }
}
