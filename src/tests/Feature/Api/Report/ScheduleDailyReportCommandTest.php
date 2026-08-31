<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Report;

use App\Modules\Report\Enums\ReportStatus;
use App\Modules\Report\Enums\ReportType;
use App\Modules\Report\Jobs\GenerateReportJob;
use App\Modules\Report\Models\Report;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Api\ApiTestCase;

class ScheduleDailyReportCommandTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->admin()->create(['id' => 1]);
    }

    public function test_command_creates_report_with_sales_type(): void
    {
        Queue::fake();

        $this->artisan('reports:schedule-daily')
            ->assertSuccessful()
            ->expectsOutputToContain(string: 'Scheduled daily report:');

        $this->assertDatabaseHas('reports', [
            'type' => ReportType::Sales->value,
            'status' => ReportStatus::Pending->value,
        ]);
    }

    public function test_command_sets_yesterday_date_range(): void
    {
        Queue::fake();

        $this->artisan('reports:schedule-daily');

        $report = Report::first();

        $this->assertNotNull($report);
        $this->assertNotNull($report->parameters);

        $yesterday = now()->subDay()->startOfDay();
        $yesterdayEnd = now()->subDay()->endOfDay();

        $this->assertSame(
            $yesterday->toDateTimeString(),
            $report->parameters['from']
        );
        $this->assertSame(
            $yesterdayEnd->toDateTimeString(),
            $report->parameters['to']
        );
        $this->assertTrue($report->parameters['scheduled']);
    }

    public function test_command_dispatches_job_to_correct_queue(): void
    {
        Queue::fake();

        $this->artisan('reports:schedule-daily');

        Queue::assertPushed(GenerateReportJob::class, 1);
        Queue::assertPushedOn(config(key: 'report.queue.queue'), GenerateReportJob::class);
    }

    public function test_command_sets_created_by_to_system_user(): void
    {
        Queue::fake();

        $this->artisan('reports:schedule-daily');

        $report = Report::first();

        $this->assertNotNull($report);
        $this->assertSame(1, $report->created_by);
    }
}
