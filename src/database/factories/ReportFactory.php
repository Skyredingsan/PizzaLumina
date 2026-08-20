<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Report\Enums\ReportStatus;
use App\Modules\Report\Enums\ReportType;
use App\Modules\Report\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(ReportType::cases()),
            'status' => ReportStatus::Pending,
            'parameters' => null,
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
            'mime_type' => null,
            'error' => null,
            'created_by' => null, // must be set explicitly in tests
        ];
    }

    public function sales(): static
    {
        return $this->state(state: fn (array $attributes): array => [
            'type' => ReportType::Sales,
        ]);
    }

    public function products(): static
    {
        return $this->state(state: fn (array $attributes): array => [
            'type' => ReportType::Products,
        ]);
    }

    public function customers(): static
    {
        return $this->state(state: fn (array $attributes): array => [
            'type' => ReportType::Customers,
        ]);
    }

    public function pending(): static
    {
        return $this->state(state: fn (array $attributes): array => [
            'status' => ReportStatus::Pending,
        ]);
    }

    public function completed(): static
    {
        return $this->state(state: fn (array $attributes): array => [
            'status' => ReportStatus::Completed,
            'file_path' => 'reports/sales/test.csv',
            'file_name' => 'test.csv',
            'file_size' => 1024,
            'mime_type' => 'text/csv',
        ]);
    }

    public function failed(): static
    {
        return $this->state(state: fn (array $attributes): array => [
            'status' => ReportStatus::Failed,
            'error' => 'Test error',
        ]);
    }
}
