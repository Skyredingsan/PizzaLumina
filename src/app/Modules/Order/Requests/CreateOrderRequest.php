<?php

declare(strict_types=1);

namespace App\Modules\Order\Requests;

use App\Modules\Order\DTO\Address;
use App\Modules\Order\DTO\CreateOrderInput;
use App\Modules\Order\Enums\DeliveryMethod;
use Illuminate\Foundation\Http\FormRequest;

final class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'delivery_method' => ['sometimes', 'string', 'in:' . implode(separator: ',', array: DeliveryMethod::values())],

            'address' => ['sometimes', 'array'],
            'address.region' => ['required_with:address', 'string', 'min:2', 'max:100'],
            'address.city' => ['required_with:address', 'string', 'min:2', 'max:100'],
            'address.street' => ['required_with:address', 'string', 'min:2', 'max:200'],
            'address.building' => ['required_with:address', 'string', 'min:1', 'max:20'],
            'address.entrance' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address.apartment' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address.zip' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'delivery_method.in' => (string) trans(key: 'order.invalid_delivery_method'),
        ];
    }

    public function toCreateOrderInput(): CreateOrderInput
    {
        $deliveryMethod = $this->has(key: 'delivery_method')
            ? DeliveryMethod::from(value: $this->string(key: 'delivery_method')->toString())
            : DeliveryMethod::Courier;

        $addressData = $this->input(key: 'address');
        $address = is_array(value: $addressData) ? Address::fromArray(data: $addressData) : null;

        return new CreateOrderInput(
            deliveryMethod: $deliveryMethod,
            address: $address,
        );
    }
}
