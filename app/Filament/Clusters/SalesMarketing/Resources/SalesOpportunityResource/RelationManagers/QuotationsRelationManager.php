<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Models\Quotation;
use App\Enums\QuotationType;

class QuotationsRelationManager extends RelationManager
{
    protected static string $relationship = 'quotations';
    protected static ?string $title = 'Quotations';
    protected static ?string $recordTitleAttribute = 'quotation_number';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Quotation Information')
                    ->schema([
                        Forms\Components\TextInput::make('quotation_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            //->default(fn () => 'QT-' . date('Ymd') . '-' . str_pad(Quotation::count() + 1, 4, '0', STR_PAD_LEFT))
                            ->default(fn() => str_pad(Quotation::count() + 1, 4, '0', STR_PAD_LEFT) .
                                '/LID/QUO/' .
                                self::toRoman(date('n')) .
                                '/' .
                                date('Y'))
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(['default' => 1, 'sm' => 2]),

                        Forms\Components\Select::make('sales_opportunity_id')
                            ->relationship('salesOpportunity', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $opportunity = \App\Models\SalesOpportunity::find($state);
                                    if ($opportunity) {
                                        $set('amount', $opportunity->estimated_value);
                                        $set('gross_profit', $opportunity->gross_profit);
                                        $set('subject', $opportunity->title);
                                    }
                                }
                            })
                            ->columnSpan(['default' => 1, 'sm' => 2]),
                    ])
                    ->columns(['default' => 1, 'sm' => 4]),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->maxValue(999999999999.99)
                            ->minValue(0)
                            ->default(0)
                            ->columnSpan(['default' => 1, 'sm' => 1]),
                        Forms\Components\TextInput::make('gross_profit')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->maxValue(999999999999.99)
                            ->minValue(0)
                            ->default(0)
                            ->columnSpan(['default' => 1, 'sm' => 1]),
                        Forms\Components\Select::make('quotation_type')
                            ->options(QuotationType::toArray())
                            ->default(QuotationType::SINGLE->value)
                            ->required()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state instanceof QuotationType) {
                                    $set('quotation_type', $state->value);
                                }
                            }),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'confirmed' => 'Confirmed',
                                'rejected' => 'Rejected',
                                'expired' => 'Expired',
                            ])
                            ->required()
                            ->default('draft')
                            ->native(false)
                            ->columnSpan(['default' => 1, 'sm' => 1]),
                        Forms\Components\Select::make('signee')
                            ->label('Signee')
                            ->relationship(
                                'approver',
                                'emp_name',
                                fn($query) => $query
                                    ->where('active', true)
                                    ->where('position_class', '<=', 61)
                                    ->where('position_class', '>=', 51)
                                    ->orderByDesc('position_class')
                            )
                            //->options(Employee::query()
                            //    ->orderBy('emp_name')
                            //    ->pluck('emp_name', 'emp_id'))
                            //->default(auth()->user()->emp_id)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(['default' => 1, 'sm' => 1]),
                        Forms\Components\FileUpload::make('file_path')
                            ->required(fn($get) => !$get('is_linked'))
                            ->label('Quotation File')
                            ->disk('private')
                            ->multiple()
                            ->downloadable()
                            ->directory('quotations')
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/*',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            ])
                            ->maxSize(10240)
                            ->columnSpan(['default' => 1, 'sm' => 2]),
                    ])
                    ->columns(['default' => 1, 'sm' => 4]),

                Forms\Components\Section::make('Validity Period')
                    ->schema([
                        Forms\Components\DatePicker::make('validity_start_date')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('validity_end_date')
                            ->required()
                            ->default(now()->addDays(30))
                            ->minDate(fn(Forms\Get $get) => $get('validity_start_date'))
                            ->afterOrEqual('validity_start_date'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\RichEditor::make('notes')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),
                    ]),


                Forms\Components\Hidden::make('user_id')
                    ->default(fn() => auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->prefix('Rp')
                    ->sortable(),

                Tables\Columns\TextColumn::make('validity_start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('validity_end_date')
                    ->date()
                    ->sortable()
                    ->color(fn (Model $record): string => 
                        $record->validity_end_date < now() ? 'danger' : 'success'
                    ),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'sent',
                        'success' => 'confirmed',
                        'danger' => 'rejected',
                        'warning' => 'expired',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'confirmed' => 'Confirmed',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                    ])
                    ->multiple(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function () {
                        $opportunity = $this->getOwnerRecord();
                        $quotations = $opportunity->quotations;

                        // If any quotation is confirmed, mark as won
                        if ($quotations->where('status', 'confirmed')->count() > 0) {
                            $opportunity->update(['status' => 'won']);
                            return;
                        }

                        // If all quotations are rejected, mark as lost
                        if ($quotations->count() > 0 && $quotations->whereNotIn('status', ['rejected'])->count() === 0) {
                            $opportunity->update(['status' => 'lost']);
                            return;
                        }

                        // Otherwise, if there are active quotations (draft, sent, expired), mark as quotation phase
                        if ($quotations->whereIn('status', ['draft', 'sent', 'expired'])->count() > 0) {
                            $opportunity->update(['status' => 'quotation_phase']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('send')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(fn (Model $record) => $record->status === 'draft')
                        ->action(fn (Model $record) => $record->update(['status' => 'sent'])),
                    Tables\Actions\Action::make('confirm')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Model $record) => $record->status === 'sent')
                        ->action(function (Model $record) {
                            $record->update(['status' => 'confirmed']);
                            // Optionally update opportunity status when quotation is confirmed
                            // $record->salesOpportunity->update(['status' => 'won']);
                        }),
                    Tables\Actions\Action::make('reject')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Model $record) => $record->status === 'sent')
                        ->action(fn (Model $record) => $record->update(['status' => 'rejected'])),
                    Tables\Actions\Action::make('extend')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\DatePicker::make('new_end_date')
                                ->label('New Validity End Date')
                                ->required()
                                ->minDate(fn (Model $record) => $record->validity_end_date),
                        ])
                        ->visible(fn (Model $record) => in_array($record->status, ['sent', 'confirmed']))
                        ->action(function (Model $record, array $data): void {
                            $record->update([
                                'validity_end_date' => $data['new_end_date'],
                                'status' => $record->status === 'expired' ? 'sent' : $record->status,
                            ]);
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function isReadOnly(): bool
    {
        $ownerRecord = $this->getOwnerRecord();
        return in_array($ownerRecord->status, ['won', 'lost']);
    }

    public function toRoman($month)
    {
        $romanMonths = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII'
        ];
        return $romanMonths[$month] ?? '';
    }
}