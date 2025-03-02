<?php

namespace App\Filament\Clusters\D365\Resources\GwmsDeliveryNoteResource\Pages;

use App\Filament\Clusters\D365\Resources\GwmsDeliveryNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Filament\Actions\Action;
use App\Imports\DeliveryNotesImport;
class ListGwmsDeliveryNotes extends ListRecords
{
    protected static string $resource = GwmsDeliveryNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('excel_file')
                        ->label('Excel File')
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv'
                        ])
                        ->directory('temp-imports')
                        ->preserveFilenames()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $filePath = Storage::disk('public')->path($data['excel_file']);

                    try {
                        Excel::import(new DeliveryNotesImport, $filePath);

                        // Clean up the temporary file
                        Storage::disk('public')->delete($data['excel_file']);

                        $this->notify('success', 'Excel file imported successfully');
                    } catch (\Exception $e) {
                        // Clean up on error
                        Storage::disk('public')->delete($data['excel_file']);

                        $this->notify('danger', 'Error importing file: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
