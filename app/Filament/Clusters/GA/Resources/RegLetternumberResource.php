<?php

namespace App\Filament\Clusters\GA\Resources;

use App\Filament\Clusters\GA;
use App\Filament\Clusters\GA\Resources\RegLetternumberResource\Pages;
use App\Models\RegLetternumber;
use App\Models\Company;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
use Illuminate\Validation\Rule;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class RegLetternumberResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = RegLetternumber::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Letter Number';
    protected static ?string $cluster = GA::class;
    protected static ?string $title = 'Letter Numbers';
    protected static ?string $modelLabel = 'Letter Number';
    protected static ?string $navigationGroup = 'Register';

    /*public static function canCreate(): bool 
    {
        $user = auth()->user();
        $permissionName = 'create_reg::letternumber';
        
        \Log::info('Shield resource check:', [
            'class' => static::class,
            'basename' => class_basename(static::class),
            //'permission_group' => static::getPermissionGroup(),
            'permission_prefixes' => static::getPermissionPrefixes(),
            'permission' => auth()->user()->getAllPermissions()->pluck('name')
        ]);
        
        return $user->can($permissionName);
    }*/

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'reorder',
            'delete',
            'force_delete',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Letter Information')
                    ->description('Basic information about the letter')
                    ->schema([
                        // Full-width components grid
                        Grid::make()
                            ->schema([
                                Forms\Components\Select::make('company_id')
                                    ->label('Company')
                                    ->options(Company::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default(fn () => auth()->user()->employeeInfo->company_id),

                                Forms\Components\Textarea::make('letter_attentionto')
                                    ->label('Attention To')
                                    ->required()
                                    ->rows(2),

                                Forms\Components\Textarea::make('letter_title')
                                    ->label('Title')
                                    ->required()
                                    ->rows(2),

                                Forms\Components\Textarea::make('remark')
                                    ->label('Remarks')
                                    ->rows(3),
                            ])
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                                'lg' => 4
                            ]),

                        // Two-column components grid
                        Grid::make()
                            ->schema([
                                Forms\Components\Select::make('fiscal')
                                    ->label('Fiscal Year')
                                    ->options(function () {
                                        $currentYear = Carbon::now()->year;
                                        $nextYear = $currentYear + 1;
                                        $startYear = 2013;
                                        return array_combine(
                                            range($startYear, Carbon::now()->month >= 11 ? $nextYear : $currentYear),
                                            range($startYear, Carbon::now()->month >= 11 ? $nextYear : $currentYear)
                                        );
                                    })
                                    ->default(Carbon::now()->year)
                                    ->required(),

                                Forms\Components\TextInput::make('letter_no')
                                    ->label('Letter No.')
                                    ->numeric()
                                    ->required()
                                        ->rule(function (callable $get) {
        $companyId = $get('company_id');
        $fiscal = $get('fiscal');

        if (!$companyId || !$fiscal) {
            return null; // Skip validation if dependencies are not set
        }

        return Rule::unique('reg_letternumbers', 'letter_no')
            ->where('company_id', $companyId)
            ->where('fiscal', $fiscal);
    })
                                    ->default(fn (callable $get) => 
                                        RegLetternumber::query()
                                            ->where('company_id', $get('company_id'))
                                            ->where('fiscal', $get('fiscal'))
                                            ->when(
                                                $get('company_id') && $get('fiscal'),
                                                fn ($query) => $query->selectRaw('GREATEST(COALESCE(MAX(letter_no), 0), COALESCE(MAX(letter_tono), 0)) + 1 as next_number')
                                                    ->value('next_number'),
                                                fn () => 1
                                            ))
                                    ->live()
                                    ->afterStateUpdated(fn (callable $get, callable $set, $state) => 
                                        $get('letter_tono') && $get('letter_tono') >= $state ?: $set('letter_tono', $state)),

                                Forms\Components\TextInput::make('letter_tono')
                                    ->label('Letter To No.')
                                    ->numeric()
                                    ->default(fn (callable $get) => 
                                        RegLetternumber::query()
                                            ->where('company_id', $get('company_id'))
                                            ->where('fiscal', $get('fiscal'))
                                            ->when(
                                                $get('company_id') && $get('fiscal'),
                                                fn ($query) => $query->selectRaw('GREATEST(COALESCE(MAX(letter_no), 0), COALESCE(MAX(letter_tono), 0)) + 1 as next_number')
                                                    ->value('next_number'),
                                                fn () => 1
                                            ))
                                    ->rules([
                                        function (Get $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                $letterNo = $get('letter_no');
                                                if ($value < $letterNo) {
                                                    $fail("The letter to number must be greater than or equal to the letter number.");
                                                }
                                            };
                                        }
                                    ]),

                                Forms\Components\DatePicker::make('letter_date')
                                    ->label('Letter Date')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\Toggle::make('use_materai')
                                    ->label('Use Materai')
                                    ->default(false),

                                Forms\Components\Select::make('requester_id')
                                    ->label('Requester')
                                    ->options(
                                        Employee::where('active', 1)
                                        ->where('company_id', auth()->user()->employeeInfo->company_id)
                                        ->pluck('emp_name', 'emp_id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ])
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                                'lg' => 3
                            ]),
                    ]),

                Section::make('Attachments')
                    ->schema([
                        Forms\Components\FileUpload::make('document_file')
                            ->label('Document Files')
                            ->multiple()
                            ->disk('private')
                            ->downloadable()
                            ->reorderable()
                            ->directory(function ($livewire, $get) {
                                $companyId = $get('company_id') ?? 'temp';
                                $fiscal = $get('fiscal') ?? date('Y');
                                return "reg-letternumber/{$companyId}/{$fiscal}";
                            })
                            ->preserveFilenames()
                            ->storeFileNamesIn('original_names')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/*',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                            ])
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_id')
                    ->label('Company')
                    ->formatStateUsing(fn ($state) => Company::find($state)?->name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fiscal')
                    ->label('Fiscal')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('letter_no')
                    ->label('No. From')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('letter_tono')
                    ->label('No. To')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('letter_title')
                    ->label('Title')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('letter_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('use_materai')
                    ->label('Materai')
                    //->boolean()
                    ,

                Tables\Columns\TextColumn::make('requester_id')
                    ->label('Requester')
                    ->formatStateUsing(fn ($state) => Employee::find($state)?->emp_name)
                    ->searchable(),

                Tables\Columns\TextColumn::make('document_file')
                    ->label('Documents')
                    ->html()
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) return '';
                        
                        $files = json_decode($state, true) ?? [];
                        $originalNames = json_decode($record->original_names ?? '[]', true) ?? [];
                        
                        return collect($files)->map(function ($file) use ($originalNames) {
                            $displayName = $originalNames[$file] ?? basename($file);
                            return sprintf(
                                '<a href="%s" target="_blank" class="link">%s</a>',
                                Storage::disk('public')->url($file),
                                e($displayName)
                            );
                        })->implode(', ');
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Company')
                    ->options(Company::query()->pluck('name', 'id')),
                    
                Tables\Filters\SelectFilter::make('fiscal')
                    ->label('Fiscal Year')
                    ->options(function () {
                        $currentYear = Carbon::now()->year;
                        $years = range(2013, $currentYear);
                        return array_combine($years, $years);
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegLetternumbers::route('/'),
            'create' => Pages\CreateRegLetternumber::route('/create'),
            'edit' => Pages\EditRegLetternumber::route('/{record}/edit'),
        ];
    }
}