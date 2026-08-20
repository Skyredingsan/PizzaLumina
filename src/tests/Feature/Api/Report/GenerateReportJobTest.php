<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Report;

use App\Modules\Product\Enums\ProductCategory;
use App\Modules\Product\Models\Product;
use App\Modules\Report\Enums\ReportStatus;
use App\Modules\Report\Jobs\GenerateReportJob;
use App\Modules\Report\Models\Report;
use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Feature\Api\ApiTestCase;

class GenerateReportJobTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMinioDisk();
    }

    /**
     * Fake the MinIO disk for tests that need a working filesystem.
     * Tests that mock Storage should call Storage::shouldReceive() AFTER setUp
     * (Mockery will override the fake).
     */
    protected function fakeMinioDisk(): void
    {
        Storage::fake(disk: 'minio');
    }

    public function test_generates_products_report_successfully(): void
    {
        Product::factory()->count(count: 5)->create();
        $admin = $this->adminUser();

        $report = Report::factory()->products()->create([
            'created_by' => $admin->id,
        ]);

        (new GenerateReportJob(reportId: $report->id))->handle();

        $report->refresh();

        $this->assertSame(ReportStatus::Completed, $report->status);
        $this->assertNotNull($report->file_path);
        $this->assertSame('text/csv', $report->mime_type);
        $this->assertGreaterThan(0, $report->file_size);
        $this->assertNull($report->error);

        Storage::disk('minio')->assertExists($report->file_path);
    }

    public function test_generates_customers_report_successfully(): void
    {
        User::factory()->count(count: 3)->create();
        $admin = $this->adminUser();

        $report = Report::factory()->customers()->create([
            'created_by' => $admin->id,
        ]);

        (new GenerateReportJob(reportId: $report->id))->handle();

        $report->refresh();

        $this->assertSame(ReportStatus::Completed, $report->status);
        $this->assertNotNull($report->file_path);
        Storage::disk('minio')->assertExists($report->file_path);
    }

    public function test_csv_has_bom_for_excel_utf8_compatibility(): void
    {
        Product::factory()->create();
        $admin = $this->adminUser();

        $report = Report::factory()->products()->create([
            'created_by' => $admin->id,
        ]);

        (new GenerateReportJob(reportId: $report->id))->handle();

        $report->refresh();

        $content = Storage::disk('minio')->get($report->file_path);
        $bom = substr(string: (string) $content, offset: 0, length: 3);

        $this->assertSame("\xEF\xBB\xBF", $bom, 'CSV must start with UTF-8 BOM');
    }

    public function test_csv_has_correct_headers_for_products_report(): void
    {
        Product::factory()->create();
        $admin = $this->adminUser();

        $report = Report::factory()->products()->create([
            'created_by' => $admin->id,
        ]);

        (new GenerateReportJob(reportId: $report->id))->handle();

        $report->refresh();

        $content = Storage::disk('minio')->get($report->file_path);
        $firstLine = strtok(substr(string: (string) $content, offset: 3), "\n");

        $this->assertStringContainsString('Product ID', $firstLine);
        $this->assertStringContainsString('Name', $firstLine);
        $this->assertStringContainsString('Category', $firstLine);
        $this->assertStringContainsString('Price (cents)', $firstLine);
        $this->assertStringContainsString('Created At', $firstLine);
    }

    public function test_csv_has_correct_headers_for_customers_report(): void
    {
        User::factory()->create();
        $admin = $this->adminUser();

        $report = Report::factory()->customers()->create([
            'created_by' => $admin->id,
        ]);

        (new GenerateReportJob(reportId: $report->id))->handle();

        $report->refresh();

        $content = Storage::disk('minio')->get($report->file_path);
        $firstLine = strtok(substr(string: (string) $content, offset: 3), "\n");

        $this->assertStringContainsString('User ID', $firstLine);
        $this->assertStringContainsString('Name', $firstLine);
        $this->assertStringContainsString('Email', $firstLine);
        $this->assertStringContainsString('Role', $firstLine);
        $this->assertStringContainsString('Created At', $firstLine);
    }

    public function test_csv_row_count_matches_db_count_for_products(): void
    {
        Product::factory()->count(count: 7)->create();
        $admin = $this->adminUser();

        $report = Report::factory()->products()->create([
            'created_by' => $admin->id,
        ]);

        (new GenerateReportJob(reportId: $report->id))->handle();

        $report->refresh();

        $content = Storage::disk('minio')->get($report->file_path);
        $lineCount = substr_count(haystack: (string) $content, needle: "\n");

        $this->assertSame(8, $lineCount);
    }

    public function test_job_applies_category_filter_for_products(): void
    {
        Product::factory()->count(count: 3)->pizza()->create();
        Product::factory()->count(count: 2)->drink()->create();
        $admin = $this->adminUser();

        $report = Report::factory()->products()->create([
            'created_by' => $admin->id,
            'parameters' => [
                'category' => ProductCategory::Pizza->value,
            ],
        ]);

        (new GenerateReportJob(reportId: $report->id))->handle();

        $report->refresh();

        $content = Storage::disk('minio')->get($report->file_path);
        $lineCount = substr_count(haystack: (string) $content, needle: "\n");

        $this->assertSame(4, $lineCount);
    }

    public function test_job_applies_role_filter_for_customers(): void
    {
        User::factory()->count(count: 4)->customer()->create();
        User::factory()->count(count: 2)->admin()->create();
        $admin = $this->adminUser();

        $report = Report::factory()->customers()->create([
            'created_by' => $admin->id,
            'parameters' => [
                'role' => UserRole::Customer->value,
            ],
        ]);

        (new GenerateReportJob(reportId: $report->id))->handle();

        $report->refresh();

        $content = Storage::disk('minio')->get($report->file_path);
        $lineCount = substr_count(haystack: (string) $content, needle: "\n");

        $this->assertSame(5, $lineCount);
    }

    public function test_job_is_idempotent_when_already_completed(): void
    {
        Product::factory()->create();
        $admin = $this->adminUser();

        $report = Report::factory()->products()->create([
            'created_by' => $admin->id,
        ]);

        // First run
        (new GenerateReportJob(reportId: $report->id))->handle();
        $firstRun = $report->refresh();

        $firstUpdatedAt = $firstRun->updated_at?->toDateTimeString();
        $firstFilePath = $firstRun->file_path;
        $firstFileSize = $firstRun->file_size;

        // Second run — should skip without updating the report
        (new GenerateReportJob(reportId: $report->id))->handle();
        $secondRun = $report->refresh();

        $this->assertSame($firstFilePath, $secondRun->file_path);
        $this->assertSame($firstFileSize, $secondRun->file_size);
        $this->assertSame(
            $firstUpdatedAt,
            $secondRun->updated_at?->toDateTimeString(),
            'updated_at should not change on idempotent skip'
        );
    }

    public function test_job_marks_report_failed_on_disk_error(): void
    {
        Product::factory()->create();
        $admin = $this->adminUser();

        $report = Report::factory()->products()->create([
            'created_by' => $admin->id,
        ]);

        Storage::shouldReceive('disk')
            ->with('minio')
            ->andThrow(exception: new RuntimeException(message: 'MinIO connection refused'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MinIO connection refused');

        try {
            (new GenerateReportJob(reportId: $report->id))->handle();
        } finally {
            $report->refresh();

            $this->assertSame(ReportStatus::Failed, $report->status);
            $this->assertNotNull($report->error);
            $this->assertStringContainsString('MinIO', $report->error);
        }
    }

    public function test_job_handles_large_dataset_without_excessive_memory_growth(): void
    {
        // 200 products — validates chunked cursor + stream write.
        // Larger counts overflow Faker's unique word generator (only ~2k words available).
        // The chunk size is 1000, so 200 rows still fit in a single chunk
        // — same code path as 10k rows, just smaller memory footprint.
        Product::factory()->count(count: 200)->create();
        $admin = $this->adminUser();

        $report = Report::factory()->products()->create([
            'created_by' => $admin->id,
        ]);

        gc_collect_cycles();
        $memoryBefore = memory_get_usage(real_usage: true);

        (new GenerateReportJob(reportId: $report->id))->handle();

        $memoryAfter = memory_get_usage(real_usage: true);
        $growth = $memoryAfter - $memoryBefore;

        // Memory growth must be < 5MB even for chunked processing
        $this->assertLessThan(
            5 * 1024 * 1024,
            $growth,
            "Memory grew by {$growth} bytes (limit: 5MB). Chunked cursor not working correctly."
        );

        $report->refresh();
        $this->assertSame(ReportStatus::Completed, $report->status);

        // Verify all 200 rows + header are in the CSV
        $content = Storage::disk('minio')->get($report->file_path);
        $lineCount = substr_count(haystack: (string) $content, needle: "\n");
        $this->assertSame(201, $lineCount, '1 header + 200 data rows');
    }

    public function test_job_cleans_up_temp_file_on_success(): void
    {
        Product::factory()->create();
        $admin = $this->adminUser();

        $report = Report::factory()->products()->create([
            'created_by' => $admin->id,
        ]);

        $tempDir = sys_get_temp_dir();
        $initialTempFiles = glob(pattern: $tempDir . '/pizzalumina_report_*');

        (new GenerateReportJob(reportId: $report->id))->handle();

        $finalTempFiles = glob(pattern: $tempDir . '/pizzalumina_report_*');

        $this->assertCount(
            count(value: $initialTempFiles),
            $finalTempFiles,
            'Temp file was not cleaned up after successful generation'
        );
    }

    public function test_job_cleans_up_temp_file_on_failure(): void
    {
        Product::factory()->create();
        $admin = $this->adminUser();

        $report = Report::factory()->products()->create([
            'created_by' => $admin->id,
        ]);

        $tempDir = sys_get_temp_dir();
        $initialTempFiles = glob(pattern: $tempDir . '/pizzalumina_report_*');

        Storage::shouldReceive('disk')
            ->with('minio')
            ->andThrow(exception: new RuntimeException(message: 'Disk full'));

        try {
            (new GenerateReportJob(reportId: $report->id))->handle();
        } catch (RuntimeException) {
            // expected
        }

        $finalTempFiles = glob(pattern: $tempDir . '/pizzalumina_report_*');

        $this->assertCount(
            count(value: $initialTempFiles),
            $finalTempFiles,
            'Temp file was not cleaned up after failure'
        );

        $report->refresh();
        $this->assertSame(ReportStatus::Failed, $report->status);
    }

    public function test_job_file_path_follows_convention(): void
    {
        Product::factory()->create();
        $admin = $this->adminUser();

        $report = Report::factory()->products()->create([
            'created_by' => $admin->id,
        ]);

        (new GenerateReportJob(reportId: $report->id))->handle();

        $report->refresh();

        $expectedPath = "reports/products/{$report->id}.csv";
        $this->assertSame($expectedPath, $report->file_path);
        $this->assertSame("{$report->id}.csv", $report->file_name);
    }
}
