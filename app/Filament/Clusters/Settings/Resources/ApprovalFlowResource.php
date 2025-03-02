<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\ApprovalFlowResource\Pages;
use App\Models\ApprovalFlow;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Traits\HasApproval;

class ApprovalFlowResource extends Resource
{
    protected static ?string $model = ApprovalFlow::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    //protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Approval Flows';
    protected static ?string $cluster = Settings::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('description')
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('model_type')
                            ->options(self::getApprovableModels())
                            ->required(),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        
                        Forms\Components\Repeater::make('steps')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                    
                                Forms\Components\Select::make('step_type')
                                    ->options([
                                        'submit' => 'Submit Step',
                                        'approve' => 'Approval Step'
                                    ])
                                    ->default('approve')
                                    ->reactive()
                                    ->required(),
                                    
                                Forms\Components\Select::make('approval_type')
                                    ->options([
                                        'OR' => 'Any person can process',
                                        'AND' => 'All person must process'
                                    ])
                                    ->visible(fn (Forms\Get $get) => $get('step_type') === 'approve')
                                    ->required(),
                                    
                                Forms\Components\CheckboxList::make('submit_options')
                                    ->options([
                                        'submit_for_approval' => 'Submit for Approval',
                                        'submit_for_cancellation' => 'Submit for Cancellation'
                                    ])
                                    ->visible(fn (Forms\Get $get) => $get('step_type') === 'submit')
                                    ->required(),
                                    
                                Forms\Components\Repeater::make('approvers')
                                    ->schema([
                                        Forms\Components\Select::make('type')
                                            ->options([
                                                'user' => 'Specific User',
                                                'role' => 'Role',
                                                'department_head' => 'Department Head'
                                            ])
                                            ->reactive()
                                            ->required(),
                                            
                                        Forms\Components\Select::make('id')
                                            ->label('User')
                                            ->options(fn () => Employee::query()
                                                ->where('active', 1)
                                                ->pluck('emp_name', 'emp_id'))
                                            ->visible(fn (Forms\Get $get) => $get('type') === 'user')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                            
                                        Forms\Components\Select::make('role')
                                            ->options(fn () => Role::pluck('name', 'name'))
                                            ->visible(fn (Forms\Get $get) => $get('type') === 'role')
                                            ->required(),
                                            
                                        Forms\Components\Select::make('department_id')
                                            ->label('Department')
                                            ->options(fn () => Organization::pluck('name', 'id'))
                                            ->visible(fn (Forms\Get $get) => $get('type') === 'department_head')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ])
                                    ->columns(2)
                                    ->minItems(1)
                                    ->defaultItems(1)
                            ])
                            ->minItems(1)
                            ->defaultItems(1)
                            ->collapsible()
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
                    
                Tables\Columns\TextColumn::make('model_type')
                    //->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->searchable(),
                    
                Tables\Columns\ToggleColumn::make('is_active')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('model_type')
                    ->options(self::getApprovableModels()),
                    
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListApprovalFlows::route('/'),
            'create' => Pages\CreateApprovalFlow::route('/create'),
            'edit' => Pages\EditApprovalFlow::route('/{record}/edit'),
        ];
    }
/*
    protected static function getApprovableModels(): array
    {
        $models = [];
        $modelsPath = app_path('Models');

        collect(File::files($modelsPath))
            ->map(fn ($file) => 'App\\Models\\' . $file->getBasename('.php'))
            ->filter(fn ($class) => class_exists($class))
            ->filter(fn ($class) => collect(class_uses_recursive($class))
                ->has(HasApproval::class))
            ->each(fn ($class) => $models[$class] = Str::title(
                Str::snake(class_basename($class), ' ')
            ));

        return $models;
    }

    protected static function getApprovableModels(): array
{
    $models = [];
    $modelsPath = app_path('Models');

    collect(File::files($modelsPath))
        ->map(fn ($file) => 'App\\Models\\' . $file->getBasename('.php'))
        ->filter(fn ($class) => class_exists($class))
        ->filter(function ($class) {
            $reflectedClass = new \ReflectionClass($class);
            $traits = collect($reflectedClass->getTraits())->keys();
            return $traits->contains(HasApproval::class);
        })
        ->each(fn ($class) => $models[$class] = Str::title(
            Str::snake(class_basename($class), ' ')
        ));

    return $models;
}
*/
protected static function getApprovableModels(): array
{
    $models = [];
    $modelsPath = app_path('Models');

    collect(File::files($modelsPath))
        ->map(fn ($file) => 'App\\Models\\' . $file->getBasename('.php'))
        /*->filter(fn ($class) => class_exists($class))
        ->filter(function ($class) {
            $traits = self::getAllTraits($class); // Use self:: instead of $this
            return $traits->contains(App\Traits\HasApproval::class);
        })
        ->each(fn ($class) => $models[$class] = Str::title(
            Str::snake(class_basename($class), ' ')
        ))*/
        ->each(function ($class) use (&$models) {
                $traits = self::getAllTraits($class); // Recursively get all traits
                if ($traits->contains(HasApproval::class)) {
                    $models[] = $class; // Add to the list if HasApproval is found
                    $models[$class] = class_basename($class);
                    return Str::title(
                        Str::snake(class_basename($class), ' ')
                    );
                } /*else {
                    $this->info("Model does not use HasApproval: {$class}"); // Output to console
                }*/
            });
        //dd($models);
    return $models;
}

private static function getAllTraits($class): \Illuminate\Support\Collection
{
    $reflectedClass = new \ReflectionClass($class);
    $traits = collect($reflectedClass->getTraits())->keys();

    foreach ($reflectedClass->getTraits() as $trait) {
        $traits = $traits->merge(self::getAllTraits($trait->getName())); // Use self:: instead of $this
    }

    return $traits->unique();
}


}