<?php

declare(strict_types=1);

namespace Tests\Unit\Report;

use App\Modules\Report\Enums\ReportStatus;
use App\Modules\Report\Enums\ReportType;
use PHPUnit\Framework\TestCase;

class ReportStatusEnumTest extends TestCase
{
    public function test_is_terminal_returns_true_for_completed(): void
    {
        $this->assertTrue(ReportStatus::Completed->isTerminal());
    }

    public function test_is_terminal_returns_true_for_failed(): void
    {
        $this->assertTrue(ReportStatus::Failed->isTerminal());
    }

    public function test_is_terminal_returns_false_for_pending(): void
    {
        $this->assertFalse(ReportStatus::Pending->isTerminal());
    }

    public function test_is_terminal_returns_false_for_processing(): void
    {
        $this->assertFalse(ReportStatus::Processing->isTerminal());
    }

    public function test_pending_can_transition_to_processing(): void
    {
        $this->assertTrue(ReportStatus::Pending->canTransitionTo(status: ReportStatus::Processing));
    }

    public function test_pending_can_transition_to_failed(): void
    {
        $this->assertTrue(ReportStatus::Pending->canTransitionTo(status: ReportStatus::Failed));
    }

    public function test_pending_cannot_transition_to_completed(): void
    {
        $this->assertFalse(ReportStatus::Pending->canTransitionTo(status: ReportStatus::Completed));
    }

    public function test_processing_can_transition_to_completed(): void
    {
        $this->assertTrue(ReportStatus::Processing->canTransitionTo(status: ReportStatus::Completed));
    }

    public function test_processing_can_transition_to_failed(): void
    {
        $this->assertTrue(ReportStatus::Processing->canTransitionTo(status: ReportStatus::Failed));
    }

    public function test_processing_cannot_transition_back_to_pending(): void
    {
        $this->assertFalse(ReportStatus::Processing->canTransitionTo(status: ReportStatus::Pending));
    }

    public function test_completed_cannot_transition_to_anything(): void
    {
        $this->assertFalse(ReportStatus::Completed->canTransitionTo(status: ReportStatus::Pending));
        $this->assertFalse(ReportStatus::Completed->canTransitionTo(status: ReportStatus::Processing));
        $this->assertFalse(ReportStatus::Completed->canTransitionTo(status: ReportStatus::Failed));
    }

    public function test_failed_cannot_transition_to_anything(): void
    {
        $this->assertFalse(ReportStatus::Failed->canTransitionTo(status: ReportStatus::Pending));
        $this->assertFalse(ReportStatus::Failed->canTransitionTo(status: ReportStatus::Processing));
        $this->assertFalse(ReportStatus::Failed->canTransitionTo(status: ReportStatus::Completed));
    }

    public function test_report_type_queue_name_returns_reports_generate(): void
    {
        $this->assertSame('reports.generate', ReportType::Sales->queueName());
        $this->assertSame('reports.generate', ReportType::Products->queueName());
        $this->assertSame('reports.generate', ReportType::Customers->queueName());
    }
}
