<?php

namespace App\Filament\Clusters\UM\Resources;

use App\Filament\Clusters\UM;
use App\Filament\Clusters\UM\Resources\DriverLogResource\Pages;
use App\Filament\Clusters\UM\Resources\DriverLogResource\RelationManagers;

use App\Models\DriverLog;
use App\Models\Driver;
use App\Models\CompanyCar;

use Filament\Resources\Resource;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Tables\Filters\Filter;


class DriverLogResource extends Resource
{
    protected static ?string $model = DriverLog::class;
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'lineawesome-truck-loading-solid';

    protected static ?string $cluster = UM::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Select::make('emp_id')
                ->label('Employee')
                ->relationship('assignment', 'driver_id')
                //->relationship('driver.emp_name')
                ->searchable()
                ->required(),
            Select::make('driveraction_id')
                ->label('Driver Action')
                ->relationship('activity', 'name') // Fetches the name of the driver activity
                ->required(),
            TextInput::make('driveraction_type')
                ->label('Driver Action Type')
                ->required(),
            DateTimePicker::make('driver_timestamp')
                ->label('Timestamp')
                ->required(),
            TextInput::make('truck_no')
                ->label('Truck Number')
                ->required(),
            TextInput::make('device_info')
                ->label('Device Info')
                ->maxLength(255),
            TextInput::make('latitude')
                ->label('Latitude')
                ->numeric(),
            TextInput::make('longitude')
                ->label('Longitude')
                ->numeric(),
            TextInput::make('accuracy')
                ->label('Accuracy')
                ->numeric(),
            TextInput::make('altitude')
                ->label('Altitude')
                ->numeric(),
            TextInput::make('speed')
                ->label('Speed')
                ->numeric(),
            TextInput::make('address')
                ->label('Address')
                ->maxLength(255),
            TextInput::make('remark')
                ->label('Remark')
                ->maxLength(500),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            TextColumn::make('assignment.driver.emp_name')
                ->label('Driver ID')
                ->searchable(),
            TextColumn::make('activity.name')
                ->label('Driver Action')
                ->sortable(),
            TextColumn::make('driveraction_type')
                ->label('Driver Action Type')
                ->searchable(),
            TextColumn::make('driver_timestamp')
                ->label('Activity TimeStamp')
                ->sortable(),
            TextColumn::make('truck_no')
                ->label('Truck Number')
                ->searchable(),
            /*TextColumn::make('device_info')
                ->label('Device Info')
                ->limit(20),
            TextColumn::make('latitude')
                ->label('Latitude'),
            TextColumn::make('longitude')
                ->label('Longitude'),
            TextColumn::make('accuracy')
                ->label('Accuracy'),
            TextColumn::make('altitude')
                ->label('Altitude'),
            TextColumn::make('speed')
                ->label('Speed'),
            TextColumn::make('address')
                ->label('Address')
                ->limit(30),
            */
            TextColumn::make('remark')
                ->label('Remark')
                ->limit(50),
        ])
            ->filters([
                //
                Filter::make('truck_no')
                ->label('Truck No')
                ->form([
                    Select::make('truck_no')
                        ->label('Select Truck No')
                        ->options(CompanyCar::query()->pluck('license_plate', 'license_plate'))
                        ->multiple()
                        ->preload()
                ])
                ->query(function (Builder $query, array $data) {
                    //Log::info($data);
                    return $query->when($data['truck_no'] ?? null, 
                    fn($query, $truckNos) => $query->whereIn('truck_no', $truckNos));
                }),
                Filter::make('emp_id')
                ->label('Driver')
                ->form([
                    Select::make('emp_id')
                        ->label('Select Driver')
                        ->options(Driver::query()->pluck('emp_name', 'emp_id'))
                        ->multiple()
                        ->preload()
                ])
                ->query(function (Builder $query, array $data) {
                    return $query->when($data['emp_id'] ?? null, function ($query, $driverIds) {
                        $query->whereIn('emp_id', $driverIds);
                    });
                }),
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
            'index' => Pages\ListDriverLogs::route('/'),
            'create' => Pages\CreateDriverLog::route('/create'),
            'edit' => Pages\EditDriverLog::route('/{record}/edit'),
        ];
    }
}
