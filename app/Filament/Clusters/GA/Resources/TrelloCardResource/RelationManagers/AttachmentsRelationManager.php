<?php

namespace App\Filament\Clusters\GA\Resources\TrelloCardResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';
    protected static ?string $recordTitleAttribute = 'name';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('url')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            //->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->sortable()->searchable()
                    ->url(fn($record) => $record->url)
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                ->date(),
                //TextColumn::make('url')->label('URL')->sortable()->searchable()
                //    ->url(fn($record) => $record->url)
                //    ->openUrlInNewTab(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
                //Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
