<?php

namespace App\Filament\Clusters\SalesMarketing\Resources;

use App\Filament\Clusters\SalesMarketing;
use App\Filament\Clusters\SalesMarketing\Resources\CustomerResource\Pages;
use App\Filament\Clusters\SalesMarketing\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Actions\Imports\ImportAction;
use Filament\Actions\Imports\ImportField;
use App\Imports\CustomersImport;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;

use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Basic Data';
    protected static ?string $cluster = SalesMarketing::class;

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
                /* Tables\Columns\TextColumn::make('currency')
                    ->sortable(),
                Tables\Columns\TextColumn::make('city') 
                    ->searchable(),*/
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
            ->headerActions([
                Action::make('import customer')
                    ->label('Import Excel')
                    ->form([
                        FileUpload::make('file')
                            ->disk('local')
                            ->directory('uploads')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $filePath = Storage::disk('local')->path($data['file']);
                        Excel::import(new CustomersImport, $filePath);
                    })
                    ->color('success')
                    ->icon('heroicon-c-arrow-up-tray'),
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
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }

}