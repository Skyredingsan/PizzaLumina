<?php

declare(strict_types=1);

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Валидация POST /api/auth/register.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:255'],
            'phone'    => [
                'required',
                'string',
                'regex:/^\+\d{10,15}$/',   // E.164: + и 10-15 цифр
                'unique:users,phone',
            ],
            'email'    => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex'    => 'Телефон должен быть в международном формате, например +79991234567.',
            'phone.unique'   => 'Пользователь с таким телефоном уже зарегистрирован.',
            'email.unique'   => 'Пользователь с таким email уже зарегистрирован.',
            'password.confirmed' => 'Пароль и подтверждение не совпадают.',
        ];
    }
}
