<?php

namespace App\Filament\Clusters\SalesMarketing\Resources;

use App\Enums\WarehouseType;
use App\Filament\Clusters\SalesMarketing;
use App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\Pages;
use App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\RelationManagers\QuotationsRelationManager;
use App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\RelationManagers\SalesActivitiesRelationManager;

use App\Models\SalesOpportunity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Resources\Pages\Page;

use App\Models\Customer;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Filament\Support\Enums\Alignment;
use Filament\Forms\Components\Textarea;

class SalesOpportunityResource extends Resource
{
    protected static ?string $model = SalesOpportunity::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Pipe Lines';
    protected static ?string $cluster = SalesMarketing::class;
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('entity_id')
                            ->label('Entity')
                            ->relationship('entity', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(['default' => 1, 'sm' => 1]),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'sm' => 3]),
                        Forms\Components\Select::make('emp_id')
                            ->label('Sales Lead')
                            ->relationship(
                                'employee',
                                'emp_name',
                                fn($query) => $query
                                    ->where('active', true)
                                    ->where('position_class', '<', 59)
                                    ->where('position_class', '>=', 40)
                                    ->whereIn('cost_center', ['TD9002', 'TD1012'])
                                    ->orderByDesc('position_class')
                            )
                            //->options(Employee::query()
                            //    ->orderBy('emp_name')
                            //    ->pluck('emp_name', 'emp_id'))
                            ->default(auth()->user()->emp_id)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(['default' => 1, 'sm' => 2]),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('business_area_tags')
                                    ->label('Business Areas')
                                    ->multiple()
                                    ->relationship('businessAreaTags', 'name', function ($query) {
                                        $query->where('type', 'business_area');
                                    })
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Hidden::make('type')
                                            ->default('business_area'),
                                        Forms\Components\TextInput::make('description')
                                            ->maxLength(255),
                                    ]),

                                Forms\Components\Select::make('business_type_tags')
                                    ->label('Business Types')
                                    ->multiple()
                                    ->relationship('businessTypeTags', 'name', function ($query) {
                                        $query->where('type', 'business_type');
                                    })
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Hidden::make('type')
                                            ->default('business_type'),
                                        Forms\Components\TextInput::make('description')
                                            ->maxLength(255),
                                    ]),

                            ])
                            ->columns(2),
                        Forms\Components\Toggle::make('is_new_customer')
                            ->required()
                            ->onColor('success')
                            ->inline(false)
                            ->reactive()
                            ->columnSpan(['default' => 1, 'sm' => 1]),
                        Forms\Components\TextInput::make('customer_name')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->hidden(fn($get) => !$get('is_new_customer'))
                            ->dehydrated(true)
                            ->columnSpan(['default' => 1, 'sm' => 3]),
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer Name')
                            ->relationship(
                                'customer',
                                'name',
                                fn($query) => $query
                                    ->where('id', 'not like', '9999%')
                                    ->orderBy('name')
                            )
                            ->searchable()
                            ->hidden(fn($get) => $get('is_new_customer'))
                            ->preload()
                            ->columnSpan(['default' => 1, 'sm' => 3]),
                        Forms\Components\DatePicker::make('expected_closing_date')
                            ->required()
                            ->minDate(now())
                            ->default(now()->addDays(30))
                            ->columnSpan(['default' => 1, 'sm' => 2]),
                    ])->columns(['default' => 1, 'sm' => 6]),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('estimated_value')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->maxValue(999999999999.99)
                            ->minValue(0)
                            ->mask('999999999999.99')
                            ->placeholder('0.00'),
                        Forms\Components\TextInput::make('gross_profit')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->maxValue(999999999999.99)
                            ->minValue(0)
                            ->mask('999999999999.99')
                            ->placeholder('0.00'),
                        Forms\Components\TextInput::make('volume'),
                        Forms\Components\Select::make('warehouse_type')
                            ->options(WarehouseType::toArray())
                            ->default(WarehouseType::NONE->value)
                            ->required()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state instanceof WarehouseType) {
                                    $set('warehouse_type', $state->value);
                                }
                            }),
                        Forms\Components\Textarea::make('warehouse_address'),
                        Forms\Components\TextInput::make('commodity'),
                        Forms\Components\TextInput::make('commodity_value'),
                    ])->columns(2),
                Forms\Components\Section::make('Status & Details')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'new' => 'New',
                                'in_progress' => 'In Progress',
                                'quotation_phase' => 'Quotation Phase',
                                'won' => 'Won',
                                'lost' => 'Lost',
                            ])
                            ->required()
                            ->default('new')
                            ->native(false),
                        Forms\Components\RichEditor::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Hidden::make('user_id')
                    ->default(fn() => auth()->id()),
            ])
            ->columns(6)
        ;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('entity.short_name')
                    ->searchable()
                    ->sortable()
                    ->grow(false)
                    ->weight(FontWeight::Bold)
                    ->limit(50),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->limit(50),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_new_customer')
                    ->icon(fn(string $state): string => match ($state) {
                        '1' => 'heroicon-o-check-circle',
                        default => ''
                    }),
                Tables\Columns\TextColumn::make('estimated_value')
                    ->money('IDR')
                    ->sortable()
                    ->alignment(Alignment::End)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expected_closing_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('employee.emp_name')
                    ->label('Sales Lead')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->colors([
                        'danger' => 'lost',
                        'warning' => 'in_progress',
                        'success' => 'won',
                        'info' => 'quotation_phase',
                        'primary' => 'new',
                    ])
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                /* Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),*/
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('entity_id')
                    ->label('Entity')
                    ->relationship('entity', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'in_progress' => 'In Progress',
                        'quotation_phase' => 'Quotation Phase',
                        'won' => 'Won',
                        'lost' => 'Lost',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('emp_id')
                    ->label('Sales Lead')
                    ->relationship(
                        'employee',
                        'emp_name',
                        fn($query) => $query
                            ->where('active', true)
                            ->where('position_class', '<', 59)
                            ->where('position_class', '>=', 40)
                            ->whereIn('cost_center', ['TD9002', 'TD1012'])
                            ->orderByDesc('position_class')
                    )
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No sales opportunities yet')
            ->emptyStateDescription('Create your first sales opportunity by clicking the button below.')
            ->emptyStateIcon('heroicon-o-currency-dollar');
    }

    public static function getRelations(): array
    {
        return [
                //
            SalesActivitiesRelationManager::class,
            QuotationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOpportunities::route('/'),
            'create' => Pages\CreateSalesOpportunity::route('/create'),
            'edit' => Pages\EditSalesOpportunity::route('/{record}/edit'),
            'view' => Pages\ViewSalesOpportunity::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'customer_name', 'description'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Customer' => $record->customer_name,
            'Status' => ucfirst($record->status),
            'Value' => number_format($record->estimated_value, 2, '.', ','),
        ];
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 0 ? 'primary' : 'gray';
    }

}
