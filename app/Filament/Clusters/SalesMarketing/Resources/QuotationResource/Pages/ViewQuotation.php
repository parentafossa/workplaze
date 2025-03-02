<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\QuotationResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\QuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('send')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'draft')
                ->action(fn () => $this->record->update(['status' => 'sent'])),
            Actions\Action::make('confirm')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'sent')
                ->action(fn () => $this->record->update(['status' => 'confirmed'])),
            Actions\Action::make('reject')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'sent')
                ->action(fn () => $this->record->update(['status' => 'rejected'])),
            Actions\DeleteAction::make(),
        ];
    }
}
