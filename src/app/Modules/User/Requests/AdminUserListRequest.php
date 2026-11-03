<?php

declare(strict_types=1);

namespace App\Modules\User\Requests;

use App\Modules\User\DTO\AdminUserListInput;
use Illuminate\Foundation\Http\FormRequest;

final class AdminUserListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['per_page' => ['sometimes', 'integer', 'min:1', 'max:100']];
    }
    public function toInput(): AdminUserListInput
    {
        return new AdminUserListInput(perPage: $this->integer(key: 'per_page', default: 15));
    }
}
