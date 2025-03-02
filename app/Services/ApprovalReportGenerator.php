<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ApprovalFlow;

class ApprovalReportGenerator
{
    protected ApprovalAnalyticsService $analyticsService;

    public function __construct(ApprovalAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function generateReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $flowId = null
    ): string {
        $flow = $flowId ? ApprovalFlow::find($flowId) : null;
        
        $data = [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'flow' => $flow,
            'metrics' => $this->analyticsService->getApprovalMetrics($startDate, $endDate, $flowId),
            'approverPerformance' => $this->analyticsService->getApproverPerformance($startDate, $endDate, $flowId),
            'efficiency' => $this->analyticsService->getProcessEfficiency($startDate, $endDate),
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ];

        $pdf = PDF::loadView('reports.approval-analytics', $data);
        
        return $pdf->output();
    }
}