<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RegContractnumber;
use Illuminate\Support\Facades\Storage;

class ReorganizeContractFiles extends Command
{
    protected $signature = 'contracts:reorganize-files';
    protected $description = 'Reorganize contract files into company/fiscal structure';

    public function handle()
    {
        $this->info('Starting file reorganization...');
        
        $bar = $this->output->createProgressBar(RegContractnumber::whereNotNull('document_file')->count());
        $bar->start();

        $success = 0;
        $failed = 0;
        $skipped = 0;

        RegContractnumber::whereNotNull('document_file')->chunk(100, function ($contracts) use (&$success, &$failed, &$skipped, $bar) {
            foreach ($contracts as $contract) {
                try {
                    $result = $this->reorganizeFiles($contract);
                    
                    if ($result === true) $success++;
                    elseif ($result === false) $failed++;
                    else $skipped++;
                    
                    $bar->advance();
                    
                } catch (\Exception $e) {
                    $this->error("Error processing contract ID {$contract->id}: " . $e->getMessage());
                    $failed++;
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine();
        
        $this->info("Reorganization completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $success],
                ['Failed', $failed],
                ['Skipped', $skipped],
            ]
        );
    }

    protected function reorganizeFiles($contract)
    {
        // Handle both string and array input
        $files = $contract->document_file;
        if (is_string($files)) {
            $files = json_decode($files, true);
        }
        
        if (!is_array($files) || empty($files)) {
            return null; // Skip if no files
        }

        $newFiles = [];
        $originalNames = [];
        
        foreach ($files as $file) {
            // Handle both old and new format
            if (is_array($file)) {
                // Old format with download_link and original_name
                $oldPath = $file['download_link'] ?? '';
                $originalName = $file['original_name'] ?? basename($oldPath);
            } else {
                // New format with just the path
                $oldPath = $file;
                $originalName = basename($oldPath);
            }

            if (empty($oldPath)) continue;

            // Create new path
            $newFileName = basename($oldPath);
            $newPath = "reg-contractnumbers/{$contract->company_id}/{$contract->fiscal}/{$newFileName}";
            
            // Check and move file if it exists
            if (Storage::disk('public')->exists($oldPath)) {
                // Ensure directory exists
                Storage::disk('public')->makeDirectory(dirname($newPath));
                
                // Move file to new location
                if (Storage::disk('public')->exists($newPath)) {
                    Storage::disk('public')->delete($newPath); // Remove if exists
                }
                
                Storage::disk('public')->move($oldPath, $newPath);
                
                $newFiles[] = $newPath;
                $originalNames[$newPath] = $originalName;
                
                $this->info("Moved file: {$oldPath} to {$newPath}");
            } else {
                // If file doesn't exist but path was in database
                $newFiles[] = $oldPath; // Keep old path
                $originalNames[$oldPath] = $originalName;
                $this->warn("File not found: {$oldPath} for contract ID {$contract->id}");
            }
        }

        if (!empty($newFiles)) {
            $contract->update([
                'document_file' => json_encode($newFiles),
                'original_names' => json_encode($originalNames)
            ]);
            return true;
        }

        return false;
    }
}