<?php

namespace App\Filament\Clusters\DataOn\Resources;

use App\Filament\Clusters\DataOn;
use App\Filament\Clusters\DataOn\Resources\DataonIFAttLogResource\Pages;
use App\Filament\Clusters\DataOn\Resources\DataonIFAttLogResource\RelationManagers;
use App\Models\DataonIFAttLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
class DataonIFAttLogResource extends Resource
{
    protected static ?string $model = DataonIFAttLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = DataOn::class;
    protected static ?string $navigationLabel = 'Attendance I/F';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'LD';
    protected static ?string $modelLabel = 'Attendance Interface Log';
    protected static ?string $pluralModelLabel = 'Attendance Interface Logs';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DateTimePicker::make('created_at')
                ->columnSpanFull(),
                TextInput::make('process_name')
                    ->columnSpanFull(),
                Textarea::make('remark')
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime(),
                TextColumn::make('process_name'),
                TextColumn::make('remark')
                    ->searchable()
                    ->color(fn(string $state): string => str_contains($state, 'Error') ? 'red' : 'info'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                //Tables\Actions\EditAction::make(),
                //Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                //Tables\Actions\BulkActionGroup::make([
                //    Tables\Actions\DeleteBulkAction::make(),
                //]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDataonIFAttLogs::route('/'),
        ];
    }
}
