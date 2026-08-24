<?php

declare(strict_types=1);

namespace App\Modules\Report\Models;

use Illuminate\Database\Eloquent\Model;

final class ReportOutbox extends Model
{
    protected $table = 'report_outbox';

    protected $fillable = ['report_id', 'event', 'payload', 'published_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
