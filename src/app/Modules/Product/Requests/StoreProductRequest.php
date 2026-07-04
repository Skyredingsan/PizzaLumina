<?php

declare(strict_types=1);

namespace App\Modules\Product\Requests;

use App\Modules\Product\Enums\ProductCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:products,name'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'weight' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'category' => ['required', 'string', new Enum(ProductCategory::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Продукт с таким названием уже существует.',
            'price.min' => 'Цена должна быть больше нуля.',
            'category.Illuminate\Validation\Rules\Enum' => 'Категория должна быть одной из: пицца, напиток.',
        ];
    }
}
