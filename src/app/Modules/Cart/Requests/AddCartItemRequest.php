<?php

declare(strict_types=1);

namespace App\Modules\Cart\Requests;

use App\Modules\Cart\DTO\AddToCartInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
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
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => 'Выбранный товар не найден.',
            'quantity.min' => 'Количество должно быть не менее 1.',
            'quantity.max' => 'Нельзя добавить более 99 единиц одного товара за один запрос.',
        ];
    }

    public function toAddToCartInput(): AddToCartInput
    {
        return new AddToCartInput(
            productId: $this->integer(key: 'product_id'),
            quantity: $this->integer(key: 'quantity', default: 1),
        );
    }
}
