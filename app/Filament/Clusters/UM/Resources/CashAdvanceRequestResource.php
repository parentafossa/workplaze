<?php

namespace App\Filament\Clusters\UM\Resources;

use App\Filament\Clusters\UM;
use App\Filament\Clusters\GA;

use App\Filament\Clusters\UM\Resources\CashAdvanceRequestResource\Pages;
use App\Filament\Clusters\UM\Resources\CashAdvanceRequestResource\RelationManagers;

use App\Models\CashAdvanceRequest;
use App\Models\CaPurpose;
use App\Models\DriverTrip;
use App\Models\Driver;
use App\Models\DriverTripAssignment;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Set;
use Filament\Forms\Get;
 
use Filament\Resources\Resource;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\TextColumn;

use Filament\Support\Enums\MaxWidth;
use Filament\Support\Enums\Alignment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Pelmered\FilamentMoneyField\Forms\Components\MoneyInput;

use App\Traits\FilamentApprovableTrait;
use Filament\Forms\Components\ViewField;
use App\Models\Employee;
use App\Models\User;
use App\Models\Company;
use App\Services\CashAdvanceNumberGenerator;

use Illuminate\Support\Facades\Auth;
class CashAdvanceRequestResource extends Resource
{
    use FilamentApprovableTrait;

    protected static ?string $model = CashAdvanceRequest::class;
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'lineawesome-cash-register-solid';
    protected static ?string $cluster = GA::class;

    //$approvalComponents = static::getApprovalFormComponents();

    public static function form(Form $form): Form
    {
        $baseSchema=[
                TextInput::make('ca_no')
                    ->label('Cash Advance Number')
                    ->default(function () {
                            $user = auth()->user();
                            $employee = Employee::find($user->emp_id);
                            return CashAdvanceRequest::previewNextNumber($employee->company_id);
                        })
                        ->disabled()
                        ->dehydrated(false)
                    ->columnSpan(2),

                Select::make('plan_usage')
                    ->options([
                        'um_operation' => 'UM Operation',
                        'car_operation' => 'Car Operation',
                        'entertainment' => 'Entertainment',
                        'other' => 'Other',
                    ])
                    ->default('um_operation')
                    ->required()
                    ->preload()
                    ->live()
                    ->columnSpan(1),

                Select::make('driver_trip_assignment_id')
                    ->label('Assignment ID')
                    ->columnSpan(2)
                    //->relationship('driverTripAssignment.trip','trip_name')
                    
                    ->options(function () {
                        return DriverTripAssignment::with(['trip', 'driver'])
                        ->get()
                        ->mapWithKeys(function ($assignment) {
                            //Log::info('Trip:', ['trip' => $assignment->trip]);

                            $tripName = $assignment->trip ? $assignment->trip->trip_name : 'Unknown Trip';
                            $driverName = $assignment->driver ? $assignment->driver->emp_name : 'Unknown Driver';
                            return [$assignment->id => "{$tripName} - {$driverName}"];
                        });
                    })
                    ->visible(function (Get $get) {
                        //dd($get('plan_usage'));
                         if ($get('plan_usage') === 'um_operation'){
                            return true;
                         } else{
                            //dd($get('plan_usage'));
                            return false;
                         }
                    })
                    ->required(function (Get $get) {
                        //dd($get('plan_usage'));
                         if ($get('plan_usage') === 'um_operation'){
                            return true;
                         } else{
                            //dd($get('plan_usage'));
                            return false;
                         }
                    })
                    /*
                    ->visible(fn (callable $get) => $get('plan_usage') === 'um_operation')
                    ->required(fn (callable $get) => $get('plan_usage') === 'um_operation'),
                    */
                    //->enabled()
                    ,

                Select::make('emp_id')
                    ->label('Employee ID')
                    ->options(Employee::activeInCompany()->pluck('emp_name', 'emp_id'))
                    ->columnSpan(2)
                    ->visible(function (Get $get) {
                        //dd($get('plan_usage'));
                         if ($get('plan_usage') === 'um_operation'){
                            return false;
                         } else{
                            //dd($get('plan_usage'));
                            return true;
                         }
                    })
                    ->required(function (Get $get) {
                        //dd($get('plan_usage'));
                         if ($get('plan_usage') === 'um_operation'){
                            return false;
                         } else{
                            //dd($get('plan_usage'));
                            return true;
                         }
                    }),
        
                DatePicker::make('submit_date')
                    ->default(now())
                    ->required()
                    ->columnSpan(1),

                DatePicker::make('plan_use_date')
                    ->required()
                    ->columnSpan(1)
                    ->live(),



                Select::make('cash_advance_type')
                    ->label('Type')
                    ->options([
                        'necessary' => 'Necessary',
                        'not_necessary' => 'Not Necessary',
                    ])
                    ->default('necessary')
                    ->required()
                    ->columnSpan(1),
                TextInput::make('bank_name')
                    ->disabled()
                    ->columnSpan(1),

                TextInput::make('bank_account_no')
                    ->disabled()
                    ->columnSpan(1),

                TextInput::make('bank_account_name')
                    ->disabled()
                    ->columnSpan(1),

                TextInput::make('amount')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->minValue(0)
                    ->maxValue(20000000)
                    ->columnSpan(1),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'waiting-transfer' => 'Waiting Transfer',
                        'waiting-settle-driver' => 'Waiting Settle Driver',
                        'waiting-settle-fa' => 'Waiting Settle FA', 
                        'settled' => 'Settled',
                        'canceled' => 'Canceled',
                    ])
                    ->default('pending')
                    ->required()
                    ->columnSpan(1),
                MoneyInput::make('remaining_balance')
                    ->label('Remaining Balance')
                    ->afterStateHydrated(function ($state, $set, $record) {
                        if ($record) {
                            $remainingBalance = $record->remaining_balance; // Accessing the calculated attribute
                            $set('remaining_balance', number_format($remainingBalance, 2));
                        }
                    })
                    ->disabled()
                    ->prefix('Rp')
                    ->dehydrated(false)
                    ->columnSpan(1),
                Textarea::make('description')
                    ->columnSpan(2),

