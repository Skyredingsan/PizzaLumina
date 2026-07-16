<?php

declare(strict_types=1);

namespace App\Modules\Cart\Requests;

use App\Modules\Cart\DTO\UpdateCartItemInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Укажите новое количество.',
            'quantity.min' => 'Количество должно быть не менее 1. Для удаления используйте DELETE.',
            'quantity.max' => 'Нельзя установить более 99 единиц одного товара.',
        ];
    }

    public function toUpdateCartItemInput(): UpdateCartItemInput
    {
        return new UpdateCartItemInput(
            quantity: $this->integer(key: 'quantity'),
        );
    }
}
