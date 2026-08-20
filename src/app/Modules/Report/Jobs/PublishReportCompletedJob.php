<?php

declare(strict_types=1);

namespace App\Modules\Report\Jobs;

use App\Modules\Report\Enums\ReportStatus;
use App\Modules\Report\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class PublishReportCompletedJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public string $reportId)
    {
    }

    public function handle(): void
    {
        $report = Report::findOrFail($this->reportId);

        if ($report->status !== ReportStatus::Completed) {
            return;
        }

        Log::info('Report completed event consumed', [
            'report_id' => $report->id,
            'type' => $report->type->value,
            'status' => $report->status->value,
            'file_path' => $report->file_path,
            'file_size' => $report->file_size,
        ]);
    }
}
