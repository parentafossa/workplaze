<?php

namespace App\Filament\Clusters\DataOn\Resources;

use App\Filament\Clusters\DataOn;
use App\Filament\Clusters\DataOn\Resources\FpLocationResource\Pages;
use App\Filament\Clusters\DataOn\Resources\FpLocationResource\RelationManagers;
use App\Models\FpLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FpLocationResource extends Resource
{
    protected static ?string $model = FpLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
protected static ?string $navigationLabel = 'Attendance Machine Location';       
    protected static ?string $cluster = DataOn::class;
    protected static ?string $navigationGroup = 'LD';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('entity_id')
                    ->label('Entity')
                    ->relationship('company', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpan(['default' => 1, 'sm' => 1]),
                Forms\Components\TextInput::make('sn')
                    ->maxLength(45),
                Forms\Components\TextInput::make('ip_address')
                    ->required()
                    ->maxLength(50)
                    ->default('0.0.0.0'),
                Forms\Components\TextInput::make('site')
                    ->maxLength(45),
                Forms\Components\TextInput::make('location')
                    ->maxLength(100),
                Forms\Components\TextInput::make('host_name')
                    ->maxLength(100),
                Forms\Components\Toggle::make('active')
                    ->required()
                    ->onColor('success')
                    ->inline(false)
                    ->reactive()
                    ->columnSpan(['default' => 1, 'sm' => 1]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.short_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sn')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('site')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('host_name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('active')
                    ->icon(fn(string $state): string => match ($state) {
                        '1' => 'heroicon-o-check-circle',
                        default => ''
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListFpLocations::route('/'),
            'create' => Pages\CreateFpLocation::route('/create'),
            'edit' => Pages\EditFpLocation::route('/{record}/edit'),
        ];
    }
}
