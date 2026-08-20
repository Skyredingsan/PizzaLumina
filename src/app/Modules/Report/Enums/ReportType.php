<?php

declare(strict_types=1);

namespace App\Modules\Report\Enums;

enum ReportType: string
{
    case Sales = 'sales';
    case Products = 'products';
    case Customers = 'customers';

    /**
     * Get the queue name for this report type.
     */
    public function queueName(): string
    {
        return 'reports.generate';
    }
}
