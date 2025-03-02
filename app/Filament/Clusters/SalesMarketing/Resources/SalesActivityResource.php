<?php

namespace App\Filament\Clusters\SalesMarketing\Resources;

use App\Filament\Clusters\SalesMarketing;
use App\Filament\Clusters\SalesMarketing\Resources\SalesActivityResource\Pages;
use App\Filament\Clusters\SalesMarketing\Resources\SalesActivityResource\RelationManagers;
use App\Models\SalesActivity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class SalesActivityResource extends Resource
{
    protected static ?string $model = SalesActivity::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Pipe Lines';
    protected static ?string $cluster = SalesMarketing::class;
    protected static ?int $navigationSort = 2;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sales Activity Details')
                    ->schema([
                        Forms\Components\Select::make('sales_opportunity_id')
                            ->relationship('salesOpportunity', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('customer_name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columnSpan(['default' => 1, 'sm' => 2]),

                        Forms\Components\Select::make('activity_type')
                            ->options([
                                'meeting' => 'Meeting',
                                'call' => 'Phone Call',
                                'email' => 'Email',
                                'presentation' => 'Presentation',
                                'proposal' => 'Proposal',
                                'negotiation' => 'Negotiation',
                                'follow_up' => 'Follow Up',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->native(false)
                            ->columnSpan(['default' => 1, 'sm' => 2]),

                        Forms\Components\Select::make('quotation_id')
                            ->relationship('quotations', 'quotation_number')
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->quotation_number} - {$record->title}") // Display both fields
                            ->searchable()
                            ->preload()
                            ->columnSpan(['default' => 1, 'sm' => 2]),
                    ])
                    ->columns(['default' => 1, 'sm' => 4]),

                Forms\Components\Section::make('Activity Information')
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('date')
                            ->required()
                            ->default(now()),

                        Forms\Components\RichEditor::make('outcome')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Follow-up')
                    ->schema([
                        Forms\Components\Select::make('next_action')
                            ->options([
                                'follow_up_call' => 'Follow-up Call',
                                'send_proposal' => 'Send Proposal',
                                'schedule_meeting' => 'Schedule Meeting',
                                'prepare_quotation' => 'Prepare Quotation',
                                'await_feedback' => 'Await Customer Feedback',
                                'other' => 'Other',
                            ])
                            ->native(false),

                        Forms\Components\DateTimePicker::make('next_action_date')
                            ->after('date'),

                        Forms\Components\Toggle::make('is_completed')
                            ->label('Mark as Completed')
                            ->default(false),
                    ])
                    ->columns(3),

                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('salesOpportunity.title')
                    ->label('Sales Opportunity')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('activity_type')
                    ->badge()
                    ->colors([
                        'primary' => 'meeting',
                        'warning' => 'call',
                        'success' => 'email',
                        'info' => 'presentation',
                        'danger' => 'negotiation',
                        'gray' => 'other',
                    ])
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->html()
                    ->words(10)
                    ->tooltip(fn (Tables\Columns\TextColumn $column): ?string => $column->getState()),

                Tables\Columns\TextColumn::make('next_action')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('next_action_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

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
                Tables\Filters\SelectFilter::make('activity_type')
                    ->multiple()
                    ->options([
                        'meeting' => 'Meeting',
                        'call' => 'Phone Call',
                        'email' => 'Email',
                        'presentation' => 'Presentation',
                        'proposal' => 'Proposal',
                        'negotiation' => 'Negotiation',
                        'follow_up' => 'Follow Up',
                        'other' => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('sales_opportunity')
                    ->relationship('salesOpportunity', 'title')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),

                Tables\Filters\TernaryFilter::make('is_completed')
                    ->label('Completion Status')
                    ->placeholder('All Activities')
                    ->trueLabel('Completed Activities')
                    ->falseLabel('Pending Activities'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsCompleted')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check')
                        ->action(function (Collection $records): void {
                            $records->each(function ($record) {
                                $record->update(['is_completed' => true]);
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->poll('60s')
            ->emptyStateHeading('No activities recorded')
            ->emptyStateDescription('Start tracking your sales activities by creating a new activity record.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
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
            'index' => Pages\ListSalesActivities::route('/'),
            'create' => Pages\CreateSalesActivity::route('/create'),
            'edit' => Pages\EditSalesActivity::route('/{record}/edit'),
            'view' => Pages\ViewSalesActivity::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 0 ? 'primary' : 'gray';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['description', 'outcome', 'salesOpportunity.title'];
    }
}
