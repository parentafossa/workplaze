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
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';
    protected static ?string $recordTitleAttribute = 'text';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('text')->required(),
                Forms\Components\TextInput::make('emp_id')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('text')
            ->columns([
                TextColumn::make('created_at')->datetime(),
                

                Split::make([
                 /* ImageColumn::make('employee.photo')
                    ->label('Photo')
                    ->getStateUsing(fn($record) => $record->empBasic ? $record->empBasic->photo : null)
                    ->circular(), 
                    TextColumn::make('emp_id')
                        ->label('Employee ID')
                        ->sortable()
                        ->searchable()
                        ->columnspan(1),
                    TextColumn::make('employee.emp_name')
                        ->label('Employee Name')
                        //->relationship('employee')
                        //->getStateUsing(fn($record) => $record->empBasic ? $record->empBasic->name : null)
                        ->sortable()
                        ->searchable()
                            ->columnspan(2), */
                    TextColumn::make('emp_id')
                        ->label('Employee')
                        ->formatStateUsing(function ($state, $record) {
                            $employeeName = $record->employee->emp_name ?? '';
                            return "{$state} - {$employeeName}";
                        })
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->where('emp_id', 'like', "%{$search}%")
                                ->orWhereHas('employee', function ($q) use ($search) {
                                    $q->where('emp_name', 'like', "%{$search}%");
                                });
                        })
                        ->sortable()
                        ->columnSpan(3),
                ]), 
                
                TextColumn::make('text')->sortable()->searchable()
                ->wrap(),
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
