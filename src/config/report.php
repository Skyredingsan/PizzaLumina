<?php

declare(strict_types=1);

return [
    'queue' => [
        'completed_queue' => env('REPORT_COMPLETED_QUEUE_NAME', 'reports.completed'),
        'connection' => env('REPORT_QUEUE_CONNECTION', 'rabbitmq'),
        'queue' => env('REPORT_QUEUE_NAME', 'reports.generate'),
    ],
    'download' => [
        'url_expires_minutes' => (int) env('REPORT_DOWNLOAD_URL_EXPIRES', 5),
    ],
];
