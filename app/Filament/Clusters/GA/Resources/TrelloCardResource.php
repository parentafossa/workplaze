<?php

namespace App\Filament\Clusters\GA\Resources;
use App\Filament\Clusters\GA;

use App\Filament\Clusters\GA\Resources\TrelloCardResource\Pages;
use App\Filament\Clusters\GA\Resources\TrelloCardResource\RelationManagers;
use App\Models\TrelloCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class TrelloCardResource extends Resource
{
    protected static ?string $model = TrelloCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Application Approvals';
    protected static ?string $cluster = GA::class;
    protected static ?string $title = 'Application Approvals';
    protected static ?string $modelLabel = 'Application Approvals';
    //protected static ?string $navigationGroup = 'Trello Card';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //                
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('status')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('created_at')
                ->dateTime(),
                TextColumn::make('urgent')->sortable()->searchable()
                    ->formatStateUsing(function ($state) {
                        return $state == 1 ? 'URGENT' : 'NORMAL';
                    })
                    ->color(function ($state) {
                        return $state == 1 ? 'danger' : 'info';
                    })
                    ->badge(),
                TextColumn::make('business_area')->sortable()->searchable(),
                TextColumn::make('name')
                    ->sortable()
                    ->wrap()
                    ->searchable(),
                TextColumn::make('status')
                    ->sortable()
                    ->wrap()
                    ->searchable()
                    ->badge()
                    ->color(function ($state) {
                        // Extract all possible statuses from status map
                        $statusColors = [
                            // Starting point
                            'New' => 'info',

                            // Waiting statuses (intermediate steps)
                            'Waiting Submit' => 'info',
                            'Waiting Manager' => 'primary',
                            'Waiting GM, w/o Manager Review' => 'primary',
                            'Waiting PD, w/o GM & Manager Review' => 'warning',
                            'Waiting GM' => 'primary',
                            'Waiting PD, w/o GM Review' => 'warning',
                            'Waiting PD' => 'warning',

                            // Rejection statuses (setbacks)
                            'Rejected by Manager, waiting Resubmit' => 'danger',
                            'Rejected by GM, waiting Resubmit' => 'danger',
                            'Rejected by PD, waiting Resubmit' => 'danger',
                            'Rejected by GM, waiting Manager' => 'danger',
                            'Rejected by PD, waiting Manager' => 'danger',
                            'Rejected by PD, waiting GM' => 'danger',

                            // Final approval statuses
                            'Approved' => 'success',
                            'Issued' => 'success',

                            // Default for any status not explicitly defined
                            'default' => 'gray'
                        ];

                        return $statusColors[$state] ?? $statusColors['default'];
                    }),
            ])
            ->filters([
                SelectFilter::make('urgent')
                    ->options([
                        '1' => 'Urgent',
                        '0' => 'Normal',
                    ])
                    ->label('Urgency')
                    ->multiple(),

                // Filter by Business Area
                SelectFilter::make('business_area')
                    ->options(function () {
                        // Get unique business areas from the database
                        return TrelloCard::whereNotNull('business_area')
                            ->distinct()
                            ->pluck('business_area', 'business_area')
                            ->toArray();
                    })
                    ->label('Business Area')
                    ->multiple(),

                // Filter by Status
                SelectFilter::make('status')
                    ->options(function () {
                        // Get unique statuses from the database
                        return TrelloCard::whereNotNull('status')
                            ->distinct()
                            ->pluck('status', 'status')
                            ->toArray();
                    })
                    ->label('Status')
                    ->multiple(),
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
            RelationManagers\CommentsRelationManager::class,
            RelationManagers\ActivitiesRelationManager::class,
            RelationManagers\AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrelloCards::route('/'),
            'create' => Pages\CreateTrelloCard::route('/create'),
            'edit' => Pages\EditTrelloCard::route('/{record}/edit'),
            'view' => Pages\ViewTrelloCard::route('/{record}'),
        ];
    }
}
