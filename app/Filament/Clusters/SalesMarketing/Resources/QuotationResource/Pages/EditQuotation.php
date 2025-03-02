<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\QuotationResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\QuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Prevent status change during edit if not allowed
        if (in_array($this->record->status, ['confirmed', 'rejected'])) {
            $data['status'] = $this->record->status;
        }

        return $data;
    }
}
