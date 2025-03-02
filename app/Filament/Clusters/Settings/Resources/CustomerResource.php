<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\CustomerResource\Pages;
use App\Filament\Clusters\Settings\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    //protected static ?string $navigationGroup = 'Sales Marketing';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $cluster = Settings::class;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('group')
                            ->maxLength(50),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'IDR' => 'IDR',
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                            ])
                            ->default('IDR'),
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city'),
                        Forms\Components\TextInput::make('payment_term'),
                        Forms\Components\TextInput::make('contact_name'),
                        Forms\Components\TextInput::make('email_address')
                            ->email(),
                        Forms\Components\TextInput::make('telephone'),
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('group')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->sortable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency')
                    ->options([
                        'IDR' => 'IDR',
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                    ]),
                Tables\Filters\SelectFilter::make('group'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            //RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}