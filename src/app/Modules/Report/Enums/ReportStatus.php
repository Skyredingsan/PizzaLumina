<?php

declare(strict_types=1);

namespace App\Modules\Report\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    /**
     * Check if report is in a terminal state (no further transitions).
     */
    public function isTerminal(): bool
    {
        return in_array(needle: $this, haystack: [self::Completed, self::Failed], strict: true);
    }

    /**
     * Check if report can be transitioned to the given status.
     */
    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Pending => in_array(needle: $status, haystack: [self::Processing, self::Failed], strict: true),
            self::Processing => in_array(needle: $status, haystack: [self::Completed, self::Failed], strict: true),
            self::Completed, self::Failed => false,
        };
    }
}
