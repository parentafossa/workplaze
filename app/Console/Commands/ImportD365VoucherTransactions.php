<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Imports\VoucherTransactionImporter;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\D365VoucherTransactionImport;
use Illuminate\Support\Facades\DB;

class ImportD365VoucherTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:d365-vouchers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import D365 voucher transactions from public/imports/d365vouchers folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $publicPath = 'imports/d365vouchers';
        $privatePath = 'private/imports/d365vouchers';

        // Ensure directories exist
        if (!Storage::disk('public')->exists($publicPath)) {
            Storage::disk('public')->makeDirectory($publicPath);
        }
        if (!Storage::exists($privatePath)) {
            Storage::makeDirectory($privatePath);
        }

        // Get all files in the public directory
        $files = Storage::disk('public')->files($publicPath);

        if (empty($files)) {
            $this->info('No files found to import.');
            return;
        }

        Log::info(sprintf('Found %d files to import.', count($files)));
        $filecount = 0;
        $totalfiles = count($files);

        foreach ($files as $file) {
            $filecount++;
            Log::info(sprintf('Processing file (%s/%s): %s', $filecount, $totalfiles, basename($file)));

            try {
                // Get full path for the file
                $fullPath = Storage::disk('public')->path($file);

                // Import the file
                //Excel::import(new VoucherTransactionImporter, $fullPath);
                D365VoucherTransactionImport::truncate();

                (new VoucherTransactionImporter)->withOutput($this->output)->import($fullPath);
                // Move original file to private storage with timestamp
                $fileName = basename($file);
                $newPath = $privatePath . '/' . date('Y-m-d_His_') . $fileName;

                // Read and store in private location
                $fileContent = Storage::disk('public')->get($file);
                Storage::put($newPath, $fileContent);

                // Delete from public location after successful move
                Storage::disk('public')->delete($file);
                Log::info(sprintf('Successfully processed and moved: %s', $fileName));

                /* $dates = DB::table('d365_voucher_transaction_imports')
                    ->selectRaw('MIN(`date`) as min_date, MAX(`date`) as max_date')
                    ->first();

                if ($dates->min_date && $dates->max_date) {
                    // Delete records from D365_voucher_transaction within the date range
                    DB::table('d365_voucher_transactions')
                        ->whereBetween('date', [$dates->min_date, $dates->max_date])
                        ->delete();

                    // Insert all data from D365_voucher_transaction_imports
                    DB::table('d365_voucher_transactions')
                        ->insertUsing(
                            DB::getSchemaBuilder()->getColumnListing('d365_voucher_transactions'),
                            DB::table('d365_voucher_transaction_imports')
                        );
                } */

            } catch (\Exception $e) {
                Log::error(sprintf('Error processing file %s: %s', basename($file), $e->getMessage()));

                // Move failed files to an error directory
                $errorPath = $privatePath . '/errors/' . date('Y-m-d_His_') . basename($file);
                if (!Storage::exists(dirname($errorPath))) {
                    Storage::makeDirectory(dirname($errorPath));
                }

                try {
                    $fileContent = Storage::disk('public')->get($file);
                    Storage::put($errorPath, $fileContent);
                    Storage::disk('public')->delete($file);
                    Log::info(sprintf('Moved failed file to error directory: %s', basename($file)));
                } catch (\Exception $moveError) {
                    Log::error(sprintf('Could not move failed file: %s', $moveError->getMessage()));
                }
            }
        }
    }
}