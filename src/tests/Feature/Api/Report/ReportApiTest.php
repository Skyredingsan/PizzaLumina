<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Report;

use App\Modules\Report\Enums\ReportStatus;
use App\Modules\Report\Enums\ReportType;
use App\Modules\Report\Jobs\GenerateReportJob;
use App\Modules\Report\Models\Report;
use App\Modules\User\Enums\UserRole;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

class ReportApiTest extends ApiTestCase
{
    public function test_guest_cannot_list_reports(): void
    {
        $this->getJson($this->getApiUrl('/reports'))
            ->assertStatus(status: Response::HTTP_UNAUTHORIZED);
    }

    public function test_guest_cannot_create_report(): void
    {
        $this->postJson($this->getApiUrl('/reports'), [
            'type' => ReportType::Sales->value,
        ])
            ->assertStatus(status: Response::HTTP_UNAUTHORIZED);
    }

    public function test_customer_cannot_list_reports(): void
    {
        $this->withToken($this->customerToken())
            ->getJson($this->getApiUrl('/reports'))
            ->assertStatus(status: Response::HTTP_FORBIDDEN);
    }

    public function test_customer_cannot_create_report(): void
    {
        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/reports'), [
                'type' => ReportType::Sales->value,
            ])
            ->assertStatus(status: Response::HTTP_FORBIDDEN);
    }

    public function test_admin_can_list_own_reports(): void
    {
        $admin = $this->adminUser();
        $otherAdmin = $this->createUser(role: UserRole::Admin);

        Report::create([
            'type' => ReportType::Sales,
            'status' => ReportStatus::Completed,
            'created_by' => $admin->id,
        ]);
        Report::create([
            'type' => ReportType::Products,
            'status' => ReportStatus::Pending,
            'created_by' => $admin->id,
        ]);
        Report::create([
            'type' => ReportType::Sales,
            'status' => ReportStatus::Completed,
            'created_by' => $otherAdmin->id,
        ]);

        $this->withToken($this->adminToken())
            ->getJson($this->getApiUrl('/reports'))
            ->assertOk()
            ->assertJsonCount(count: 2, key: 'data')
            ->assertJsonStructure(structure: [
                'data' => [
                    '*' => ['id', 'type', 'status', 'parameters', 'file_name', 'file_size', 'mime_type', 'error', 'created_by', 'created_at', 'updated_at'],
                ],
                'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
            ]);
    }

    public function test_admin_can_create_report_and_dispatches_job(): void
    {
        Bus::fake();

        $response = $this->withToken($this->adminToken())
            ->postJson($this->getApiUrl('/reports'), [
                'type' => ReportType::Sales->value,
                'parameters' => [
                    'from' => '2025-01-01',
                    'to' => '2025-01-31',
                ],
            ]);

        $response->assertStatus(status: Response::HTTP_ACCEPTED)
            ->assertJsonPath(path: 'data.type', expect: ReportType::Sales->value)
            ->assertJsonPath(path: 'data.status', expect: ReportStatus::Pending->value)
            ->assertJsonStructure(structure: [
                'data' => ['id', 'type', 'status', 'parameters', 'created_by', 'created_at'],
            ]);

        $this->assertDatabaseHas('reports', [
            'type' => ReportType::Sales->value,
            'status' => ReportStatus::Pending->value,
            'created_by' => $this->adminUser()->id,
        ]);

        Bus::assertDispatched(GenerateReportJob::class);
    }

    public function test_admin_can_show_own_report(): void
    {
        $admin = $this->adminUser();           // creates admin A, caches token
        $report = Report::factory()->sales()->completed()->create([
            'created_by' => $admin->id,         // SAME admin A
            'file_name' => 'test.csv',
        ]);

        $this->withToken($this->adminToken())   // token for admin A
        ->getJson($this->getApiUrl("/reports/{$report->id}"))
            ->assertOk()
            ->assertJsonPath(path: 'data.id', expect: $report->id)
            ->assertJsonPath(path: 'data.type', expect: ReportType::Sales->value)
            ->assertJsonPath(path: 'data.status', expect: ReportStatus::Completed->value)
            ->assertJsonPath(path: 'data.file_name', expect: 'test.csv');
    }

    public function test_admin_cannot_see_other_admin_report(): void
    {
        $otherAdmin = $this->createUser(role: UserRole::Admin);
        $report = Report::create([
            'type' => ReportType::Sales,
            'status' => ReportStatus::Completed,
            'created_by' => $otherAdmin->id,
        ]);

        $this->withToken($this->adminToken())
            ->getJson($this->getApiUrl("/reports/{$report->id}"))
            ->assertStatus(status: Response::HTTP_NOT_FOUND);
    }

    public function test_admin_cannot_download_pending_report(): void
    {
        $admin = $this->adminUser();
        $report = Report::create([
            'type' => ReportType::Sales,
            'status' => ReportStatus::Pending,
            'created_by' => $admin->id,
        ]);

        $this->withToken($this->adminToken())
            ->getJson($this->getApiUrl("/reports/{$report->id}/download"))
            ->assertStatus(status: Response::HTTP_CONFLICT)
            ->assertJsonPath(path: 'status', expect: ReportStatus::Pending->value);
    }

    public function test_admin_cannot_download_processing_report(): void
    {
        $admin = $this->adminUser();
        $report = Report::create([
            'type' => ReportType::Sales,
            'status' => ReportStatus::Processing,
            'created_by' => $admin->id,
        ]);

        $this->withToken($this->adminToken())
            ->getJson($this->getApiUrl("/reports/{$report->id}/download"))
            ->assertStatus(status: Response::HTTP_CONFLICT);
    }

    public function test_admin_cannot_download_failed_report(): void
    {
        $admin = $this->adminUser();
        $report = Report::create([
            'type' => ReportType::Sales,
            'status' => ReportStatus::Failed,
            'error' => 'MinIO unreachable',
            'created_by' => $admin->id,
        ]);

        $this->withToken($this->adminToken())
            ->getJson($this->getApiUrl("/reports/{$report->id}/download"))
            ->assertStatus(status: Response::HTTP_CONFLICT);
    }

    public function test_admin_cannot_download_completed_report_with_missing_file(): void
    {
        Storage::fake(disk: 'minio');

        $admin = $this->adminUser();
        $report = Report::create([
            'type' => ReportType::Sales,
            'status' => ReportStatus::Completed,
            'file_path' => 'reports/sales/non-existent.csv',
            'file_name' => 'non-existent.csv',
            'created_by' => $admin->id,
        ]);

        $this->withToken($this->adminToken())
            ->getJson($this->getApiUrl("/reports/{$report->id}/download"))
            ->assertStatus(status: Response::HTTP_NOT_FOUND);
    }

    public function test_admin_can_download_completed_report_with_file(): void
    {
        Storage::fake(disk: 'minio');

        $admin = $this->adminUser();
        $filePath = 'reports/sales/test-report.csv';

        Storage::disk('minio')->put($filePath, "test,data\n1,2\n");

        $report = Report::create([
            'type' => ReportType::Sales,
            'status' => ReportStatus::Completed,
            'file_path' => $filePath,
            'file_name' => 'test-report.csv',
            'file_size' => 12,
            'mime_type' => 'text/csv',
            'created_by' => $admin->id,
        ]);

        $this->withToken($this->adminToken())
            ->getJson($this->getApiUrl("/reports/{$report->id}/download"))
            ->assertOk()
            ->assertJsonStructure(structure: ['url', 'expires_at'])
            ->assertJsonPath(path: 'url', expect: fn ($url): bool => is_string(value: $url) && str_contains(haystack: $url, needle: 'test-report.csv'));
    }

    public function test_download_cannot_be_accessed_by_other_admin(): void
    {
        $otherAdmin = $this->createUser(role: UserRole::Admin);
        $report = Report::create([
            'type' => ReportType::Sales,
            'status' => ReportStatus::Completed,
            'created_by' => $otherAdmin->id,
        ]);

        $this->withToken($this->adminToken())
            ->getJson($this->getApiUrl("/reports/{$report->id}/download"))
            ->assertStatus(status: Response::HTTP_NOT_FOUND);
    }

    public function test_validation_fails_on_invalid_type(): void
    {
        $this->withToken($this->adminToken())
            ->postJson($this->getApiUrl('/reports'), [
                'type' => 'invalid-type',
            ])
            ->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(errors: ['type']);
    }

    public function test_validation_fails_on_missing_type(): void
    {
        $this->withToken($this->adminToken())
            ->postJson($this->getApiUrl('/reports'), [])
            ->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(errors: ['type']);
    }

    public function test_validation_fails_when_to_date_is_before_from_date(): void
    {
        $this->withToken($this->adminToken())
            ->postJson($this->getApiUrl('/reports'), [
                'type' => ReportType::Sales->value,
                'parameters' => [
                    'from' => '2025-01-31',
                    'to' => '2025-01-01',
                ],
            ])
            ->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(errors: ['parameters.to']);
    }

    public function test_pagination_works(): void
    {
        $admin = $this->adminUser();
        Report::factory()->count(count: 15)->create(attributes: [
            'created_by' => $admin->id,
        ]);

        $this->withToken($this->adminToken())
            ->getJson($this->getApiUrl('/reports?per_page=10'))
            ->assertOk()
            ->assertJsonCount(count: 10, key: 'data')
            ->assertJsonPath(path: 'meta.per_page', expect: 10)
            ->assertJsonPath(path: 'meta.total', expect: 15)
            ->assertJsonPath(path: 'meta.last_page', expect: 2);
    }
}
