<?php

declare(strict_types=1);

namespace App\Modules\Report\Console\Commands;

use App\Modules\Report\Jobs\PublishReportCompletedJob;
use App\Modules\Report\Models\ReportOutbox;
use Illuminate\Console\Command;

final class PublishReportOutboxCommand extends Command
{
    protected $signature = 'reports:publish-outbox {--limit=100}';
    protected $description = 'Publish pending report outbox events to RabbitMQ';

    public function handle(): int
    {
        ReportOutbox::query()
            ->whereNull('published_at')
            ->orderBy(column: 'id')
            ->limit(value: (int) $this->option(key: 'limit'))
            ->get()
            ->each(callback: function (ReportOutbox $event): void {
                dispatch(job: new PublishReportCompletedJob(reportId: $event->report_id))
                    ->onConnection(connection: config(key: 'report.queue.connection'))
                    ->onQueue(queue: config(key: 'report.queue.completed_queue'));
                $event->update(attributes: ['published_at' => now()]);
            });

        return self::SUCCESS;
    }
}