                // Cash Advance Usage Repeater
                Repeater::make('cashAdvanceUsages')
                    ->relationship()
                    ->schema([
                        DatePicker::make('usage_datetime')
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0)
                            ->columnSpan(1),

                        Select::make('purpose_id')
                            ->label('Purpose')
                            //->relationship('purpose', 'name')  // Changed this line
                            ->options(CaPurpose::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->columnSpan(2),

                        TextInput::make('remarks')
                            ->columnSpan(2),
                        FileUpload::make('files')
                            ->multiple()
                            ->label('Receipts')
                            ->disk('private')
                            ->directory(function(?Model $record){
                                $recordId = $record->cash_advance_request_id;
                                //log::info($recordId);
                                return "cash_adv_usages/{$recordId}";
                            })
                            ->downloadable()
                            ->previewable()
                            ->panelLayout('grid')
                            ->image()
                            ->maxSize(5120)
                            ->movefile()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->getUploadedFileNameForStorageUsing(
                                function (TemporaryUploadedFile $file){
                                    $timestamp = now()->format('Ymd_His');
                                    $extension = $file->getClientOriginalExtension();
                                    return "{$timestamp}.{$extension}";
                                }
                            )
                            ->columnspan(2)
                            ->imagePreviewHeight('100')
                            // ->panelLayout('integrated')
                            ,
                            
                    ])
                    ->columns(8)
                    ->defaultItems(0)
                    ->addActionLabel('Add Usage Record')
                    ->addActionAlignment(Alignment::Start)
                    ->columnSpan(8),

                    /*
                    Forms\Components\ViewField::make('approval_status')
                    ->view('filament.forms.components.approval-status')
                    ->visible(fn ($record) => $record && $record->currentApprovalInstance())
                    ->columnSpan(2),
                    */
                    //...static::getApprovalFormComponents(), 
                    //...$approvalComponents ? $approvalComponents : []
                            ];

                            $approvalComponents = static::getApprovalFormComponents();

        $fullSchema = !empty($approvalComponents) 
        ? array_merge($baseSchema, $approvalComponents)
        : $baseSchema;
        
        return $form
        ->schema($fullSchema)
        ->columns(8);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                //TextColumn::make('driver_trip_assignment_id'), 
                TextColumn::make('ca_no')
                ->label("Cash Advance No."),
                TextColumn::make('driverTripAssignment.trip.trip_name')
                    ->label('Trip Name')
                    ->searchable(),
                TextColumn::make('driverTripAssignment.driver.emp_name')
                    ->label('Driver Name'),
                TextColumn::make('submit_date'),
                TextColumn::make('plan_use_date'),
                TextColumn::make('amount')
                    ->numeric(decimalPlaces: 2), 
                TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'gray',
                    'in-progress' => 'warning',
                    'completed' => 'success',
                    'canceled' => 'danger',
                }), 
                TextColumn::make('approval_status')
                ->badge()
                ->color(fn ($state) => match($state) {
                    'completed' => 'success',
                    'rejected' => 'danger',
                    'draft' => 'gray',
                    'pending' => 'warning',
                    default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\Filter::make('submit_date')
                    ->form([
                        Forms\Components\DatePicker::make('submit_from'),
                        Forms\Components\DatePicker::make('submit_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['submit_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submit_date', '>=', $date),
                            )
                            ->when(
                                $data['submit_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submit_date', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                ...static::getApprovalTableActions(),
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
            'index' => Pages\ListCashAdvanceRequests::route('/'),
            'create' => Pages\CreateCashAdvanceRequest::route('/create'),
            'edit' => Pages\EditCashAdvanceRequest::route('/{record}/edit'),
        ];
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
}
