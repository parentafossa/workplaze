<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupApprovalReports extends Command
{
    protected $signature = 'approvals:cleanup-reports {--older-than=90}';
    protected $description = 'Clean up old approval reports';

    public function handle(): void
    {
        $days = $this->option('older-than');
        $disk = Storage::disk('reports');
        $cutoffDate = now()->subDays($days);
        $count = 0;

        foreach ($disk->files() as $file) {
            // Extract date from filename using regex
            if (preg_match('/\d{4}-\d{2}-\d{2}/', $file, $matches)) {
                $fileDate = Carbon::createFromFormat('Y-m-d', $matches[0]);
                
                if ($fileDate->isBefore($cutoffDate)) {
                    $disk->delete($file);
                    $count++;
                }
            }
        }

        $this->info("Cleaned up {$count} report(s) older than {$days} days");
    }
}