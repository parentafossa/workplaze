<?php

namespace App\Filament\Clusters\D365\Resources;

use App\Filament\Clusters\D365;
use App\Filament\Clusters\D365\Resources\D365VoucherTransactionResource\Pages;
use App\Filament\Clusters\D365\Resources\D365VoucherTransactionResource\RelationManagers;
use App\Jobs\ImportVoucherJob;
use App\Models\D365VoucherTransaction;
use App\Models\D365VoucherTransactionImport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Http\Controllers\D365VoucherTransactionImportController;
use Illuminate\Http\Request;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\Action;
use App\Imports\D365VoucherImport;
use Illuminate\Support\Facades\Log;
class D365VoucherTransactionResource extends Resource
{
    protected static ?string $model = D365VoucherTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'D365';
    protected static ?string $cluster = D365::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('journal_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('tax_invoice_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('voucher')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\Toggle::make('year_closed')
                    ->required(),
                Forms\Components\TextInput::make('ledger_account')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('account_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->maxLength(10),
                Forms\Components\TextInput::make('amount_in_transaction_currency')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('amount_in_reporting_currency')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('posting_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('posting_layer')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('vendor_account')
                    ->maxLength(255),
                Forms\Components\TextInput::make('vendor_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('customer_account')
                    ->maxLength(255),
                Forms\Components\TextInput::make('customer_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('sort_key')
                    ->maxLength(255),
                Forms\Components\TextInput::make('job_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('bp_list')
                    ->maxLength(255),
                Forms\Components\TextInput::make('tax_invoice_number2')
                    ->maxLength(255),
                Forms\Components\TextInput::make('transaction_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('created_by')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('created_date_and_time')
                    ->required(),
                Forms\Components\Toggle::make('correction')
                    ->required(),
                Forms\Components\Toggle::make('crediting')
                    ->required(),
                Forms\Components\TextInput::make('currency2')
                    ->maxLength(10),
                Forms\Components\Textarea::make('description2')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('level')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('main_account')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_reference')
                    ->maxLength(255),
                Forms\Components\TextInput::make('posting_type2')
                    ->maxLength(255),
                Forms\Components\TextInput::make('transaction_type2')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->headerActions([
            Action::make('import vouchers')
                    ->label('Import Voucher')
                    ->form([
                        FileUpload::make('file')
                            ->disk('local')
                            ->directory('uploads')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $filePath = Storage::disk('local')->path($data['file']);
                        if ($file = request()->file('file')) {
                            Log::info('Truncate DB');
                            D365VoucherTransactionImport::truncate();
                            Log::info('Dispatch Job');
                            dispatch(new ImportVoucherJob($file->getPathname()));
                        }
                    })
                    ->color('success')
                    ->icon('heroicon-c-arrow-up-tray'),
        ])
            ->columns([
                Tables\Columns\TextColumn::make('journal_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_invoice_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('voucher')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('year_closed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('ledger_account')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount_in_transaction_currency')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_in_reporting_currency')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('posting_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('posting_layer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vendor_account')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vendor_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_account')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sort_key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('job_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bp_list')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_invoice_number2')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_date_and_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('correction')
                    ->boolean(),
                Tables\Columns\IconColumn::make('crediting')
                    ->boolean(),
                Tables\Columns\TextColumn::make('currency2')
                    ->searchable(),
                Tables\Columns\TextColumn::make('level')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('main_account')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('posting_type2')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_type2')
                    ->searchable(),
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
                Tables\Actions\ViewAction::make(),
                //Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //Tables\Actions\BulkActionGroup::make([
                //    Tables\Actions\DeleteBulkAction::make(),
                //]),
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
            'index' => Pages\ListD365VoucherTransactions::route('/'),
            //'create' => Pages\CreateD365VoucherTransaction::route('/create'),
            'view' => Pages\ViewD365VoucherTransaction::route('/{record}'),
            //'edit' => Pages\EditD365VoucherTransaction::route('/{record}/edit'),
        ];
    }
}
