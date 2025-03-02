<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use App\Models\Company;

use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Toggle;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class UserResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Settings::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('emp_id')
                    ->label('Employee')
                    ->options(function () {
                        // If super admin, show all employees
                        if (Auth::user()->hasRole('super_admin')) {
                            return Employee::query()
                                ->orderBy('emp_name')
                                ->get()
                                ->mapWithKeys(fn ($emp) => [
                                    $emp->emp_id => "{$emp->emp_id} - {$emp->emp_name}"
                                ]);
                        }
                        
                        // Otherwise, show only employees from user's companies
                        $companyIds = Auth::user()->companies->pluck('id');
                        return Employee::query()
                            ->whereIn('company_id', $companyIds)
                            ->orderBy('emp_name')
                            ->get()
                            ->mapWithKeys(fn ($emp) => [
                                $emp->emp_id => "{$emp->emp_id} - {$emp->emp_name}"
                            ]);
                    })
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $employee = Employee::find($state);
                            if ($employee) {
                                $set('name', $employee->emp_name);
                                $set('email', $employee->email);
                            }
                        }
                    }),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('If disabled, user cannot login')
                    ->disabled(function ($record) {
                        // Prevent deactivating own account
                        return $record && $record->id === auth()->id();
                    }),

                DateTimePicker::make('email_verified_at'),

                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->reactive()
                    ->required(fn (callable $get, $record) => $record && !$record->password)
                    ->rules(['nullable', 'confirmed', 'min:8'])
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state)),

                TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->reactive()
                    ->hint(function(callable $get,){
                        if($get('password') === $get('password_confirmation') ){
                            return 'Password OK';
                        } else {
                            return 'Password does not match';
                        }
                    })
                    ->visible(fn (callable $get) => filled($get('password'))) 
                    ->required(fn (string $operation): bool => $operation === 'create'),

                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Select::make('companies')
                    ->relationship('companies', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(function () {
                        if (Auth::user()->hasRole('super_admin')) {
                            return Company::pluck('name', 'id');
                        }
                        return Auth::user()->companies->pluck('name', 'id');
                    })
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('emp_id')
                    ->label('Employee ID')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                //Tables\Columns\TextColumn::make('email_verified_at')
                //    ->dateTime()
                //    ->sortable(),

                Tables\Columns\TextColumn::make('companies.short_name')
                    ->badge()
                    ->separator(',')
                    ->label('Companies'),
                
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->separator(',')
                    ->label('roles'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                //Tables\Columns\TextColumn::make('created_at')
                //    ->dateTime()
                //    ->sortable()
                //    ->toggleable(isToggledHiddenByDefault: true),

                //Tables\Columns\TextColumn::make('updated_at')
                //    ->dateTime()
                //    ->sortable()
                //    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('companies')
                    ->relationship('companies', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Filter by Company'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Users')
                    ->trueLabel('Active Users')
                    ->falseLabel('Blocked Users'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn ($record) => $record->is_active ? 'Block' : 'Unblock')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => ($record->is_active ? 'Block' : 'Unblock') . ' User')
                    ->modalDescription(fn ($record) => 'Are you sure you want to ' . ($record->is_active ? 'block' : 'unblock') . ' this user?')
                    ->modalSubmitActionLabel(fn ($record) => $record->is_active ? 'Yes, block user' : 'Yes, unblock user')
                    ->hidden(fn ($record) => $record->id === auth()->id()) // Can't block self
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                        
                        // Optional: Force logout if blocking
                        if (!$record->is_active) {
                            auth()->guard('web')->logoutOther($record->id);
                        }
                    }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // If not super admin, only show users from the same companies
        if (!Auth::user()->hasRole('super_admin')) {
            $userCompanyIds = Auth::user()->companies->pluck('id');
            
            $query->whereHas('companies', function ($q) use ($userCompanyIds) {
                $q->whereIn('m_companies.id', $userCompanyIds);
            });
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->hasRole('super_admin') || Auth::user()->companies()->exists();
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
        ];
    }
}