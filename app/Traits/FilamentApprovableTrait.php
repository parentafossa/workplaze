<?php

namespace App\Traits;

use Filament\Forms;
use Filament\Tables;
use App\Services\ApprovalService;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

trait FilamentApprovableTrait
{    
    public static function getApprovalFormComponents(): array
    {
        return [
            Forms\Components\Section::make('Approval Information')
                ->schema([
                    Forms\Components\Select::make('approval_flow_id')
                        ->relationship('approvalFlow', 'name')
                        ->visible(fn (Model $record = null) => !$record || $record->approval_status === 'draft')
                        ->required(),
                        
                    Forms\Components\View::make('filament.forms.components.approval-status')
                        ->visible(fn (Model $record = null) => $record && $record->currentApprovalInstance()),
                ])
                ->columnSpan(2)
        ];
    }

    public static function getApprovalTableColumns(): array
    {
        return [
            TextColumn::make('approval_status')
                ->label('Approval Status')
                ->badge()
                ->color(fn (string $state): string => match($state) {
                    'completed' => 'success',
                    'cancelled', 'rejected' => 'danger',
                    'draft' => 'gray',
                    'pending', 'pending_cancellation' => 'warning',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state))),
        ];
    }

    public static function getApprovalTableActions(): array
    {
        return [
            // Initial submit action
            Tables\Actions\Action::make('submit_for_approval')
                ->action(fn (Model $record) => app(ApprovalService::class)->initiateApproval($record))
                ->requiresConfirmation()
                ->label('Submit')
                ->modalHeading('Submit for Approval')
                ->modalDescription('Are you sure you want to submit this record for approval?')
                ->modalSubmitActionLabel('Submit')
                ->color('primary')
                ->visible(fn (Model $record): bool => $record->approval_status === 'draft'),

            // Submit with options action
            Tables\Actions\Action::make('submit')
                ->form(function (Model $record): array {
                    $instance = $record->currentApprovalInstance();
                    $step = $instance?->currentStepConfig;
                    $submitOptions = $step['submit_options'] ?? ['submit_for_approval'];

                    return [
                        Forms\Components\Select::make('submit_type')
                            ->label('Submit Type')
                            ->options(array_combine($submitOptions, array_map(
                                fn ($option) => str_replace('_', ' ', ucfirst($option)),
                                $submitOptions
                            )))
                            ->required(),
                            
                        Forms\Components\Textarea::make('comments')
                            ->label('Comments')
                            ->placeholder('Add your submission comments...')
                    ];
                })
                ->action(function (Model $record, array $data): void {
                    app(ApprovalService::class)->processApproval(
                        record: $record,
                        user: auth()->user(),
                        action: 'submit',
                        comments: $data['comments'],
                        submitType: $data['submit_type']
                    );
                })
                ->visible(function (Model $record): bool {
                    $instance = $record->currentApprovalInstance();
                    if (!$instance || !in_array($instance->status, ['pending', 'pending_cancellation'])) {
                        return false;
                    }
                    
                    $currentStep = $instance->currentStepConfig;
                    return $currentStep && 
                           ($currentStep['step_type'] ?? 'approve') === 'submit' &&
                           app(ApprovalService::class)->isAuthorizedApprover(auth()->user(), $currentStep);
                }),

            // Approve action
            Tables\Actions\Action::make('approve')
                ->form([
                    Forms\Components\Textarea::make('comments')
                        ->label('Comments')
                        ->placeholder('Add your approval comments...')
                ])
                ->action(function (Model $record, array $data): void {
                    app(ApprovalService::class)->processApproval(
                        record: $record,
                        user: auth()->user(),
                        action: 'approve',
                        comments: $data['comments']
                    );
                })
                ->visible(function (Model $record): bool {
                    $instance = $record->currentApprovalInstance();
                    if (!$instance || !in_array($instance->status, ['pending', 'pending_cancellation'])) {
                        return false;
                    }
                    
                    $currentStep = $instance->currentStepConfig;
                    return $currentStep && 
                           ($currentStep['step_type'] ?? 'approve') === 'approve' &&
                           app(ApprovalService::class)->isAuthorizedApprover(auth()->user(), $currentStep);
                })
                ->modalSubmitActionLabel('Approve')
                ->color('success'),

            // Reject action
            Tables\Actions\Action::make('reject')
                ->color('danger')
                ->form([
                    Forms\Components\Textarea::make('comments')
                        ->label('Rejection Reason')
                        ->placeholder('Please provide a reason for rejection...')
                        ->required()
                ])
                ->action(function (Model $record, array $data): void {
                    app(ApprovalService::class)->processApproval(
                        record: $record,
                        user: auth()->user(),
                        action: 'reject',
                        comments: $data['comments']
                    );
                })
                ->requiresConfirmation()
                ->modalHeading('Reject Request')
                ->modalDescription('Are you sure you want to reject this request? This action cannot be undone.')
                ->modalSubmitActionLabel('Reject')
                ->visible(function (Model $record): bool {
                    $instance = $record->currentApprovalInstance();
                    if (!$instance || !in_array($instance->status, ['pending', 'pending_cancellation'])) {
                        return false;
                    }
                    
                    $currentStep = $instance->currentStepConfig;
                    return $currentStep && 
                           ($currentStep['step_type'] ?? 'approve') === 'approve' &&
                           app(ApprovalService::class)->isAuthorizedApprover(auth()->user(), $currentStep);
                }),
        ];
    }
}