<?php

namespace App\Jobs;

use App\Imports\D365VoucherImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\D365VoucherTransactionImport;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
class ImportVoucherJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    private $filePath;
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Import started');
        Excel::import(new D365VoucherImport(), $this->filePath);
        Log::info('Import completed');
        $rowcount = D365VoucherTransactionImport::count();

        $recipient = auth()->user();
        Notification::make()
            ->title('Import Completed')
            ->body("File '$this->filePath' has been imported successfully $rowcount rows.")
            ->broadcast($recipient);
        Log::info("import complete message sent");
    }
}
