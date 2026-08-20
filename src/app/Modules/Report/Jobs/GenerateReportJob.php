<?php

declare(strict_types=1);

namespace App\Modules\Report\Jobs;

use App\Modules\Order\Models\OrderItem;
use App\Modules\Product\Models\Product;
use App\Modules\Report\Enums\ReportStatus;
use App\Modules\Report\Enums\ReportType;
use App\Modules\Report\Models\Report;
use App\Modules\User\Models\User;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Single attempt — we handle failure manually via try/catch
     * and don't want duplicate report generation.
     */
    public int $tries = 1;

    /**
     * Max execution time in seconds (5 minutes).
     */
    public int $timeout = 300;

    public function __construct(
        public string $reportId
    ) {
    }

    public function handle(): void
    {
        $report = Report::findOrFail($this->reportId);

        // Idempotency: skip if already completed/failed (e.g., retry after crash)
        if ($report->status->isTerminal()) {
            Log::info('Report already in terminal state, skipping', [
                'report_id' => $report->id,
                'status' => $report->status->value,
            ]);
            return;
        }

        $report->update(['status' => ReportStatus::Processing]);

        try {
            $filePath = $this->generate(report: $report);
            $fileSize = Storage::disk(Report::DISK)->size($filePath);

            $report->update([
                'status' => ReportStatus::Completed,
                'file_path' => $filePath,
                'file_name' => basename(path: $filePath),
                'file_size' => $fileSize,
                'mime_type' => $report->type === ReportType::Sales ? 'application/jsonl' : 'text/csv',
                'error' => null,
            ]);

            dispatch(job: new PublishReportCompletedJob(reportId: $report->id))
                ->onConnection(connection: config(key: 'report.queue.connection'))
                ->onQueue(queue: config(key: 'report.queue.completed_queue'));

            Log::info('Report generated successfully', [
                'report_id' => $report->id,
                'type' => $report->type->value,
                'file_size' => $fileSize,
            ]);
        } catch (Throwable $e) {
            Log::error('Report generation failed', [
                'report_id' => $report->id,
                'type' => $report->type->value,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            $report->update([
                'status' => ReportStatus::Failed,
                'error' => mb_substr(string: $e->getMessage(), start: 0, length: 1000),
            ]);

            throw $e;
        }
    }

    /**
     * Generate the CSV report and upload to MinIO.
     * Uses chunked cursor + stream write for memory efficiency.
     *
     * @throws RuntimeException On disk write/upload failure
     */
    private function generate(Report $report): string
    {
        $filePath = sprintf(
            'reports/%s/%s.%s',
            $report->type->value,
            $report->id,
            $report->type === ReportType::Sales ? 'jsonl' : 'csv'
        );

        $tempPath = tempnam(directory: sys_get_temp_dir(), prefix: 'pizzalumina_report_');

        try {
            $handle = fopen(filename: $tempPath, mode: 'w');
            if ($handle === false) {
                throw new RuntimeException(message: 'Failed to open temp file for writing: ' . $tempPath);
            }

            if ($report->type !== ReportType::Sales) {
                // BOM and header are kept for spreadsheet reports.
                fwrite(stream: $handle, data: "\xEF\xBB\xBF");
                fputcsv(stream: $handle, fields: $this->getHeader(type: $report->type));
            }

            // Stream data using chunked cursor (memory-efficient: loads 1000 rows at a time)
            $this->buildQuery(report: $report)->chunkById(count: 1000, callback: function ($chunk) use ($handle, $report): void {
                foreach ($chunk as $model) {
                    $row = $this->formatRow(model: $model, type: $report->type);
                    if ($report->type === ReportType::Sales) {
                        fwrite(stream: $handle, data: json_encode(value: $row, flags: JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . PHP_EOL);
                    } else {
                        fputcsv(stream: $handle, fields: $row);
                    }
                }
            });

            fclose(stream: $handle);
            $handle = null;

            // Upload to MinIO via stream resource (avoids loading entire file into memory)
            $resource = fopen(filename: $tempPath, mode: 'r');
            if ($resource === false) {
                throw new RuntimeException(message: 'Failed to open temp file for reading: ' . $tempPath);
            }

            try {
                $uploaded = Storage::disk(Report::DISK)->put($filePath, $resource);
                if ($uploaded === false) {
                    throw new RuntimeException(message: 'MinIO upload failed for path: ' . $filePath);
                }
            } finally {
                fclose(stream: $resource);
            }

            return $filePath;
        } finally {
            if (isset($handle) && is_resource(value: $handle)) {
                fclose(stream: $handle);
            }
            if (file_exists(filename: $tempPath)) {
                @unlink(filename: $tempPath);
            }
        }
    }

    /**
     * Get CSV header columns for the report type.
     *
     * @return string[]
     */
    private function getHeader(ReportType $type): array
    {
        return match ($type) {
            ReportType::Sales => ['Order ID', 'Date', 'Customer Email', 'Status', 'Total (cents)'],
            ReportType::Products => ['Product ID', 'Name', 'Category', 'Price (cents)', 'Created At'],
            ReportType::Customers => ['User ID', 'Name', 'Email', 'Role', 'Created At'],
        };
    }

    /**
     * Build the query for the report type, applying date/category/role filters from parameters.
     */
    private function buildQuery(Report $report): Builder
    {
        $parameters = $report->parameters ?? [];

        return match ($report->type) {
            ReportType::Sales => $this->buildSalesQuery(parameters: $parameters),
            ReportType::Products => $this->buildProductsQuery(parameters: $parameters),
            ReportType::Customers => $this->buildCustomersQuery(parameters: $parameters),
        };
    }

    /** @param array<string, mixed> $parameters */
    private function buildSalesQuery(array $parameters): Builder
    {
        $query = OrderItem::query()->with(relations: 'order.user');

        if (isset($parameters['from']) && $parameters['from']) {
            $query->whereHas(relation: 'order', callback: fn (Builder $order): Builder => $order->where(column: 'created_at', operator: '>=', value: $parameters['from']));
        }
        if (isset($parameters['to']) && $parameters['to']) {
            $query->whereHas(relation: 'order', callback: fn (Builder $order): Builder => $order->where(column: 'created_at', operator: '<=', value: $parameters['to']));
        }

        return $query->orderBy('id');
    }

    /** @param array<string, mixed> $parameters */
    private function buildProductsQuery(array $parameters): Builder
    {
        $query = Product::query();

        if (isset($parameters['category']) && $parameters['category']) {
            $query->where(column: 'category', operator: $parameters['category']);
        }

        return $query->orderBy('id');
    }

    /** @param array<string, mixed> $parameters */
    private function buildCustomersQuery(array $parameters): Builder
    {
        $query = User::query();

        if (isset($parameters['role']) && $parameters['role']) {
            $query->where(column: 'role', operator: $parameters['role']);
        }

        return $query->orderBy('id');
    }

    /**
     * Format a model as a CSV row.
     *
     * @return array<int, string|int|float|null>
     */
    private function formatRow(OrderItem|Product|User $model, ReportType $type): array
    {
        return match ($type) {
            ReportType::Sales => [
                'product_name' => $model->product_name,
                'price' => $model->unit_price,
                'amount' => $model->quantity,
                'user' => ['id' => $model->order?->user_id],
            ],
            ReportType::Products => [
                $model->getKey(),
                $model->name,
                $this->formatEnum(value: $model->category),
                $this->formatMoney(value: $model->price),
                $this->formatDate(date: $model->created_at),
            ],
            ReportType::Customers => [
                $model->getKey(),
                $model->name,
                $model->email,
                $this->formatEnum(value: $model->role),
                $this->formatDate(date: $model->created_at),
            ],
        };
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date === null) {
            return null;
        }
        return $date instanceof DateTimeInterface
            ? $date->format('Y-m-d H:i:s')
            : (string) $date;
    }

    private function formatEnum(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return is_object(value: $value) && enum_exists(enum: $value::class)
            ? $value->value
            : (string) $value;
    }

    private function formatMoney(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_object(value: $value) && method_exists(object_or_class: $value, method: 'getAmount')) {
            return (string) $value->getAmount();
        }
        return (string) $value;
    }
}
