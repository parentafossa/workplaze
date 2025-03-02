<?php

namespace App\Filament\Clusters\D365\Resources\GwmsDeliveryNoteResource\Pages;

use App\Filament\Clusters\D365\Resources\GwmsDeliveryNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewGwmsDeliveryNote extends ViewRecord
{
    protected static string $resource = GwmsDeliveryNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Basic Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('system_id'),
                        Infolists\Components\TextEntry::make('site_cd'),
                        Infolists\Components\TextEntry::make('site_name'),
                        Infolists\Components\TextEntry::make('owner_cd'),
                        Infolists\Components\TextEntry::make('owner_name'),
                        Infolists\Components\TextEntry::make('st_no')
                            ->label('S/T No'),
                        Infolists\Components\TextEntry::make('status'),
                    ])->columns(2),

                Infolists\Components\Section::make('Shipping Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('ship_to_cd'),
                        Infolists\Components\TextEntry::make('ship_to_name'),
                        Infolists\Components\TextEntry::make('ship_to_adr1'),
                        Infolists\Components\TextEntry::make('ship_to_adr2'),
                        Infolists\Components\TextEntry::make('etd')
                            ->label('ETD')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('eta')
                            ->label('ETA')
                            ->dateTime(),
                    ])->columns(2),

                Infolists\Components\Section::make('Transport Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('truck_cd'),
                        Infolists\Components\TextEntry::make('truck_name'),
                        Infolists\Components\TextEntry::make('truck_no'),
                        Infolists\Components\TextEntry::make('sj_barcode'),
                    ])->columns(2),

                Infolists\Components\Section::make('Quantities and Measurements')
                    ->schema([
                        Infolists\Components\TextEntry::make('ctn_qty')
                            ->numeric(),
                        Infolists\Components\TextEntry::make('bulk_m3')
                            ->numeric(
                                decimalPlaces: 2,
                                thousandsSeparator: ',',
                            ),
                        Infolists\Components\TextEntry::make('wgt_kg')
                            ->numeric(
                                decimalPlaces: 2,
                                thousandsSeparator: ',',
                            ),
                        Infolists\Components\TextEntry::make('sj_qty')
                            ->numeric(),
                    ])->columns(2),

                Infolists\Components\Section::make('Receipt Information')
                    ->schema([
                        Infolists\Components\IconEntry::make('sj_receipt_print_flg')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('sj_receipt_print_flg_name'),
                        Infolists\Components\TextEntry::make('sj_receipt_print_user_id'),
                        Infolists\Components\TextEntry::make('sj_receipt_print_user_name'),
                        Infolists\Components\TextEntry::make('sj_receipt_print_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('sj_receipt_print_time'),
                        Infolists\Components\TextEntry::make('sj_received_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('sj_received_user'),
                    ])->columns(2),

                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('lt')
                            ->label('LT'),
                        Infolists\Components\TextEntry::make('due_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('remarks'),
                    ])->columns(2),
            ]);
    }
}
