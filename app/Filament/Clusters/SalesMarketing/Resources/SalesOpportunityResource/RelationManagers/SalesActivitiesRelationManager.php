<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';
    protected static ?string $title = 'Sales Activities';
    protected static ?string $recordTitleAttribute = 'activity_type';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->required(),
                Forms\Components\RichEditor::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('date')
                    ->required()
                    ->default(now()),
                Forms\Components\Textarea::make('outcome')
                    ->columnSpanFull(),
                Forms\Components\Select::make('next_action')
                    ->options([
                        'follow_up_call' => 'Follow-up Call',
                        'send_proposal' => 'Send Proposal',
                        'schedule_meeting' => 'Schedule Meeting',
                        'prepare_quotation' => 'Prepare Quotation',
                        'await_feedback' => 'Await Customer Feedback',
                        'other' => 'Other',
                    ]),
                Forms\Components\DateTimePicker::make('next_action_date'),
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('activity_type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->html()
                    ->words(10),
                Tables\Columns\TextColumn::make('next_action')
                    ->badge(),
                Tables\Columns\TextColumn::make('next_action_date')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activity_type')
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function () {
                        // Update opportunity status to in_progress when a new activity is added
                        $opportunity = $this->getOwnerRecord();
                        if ($opportunity->status === 'new') {
                            $opportunity->update(['status' => 'in_progress']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
