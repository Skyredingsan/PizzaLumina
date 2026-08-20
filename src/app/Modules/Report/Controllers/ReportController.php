<?php

declare(strict_types=1);

namespace App\Modules\Report\Controllers;

use App\Modules\Report\Enums\ReportStatus;
use App\Modules\Report\Jobs\GenerateReportJob;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Requests\StoreReportRequest;
use App\Modules\Report\Resources\ReportResource;
use App\Shared\Requests\PaginationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * List reports created by the authenticated admin.
     */
    public function index(PaginationRequest $request): AnonymousResourceCollection
    {
        $page = (int) $request->validated(key: 'page', default: 1);
        $perPage = (int) $request->validated(key: 'per_page', default: 10);

        $reports = Report::query()
            ->where(column: 'created_by', operator: Auth::id())->latest()
            ->paginate(perPage: $perPage, columns: ['*'], page: $page);

        return ReportResource::collection(resource: $reports);
    }

    /**
     * Show a single report (only if owned by the authenticated admin).
     */
    public function show(string $id): ReportResource
    {
        $report = Report::query()
            ->where(column: 'id', operator: $id)
            ->where(column: 'created_by', operator: Auth::id())
            ->firstOrFail();

        return new ReportResource(resource: $report);
    }

    /**
     * Create a new report and dispatch generation to the queue.
     * Returns 202 Accepted — report will be generated asynchronously.
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $data = $request->validated();

        $report = Report::create([
            'type' => $data['type'],
            'status' => ReportStatus::Pending,
            'parameters' => $data['parameters'] ?? null,
            'created_by' => Auth::id(),
        ]);

        // Dispatch to configured connection (rabbitmq in prod, sync in tests)
        dispatch(job: new GenerateReportJob(reportId: $report->id))
            ->onConnection(connection: config(key: 'report.queue.connection'))
            ->onQueue(queue: config(key: 'report.queue.queue'));

        return (new ReportResource(resource: $report))
            ->response()
            ->setStatusCode(code: 202);
    }

    /**
     * Get a temporary download URL for a completed report.
     * Returns 200 with presigned URL, 409 if not ready, 404 if file missing.
     */
    public function download(string $id): JsonResponse
    {
        $report = Report::query()
            ->where(column: 'id', operator: $id)
            ->where(column: 'created_by', operator: Auth::id())
            ->firstOrFail();

        if ($report->status !== ReportStatus::Completed) {
            return response()->json([
                'message' => 'Report is not completed yet',
                'status' => $report->status->value,
            ], 409);
        }

        if (!$report->fileExists()) {
            return response()->json([
                'message' => 'Report file not found in storage',
            ], 404);
        }

        $expiresMinutes = (int) config(key: 'report.download.url_expires_minutes', default: 5);
        $url = $report->temporaryDownloadUrl(expiresMinutes: $expiresMinutes);

        return response()->json([
            'url' => $url,
            'expires_at' => now()->addMinutes($expiresMinutes)->toISOString(),
        ]);
    }
}
