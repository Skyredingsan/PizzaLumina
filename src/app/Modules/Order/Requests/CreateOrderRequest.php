<?php

declare(strict_types=1);

namespace App\Modules\Order\Requests;

use App\Modules\Order\DTO\Address;
use App\Modules\Order\DTO\CreateOrderInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
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
            'address' => ['required', 'array'],
            'address.region' => ['required', 'string', 'max:100'],
            'address.city' => ['required', 'string', 'max:100'],
            'address.street' => ['required', 'string', 'max:200'],
            'address.building' => ['required', 'string', 'max:20'],
            'address.entrance' => ['sometimes', 'nullable', 'string', 'max:10'],
            'address.apartment' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address.zip' => ['sometimes', 'nullable', 'string', 'max:12'],
        ];
    }

    public function messages(): array
    {
        return [
            'address.required' => 'Укажите адрес доставки.',
            'address.region.required' => 'Укажите регион (область/край).',
            'address.city.required' => 'Укажите город.',
            'address.street.required' => 'Укажите улицу.',
            'address.building.required' => 'Укажите номер дома.',
        ];
    }

    public function toCreateOrderInput(): CreateOrderInput
    {
        /**
         * @var array{region: string, city: string, street: string, building: string,
         *     entrance?: string|null, apartment?: string|null, zip?: string|null} $addr
         */
        $addr = $this->input(key: 'address', default: []);

        return new CreateOrderInput(
            address: Address::fromArray(data: $addr),
        );
    }
}
