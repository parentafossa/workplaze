<?php

namespace App\Filament\Clusters\SalesMarketing\Resources;

use App\Filament\Clusters\SalesMarketing;
use App\Filament\Clusters\SalesMarketing\Resources\DocumentTagResource\Pages;
use App\Filament\Clusters\SalesMarketing\Resources\DocumentTagResource\RelationManagers;
use App\Models\DocumentTag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class DocumentTagResource extends Resource
{
    protected static ?string $model = DocumentTag::class;
    protected static ?string $navigationGroup = 'Basic Data';
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Document Tags';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Document Tag';
    protected static ?string $pluralModelLabel = 'Document Tags';
    protected static ?string $cluster = SalesMarketing::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('type')
                            ->options([
                                'business_area' => 'Business Area',
                                'business_type' => 'Business Type',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('description')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'business_area' => 'Business Area',
                        'business_type' => 'Business Type',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'business_area',
                        'success' => 'business_type',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Usage')
                    ->counts('documents')
                    ->sortable(),

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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'business_area' => 'Business Area',
                        'business_type' => 'Business Type',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (DocumentTag $record) {
                        // Optional: Check if tag is in use before deletion
                        if ($record->documents()->count() > 0) {
                            throw new \Exception('Cannot delete tag that is in use.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            // Optional: Check if any tags are in use before bulk deletion
                            $inUseCount = $records->sum(function ($record) {
                                return $record->documents()->count();
                            });
                            
                            if ($inUseCount > 0) {
                                throw new \Exception('Cannot delete tags that are in use.');
                            }
                        }),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListDocumentTags::route('/'),
            'create' => Pages\CreateDocumentTag::route('/create'),
            'edit' => Pages\EditDocumentTag::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('documents');
    }
}