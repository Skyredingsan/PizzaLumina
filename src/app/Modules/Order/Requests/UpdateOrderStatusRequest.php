<?php

declare(strict_types=1);

namespace App\Modules\Order\Requests;

use App\Modules\Order\Enums\OrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderStatusRequest extends FormRequest
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
            'status' => ['required', 'string', new Enum(type: OrderStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Укажите новый статус заказа.',
            'status.Illuminate\Validation\Rules\Enum' => 'Неизвестный статус заказа.',
        ];
    }

    public function toStatus(): OrderStatus
    {
        /** @var string $value */
        $value = $this->string(key: 'status')->toString();

        return OrderStatus::from(value: $value);
    }
}
