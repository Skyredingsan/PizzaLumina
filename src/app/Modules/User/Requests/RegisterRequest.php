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
                'regex:/^\+\d{10,15}$/',
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
            'phone.regex'        => 'Телефон должен быть в международном формате, например +79991234567.',
            'phone.unique'       => 'Пользователь с таким телефоном уже зарегистрирован.',
            'email.unique'       => 'Пользователь с таким email уже зарегистрирован.',
            'password.confirmed' => 'Пароль и подтверждение не совпадают.',
        ];
    }

    /**
     * @return array{name: string, phone: string, email: string, password: string}
     */
    public function toRegisterInput(): array
    {
        return [
            'name'     => $this->string('name')->toString(),
            'phone'    => $this->string('phone')->toString(),
            'email'    => $this->string('email')->toString(),
            'password' => $this->string('password')->toString(),
        ];
    }
}
