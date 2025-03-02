<?php

namespace App\Filament\Clusters\UM\Resources;

use App\Filament\Clusters\UM;

use App\Filament\Clusters\UM\Resources\TripDistanceResource\Pages;

use App\Models\TripDistance;
use App\Models\TripDestination;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Forms\Get;

use Filament\Tables\Table;

use Filament\Resources\Resource;

use Filament\Notifications\Notification;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class TripDistanceResource extends Resource
{
    protected static ?string $model = TripDistance::class;
    protected static ?string $navigationIcon = 'lineawesome-ruler-combined-solid';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $cluster = UM::class;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('origin')
                    ->label('Origin')
                    ->relationship('originLocation', 'name', fn (Builder $query): Builder => $query->orderBy('name', 'asc'))
                    ->required()
                    ->live()
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('New Origin')
                            ->required()
                            ->maxLength(100)
                    ])
/*                     ->options(function () {
                        return TripDestination::pluck('name', 'id')->map(fn($name) => ucwords($name));
                    }) */
                    ->preload()
                    ->afterStateUpdated(function (Set $set, $state, Get $get) {
                            // Convert to Proper Case for display
                            $set('name', ucwords(strtolower($state)));
                            //Log::info('origin: ' . $get('origin') . ' ' . $get('destination'));
                                    if ($get('origin') === $get('destination')) {
            Notification::make()
                ->title('Origin and Destination must be different')
                ->danger()
                ->send();
        }
                        })
                    ->columnspan(2),

                Forms\Components\Select::make('destination')
                    ->label('Destination')
                    ->relationship('destinationLocation', 'name')
                    ->required()
                    ->live()
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('New Destination')
                            ->required()
                            ->maxLength(100)
                    ])
/*                     ->options(function () {
                        return TripDestination::pluck('name', 'id')->map(fn($name) => ucwords($name));
                    }) */
                    ->preload()
                    ->afterStateUpdated(function (Set $set, $state, Get $get) {
                            // Convert to Proper Case for display
                        $set('name', ucwords(strtolower($state)));
                        //Log::info('origin: ' . $get('origin') . ' ' . $get('destination'));
                                if ($get('origin') === $get('destination')) {
            Notification::make()
                ->title('Origin and Destination must be different')
                ->danger()
                ->send();
                $set('destination',null);
        }
                    })
                    ->columnspan(2),

                Forms\Components\TextInput::make('distance')
                    ->label('Distance (km)')
                    ->numeric()
                    ->required()
                    ->label('Distance')
                    ->columnspan(1),

                Forms\Components\Textarea::make('remark')
                    ->label('Remark')
                    ->nullable()
                    ->columnspan(2),
                ])
                ->columns(7);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('originLocation.name')
                    ->label('Origin')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('destinationLocation.name')
                    ->label('Destination')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('distance')
                    ->sortable()
                    ->label('Distance'),

                Tables\Columns\TextColumn::make('remark')
                    ->label('Remark')
                    ->limit(50),
                /*
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Created At'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Updated At'), */
            ])
            ->filters([
                // Define filters if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTripDistances::route('/'),
            'create' => Pages\CreateTripDistance::route('/create'),
            'edit' => Pages\EditTripDistance::route('/{record}/edit'),
        ];
    }
}
