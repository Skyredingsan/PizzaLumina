<?php

declare(strict_types=1);

namespace App\Modules\Report\Console\Commands;

use App\Modules\Report\Enums\ReportStatus;
use App\Modules\Report\Enums\ReportType;
use App\Modules\Report\Jobs\GenerateReportJob;
use App\Modules\Report\Models\Report;
use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Illuminate\Console\Command;
use RuntimeException;

class ScheduleDailyReportCommand extends Command
{
    protected $signature = 'reports:schedule-daily';
    protected $description = 'Schedule daily sales report for yesterday (for cron at 02:00)';

    public function handle(): int
    {
        $systemUserId = (int) config(key: 'report.system_user_id', default: 1);
        $systemUser = User::query()->whereKey(id: $systemUserId)->where(column: 'role', operator: UserRole::Admin->value)->first();

        if ($systemUser === null) {
            throw new RuntimeException(message: 'Configured system report user was not found or is not an admin.');
        }

        $report = Report::create([
            'type' => ReportType::Sales,
            'status' => ReportStatus::Pending,
            'parameters' => [
                'from' => now()->subDay()->startOfDay()->toDateTimeString(),
                'to' => now()->subDay()->endOfDay()->toDateTimeString(),
                'scheduled' => true,
            ],
            'created_by' => $systemUserId,
        ]);

        dispatch(job: new GenerateReportJob(reportId: $report->id))
            ->onConnection(connection: config(key: 'report.queue.connection'))
            ->onQueue(queue: config(key: 'report.queue.queue'));

        $this->info(string: "Scheduled daily report: {$report->id}");

        return self::SUCCESS;
    }
}
