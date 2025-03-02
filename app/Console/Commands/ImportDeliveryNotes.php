<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Imports\DeliveryNotesImport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Settings;

class ImportDeliveryNotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:delivery-notes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import delivery notes from public/imports/deliverynotes folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $publicPath = 'imports/deliverynotes';
        $privatePath = 'private/imports/deliverynotes';

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
        $filecount=0;
        $totalfiles=count($files);
        foreach ($files as $file) {
            $filecount = $filecount+1;
            Log::info(sprintf('Processing file (%s/%s): %s', $filecount, $totalfiles, basename($file)));

            try {
                // Get full path for the file
                $fullPath = Storage::disk('public')->path($file);

                // Import the file
                Excel::import(new DeliveryNotesImport, $fullPath);
                //(new DeliveryNotesImport)->withOutput($this->output)->import($fullPath);
                // Move original file to private storage with timestamp
                $fileName = basename($file);
                $newPath = $privatePath . '/' . date('Y-m-d_His_') . $fileName;

                // Read and store in private location
                $fileContent = Storage::disk('public')->get($file);
                Storage::put($newPath, $fileContent);

                // Delete from public location after successful move
                Storage::disk('public')->delete($file);

                Log::info(sprintf('Successfully processed and moved: %s', $fileName));
                //Log::info(sprintf('Delivery Notes import completed for file: %s', $fileName));

            } catch (\Exception $e) {
                Log::error(sprintf('Error processing file %s: %s', basename($file), $e->getMessage()));
                //Log::error(sprintf('Delivery Notes import failed for file %s: %s', basename($file), $e->getMessage()));

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
