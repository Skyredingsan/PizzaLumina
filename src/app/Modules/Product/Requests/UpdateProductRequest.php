<?php

declare(strict_types=1);

namespace App\Modules\Product\Requests;

use App\Modules\Product\Enums\ProductCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255', 'unique:products,name'],
            'description' => ['sometimes', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0.01', 'max:999999.99'],
            'weight' => ['sometimes', 'numeric', 'min:0.01', 'max:9999.99'],
            'category' => ['sometimes', 'string', new Enum(type: ProductCategory::class)],
        ];
    }
}
