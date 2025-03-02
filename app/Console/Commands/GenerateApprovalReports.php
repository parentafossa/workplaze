<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ApprovalFlow;
use App\Services\ApprovalReportGenerator;
use App\Mail\ApprovalAnalyticsReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class GenerateApprovalReports extends Command
{
    protected $signature = 'approvals:generate-reports {--period=weekly} {--recipients=*}';
    protected $description = 'Generate and email approval analytics reports';

    public function handle(ApprovalReportGenerator $reportGenerator): void
    {
        $period = $this->option('period');
        $recipients = $this->option('recipients');

        if (empty($recipients)) {
            // Default to users with specific roles
            $recipients = User::role(['admin', 'supervisor', 'manager'])->pluck('email')->toArray();
        }

        [$startDate, $endDate] = $this->getDateRange($period);

        // Generate overall report
        $this->generateAndSendReport(
            $reportGenerator,
            $startDate,
            $endDate,
            null,
            'Overall',
            $recipients
        );

        // Generate per-flow reports
        ApprovalFlow::where('is_active', true)->each(function ($flow) use ($reportGenerator, $startDate, $endDate, $recipients) {
            $this->generateAndSendReport(
                $reportGenerator,
                $startDate,
                $endDate,
                $flow->id,
                $flow->name,
                $recipients
            );
        });
    }

    protected function getDateRange(string $period): array
    {
        $endDate = now();
        
        $startDate = match($period) {
            'daily' => $endDate->copy()->subDay(),
            'weekly' => $endDate->copy()->subWeek(),
            'monthly' => $endDate->copy()->subMonth(),
            default => $endDate->copy()->subWeek(),
        };

        return [$startDate, $endDate];
    }

    protected function generateAndSendReport(
        ApprovalReportGenerator $reportGenerator,
        Carbon $startDate,
        Carbon $endDate,
        ?string $flowId,
        string $reportName,
        array $recipients
    ): void {
        try {
            $pdf = $reportGenerator->generateReport($startDate, $endDate, $flowId);
            
            $filename = sprintf(
                'approval-analytics-%s-%s-to-%s.pdf',
                strtolower(str_replace(' ', '-', $reportName)),
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            // Store the report
            Storage::disk('reports')->put($filename, $pdf);

            // Send email
            foreach ($recipients as $email) {
                Mail::to($email)->queue(new ApprovalAnalyticsReport(
                    storage_path("app/reports/{$filename}"),
                    $reportName,
                    $startDate,
                    $endDate
                ));
            }

            $this->info("Generated and sent {$reportName} report to " . count($recipients) . " recipients");
        } catch (\Exception $e) {
            $this->error("Error generating {$reportName} report: " . $e->getMessage());
        }
    }
}