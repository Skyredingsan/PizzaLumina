<?php

declare(strict_types=1);

namespace App\Modules\Report\Requests;

use App\Modules\Report\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth handled by middleware (jwt.auth + role:admin)
    }

    /**
     * @return array<string, list<string|Rule>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::enum(type: ReportType::class)],
            'parameters' => ['sometimes', 'array'],
            'parameters.from' => ['sometimes', 'string', 'date'],
            'parameters.to' => ['sometimes', 'string', 'date', 'after_or_equal:parameters.from'],
            'parameters.category' => ['sometimes', 'string'],
            'parameters.role' => ['sometimes', 'string'],
        ];
    }

    /**
     */
    public function prepareForValidation(): void
    {
        // Allow type to be passed as lowercase string
        if ($this->has(key: 'type') && is_string(value: $this->input(key: 'type'))) {
            $this->merge(input: ['type' => strtolower(string: $this->input(key: 'type'))]);
        }
    }
}
