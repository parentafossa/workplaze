<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\PdfToText\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class RenamePurchaseOrderPDF extends Command
{
    protected $signature = 'pdf:rename {path? : Path to PDF file or directory}';
    protected $description = 'Rename PO PDF files using PO number and vendor name';

    public function handle()
    {
        // Get the provided path or use default
        $path = $this->argument('path') ?? 'uploads/purchase_orders';
        
        // Try both private and public storage
        $privatePath = '/' . trim($path, '/');
        
        if (!Storage::exists($privatePath)) {
            $this->error("Path not found: {$path}");
            $this->info("Tried storage path: " . Storage::path($privatePath));
            return 1;
        }

        $this->info("Processing directory: " . Storage::path($privatePath));

        // Get all PDF files
        $files = $this->getPDFFiles($privatePath);
        
        if (empty($files)) {
            $this->warn("No PDF files found in the directory.");
            return;
        }

        $this->info("Found " . count($files) . " PDF files.");

        foreach ($files as $file) {
            $this->processPDFFile($file);
        }

        $this->info('PDF processing completed!');
    }

    protected function getPDFFiles($path)
    {
        if (Str::endsWith(strtolower($path), '.pdf')) {
            return [$path];
        }

        // Get all files in directory
        $allFiles = Storage::files($path);
        
        // Filter for PDF files
        return array_filter($allFiles, function($file) {
            return Str::endsWith(strtolower($file), '.pdf');
        });
    }


    protected function processPDFFile($filePath)
    {
        try {
            // Get PDF content
            $pdfContent = (new Pdf())
                ->setPdf(Storage::path($filePath))
                ->text();

            // Extract PO number and vendor name
            $poNumber = $this->extractPONumber($pdfContent);
            $vendorName = $this->extractVendorName($pdfContent);

            if (!$poNumber || !$vendorName) {
                $this->warn("Could not extract required information from: {$filePath}");
                $this->line("PO Number: " . ($poNumber ?? 'Not found'));
                $this->line("Vendor Name: " . ($vendorName ?? 'Not found'));
                return;
            }

            // Generate new filename
            $newFileName = $this->generateNewFileName($poNumber, $vendorName);
            $newFilePath = dirname($filePath) . '/' . $newFileName;

            // Rename file
            if (Storage::exists($newFilePath)) {
                $this->warn("File already exists: {$newFileName}");
                return;
            }

            Storage::move($filePath, $newFilePath);
            $this->info("Renamed: {$filePath} → {$newFileName}");

        } catch (\Exception $e) {
            $this->error("Error processing {$filePath}: " . $e->getMessage());
        }
    }

    protected function extractPONumber($content)
    {
        if (preg_match('/Number\s+(\d{3}-PO\d{7}-\d)/', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function extractVendorName($content)
    {
        // Split content into lines
        $lines = explode("\n", $content);
        
        // Find the line containing "Supplier account"
        foreach ($lines as $key => $line) {
            if (str_contains($line, 'Supplier account')) {
                // Get the next line which should contain the vendor name
                if (isset($lines[$key + 1])) {
                    $vendorName = trim($lines[$key + 1]);
                    // Remove any trailing/leading special characters or excessive spaces
                    $vendorName = preg_replace('/[^\w\s\.-]/', '', $vendorName);
                    return $vendorName;
                }
                break;
            }
        }
        return null;
    }

    protected function generateNewFileName($poNumber, $vendorName)
    {
        // Clean vendor name
        $cleanVendorName = Str::of($vendorName)
            ->replaceMatches('/[\/\\\:\*\?"<>\|]/', '_') // Remove invalid filename characters
            ->replace(' ', '_')
            ->trim('_')
            ->toString();

        return "{$poNumber}_{$cleanVendorName}.pdf";
    }
}