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

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';
    protected static ?string $recordTitleAttribute = 'action';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('action')->required(),
                TextInput::make('list_from'),
                TextInput::make('list_to'),
                TextInput::make('status'),
                TextInput::make('user')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('action')->sortable()->searchable(),
                TextColumn::make('list_from')->label('From List')->sortable()->searchable(),
                TextColumn::make('list_to')->label('To List')->sortable()->searchable(),
                TextColumn::make('status')->sortable()->searchable(),
                TextColumn::make('user')->label('User')->sortable()->searchable(),
                TextColumn::make('created_at')->label('Date')->sortable()->searchable(),

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
