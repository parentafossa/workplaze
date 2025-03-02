<?php

namespace App\Filament\Clusters\SalesMarketing\Resources;

use App\Filament\Clusters\SalesMarketing;
use App\Filament\Clusters\SalesMarketing\Resources\QuotationResource\Pages;
use App\Filament\Clusters\SalesMarketing\Resources\QuotationResource\RelationManagers;
use App\Models\Quotation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\QuotationType;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Pipe Lines';
    protected static ?string $cluster = SalesMarketing::class;
    protected static ?string $navigationLabel = 'Sales Quotation';
    protected static ?string $recordTitleAttribute = 'quotation_number';
    protected static ?int $navigationSort = 3;

    protected static function toRoman($month) {
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 
            6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 
            11 => 'XI', 12 => 'XII'
        ];
        return $romanMonths[$month] ?? '';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Quotation Information')
                    ->schema([
                        Forms\Components\TextInput::make('quotation_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            //->default(fn () => 'QT-' . date('Ymd') . '-' . str_pad(Quotation::count() + 1, 4, '0', STR_PAD_LEFT))
                            ->default(fn () => str_pad(Quotation::count() + 1, 4, '0', STR_PAD_LEFT) .
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
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                                'application/vnd.ms-excel', 
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
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
                            ->minDate(fn (Forms\Get $get) => $get('validity_start_date'))
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
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Quotation number copied')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('salesOpportunity.title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->numeric(locale: 'id', decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('validity_start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('validity_end_date')
                    ->date()
                    ->sortable()
                    ->color(fn (Quotation $record): string => 
                        $record->validity_end_date < now() ? 'danger' : 'success'
                    ),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'sent',
                        'success' => 'confirmed',
                        'danger' => 'rejected',
                        'warning' => 'expired',
                    ])
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
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

                Tables\Filters\SelectFilter::make('sales_opportunity')
                    ->relationship('salesOpportunity', 'title')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('validity_period')
                    ->form([
                        Forms\Components\DatePicker::make('valid_from'),
                        Forms\Components\DatePicker::make('valid_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['valid_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('validity_start_date', '>=', $date),
                            )
                            ->when(
                                $data['valid_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('validity_end_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('send')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(fn (Quotation $record) => $record->status === 'draft')
                        ->action(fn (Quotation $record) => $record->update(['status' => 'sent'])),
                    Tables\Actions\Action::make('confirm')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Quotation $record) => $record->status === 'sent')
                        ->action(fn (Quotation $record) => $record->update(['status' => 'confirmed'])),
                    Tables\Actions\Action::make('reject')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Quotation $record) => $record->status === 'sent')
                        ->action(fn (Quotation $record) => $record->update(['status' => 'rejected'])),
                    Tables\Actions\Action::make('extend')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\DatePicker::make('new_end_date')
                                ->label('New Validity End Date')
                                ->required()
                                ->minDate(fn (Quotation $record) => $record->validity_end_date),
                        ])
                        ->visible(fn (Quotation $record) => in_array($record->status, ['sent', 'confirmed']))
                        ->action(function (Quotation $record, array $data): void {
                            $record->update([
                                'validity_end_date' => $data['new_end_date'],
                                'status' => $record->status === 'expired' ? 'sent' : $record->status,
                            ]);
                        }),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->emptyStateHeading('No quotations yet')
            ->emptyStateDescription('Start creating quotations for your sales opportunities.')
            ->emptyStateIcon('heroicon-o-document-text');
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
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'view' => Pages\ViewQuotation::route('/{record}'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'sent')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending Quotations';
    }


}
