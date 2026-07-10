<?php

declare(strict_types=1);

namespace App\Modules\User\Requests;

use App\Modules\User\DTO\RegisterInput;
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
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => [
                'required',
                'string',
                'regex:/^\+[1-9]\d{6,14}$/',
                'unique:users,phone',
            ],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(size: 8)->mixedCase()->numbers()->symbols(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Телефон должен быть в международном формате, например +79991234567.',
            'phone.unique' => 'Пользователь с таким телефоном уже зарегистрирован.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            'password.confirmed' => 'Пароль и подтверждение не совпадают.',
        ];
    }

    public function toRegisterInput(): RegisterInput
    {
        return new RegisterInput(
            name: $this->string(key: 'name')->toString(),
            phone: $this->string(key: 'phone')->toString(),
            email: $this->string(key: 'email')->toString(),
            password: $this->string(key: 'password')->toString(),
        );
    }
}
