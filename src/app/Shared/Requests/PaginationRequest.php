<?php

declare(strict_types=1);

namespace App\Shared\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PaginationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxPerPage = (int) config(key: 'product.cache.max_per_page', default: 100);

        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', "max:{$maxPerPage}"],
        ];
    }

    public function getPage(): int
    {
        return (int) $this->input(key: 'page', default: 1);
    }

    public function getPerPage(int $default = 15): int
    {
        return (int) $this->input(key: 'per_page', default: $default);
    }
}
