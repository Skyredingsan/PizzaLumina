<?php

declare(strict_types=1);

namespace App\Modules\User\Requests;

use App\Modules\User\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['role' => ['required', 'string', new Enum(type: UserRole::class)]];
    }
}
