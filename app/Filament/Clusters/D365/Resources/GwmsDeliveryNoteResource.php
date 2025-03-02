<?php

namespace App\Filament\Clusters\D365\Resources;

use App\Filament\Clusters\D365;
use App\Filament\Clusters\D365\Resources\GwmsDeliveryNoteResource\Pages;
use App\Filament\Clusters\D365\Resources\GwmsDeliveryNoteResource\RelationManagers;
use App\Models\GwmsDeliveryNote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use DateTime;

class GwmsDeliveryNoteResource extends Resource
{
    protected static ?string $model = GwmsDeliveryNote::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Delivery Notes';
    protected static ?string $navigationGroup = 'GWMS';
    protected static ?string $cluster = D365::class;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site_cd')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('site_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('st_no')
                    ->label('S/T No')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('etd')
                    ->label('ETD')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('eta')
                    ->label('ETA')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ship_to_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('truck_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ctn_qty')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bulk_m3')
                    ->numeric(
                        decimalPlaces: 2,
                        thousandsSeparator: ',',
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('wgt_kg')
                    ->numeric(
                        decimalPlaces: 2,
                        thousandsSeparator: ',',
                    )
                    ->sortable(),
                Tables\Columns\IconColumn::make('sj_receipt_print_flg')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sj_qty')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('etd', 'desc')
            ->filters([
                SelectFilter::make('site_cd')
                    ->options(
                        GwmsDeliveryNote::distinct()
                            ->pluck('site_name', 'site_cd')
                            ->map(function ($siteName, $siteCd) {
                                return "$siteCd - $siteName";
                            })
                    ),
                SelectFilter::make('status')
                    ->options(
                        GwmsDeliveryNote::distinct()
                            ->pluck('status', 'status')
                            ->toArray()
                    ),
                Filter::make('etd')
                    ->form([
                        Forms\Components\DatePicker::make('etd_from'),
                        Forms\Components\DatePicker::make('etd_to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['etd_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('etd', '>=', $date),
                            )
                            ->when(
                                $data['etd_to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('etd', '<=', $date),
                            );
                    }),
                Filter::make('eta')
                    ->form([
                        Forms\Components\DatePicker::make('eta_from'),
                        Forms\Components\DatePicker::make('eta_to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['eta_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('eta', '>=', $date),
                            )
                            ->when(
                                $data['eta_to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('eta', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGwmsDeliveryNotes::route('/'),
            //'create' => Pages\CreateGwmsDeliveryNote::route('/create'),
            'view' => Pages\ViewGwmsDeliveryNote::route('/{record}/edit'),

        ];
    }
}
