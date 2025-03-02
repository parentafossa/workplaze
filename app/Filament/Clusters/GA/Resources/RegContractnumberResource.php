<?php

namespace App\Filament\Clusters\GA\Resources;

use App\Filament\Clusters\GA;
use App\Filament\Clusters\GA\Resources\RegContractnumberResource\Pages;
use App\Models\RegContractnumber;
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
use App\Models\Organization;
use Filament\Forms\Components\Grid;

class RegContractnumberResource extends Resource
{
    protected static ?string $model = RegContractnumber::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationLabel = 'PKWT/PKWTT Number';
    protected static ?string $cluster = GA::class;
    protected static ?string $modelLabel = 'PKWT/PKWTT Number';
    protected static ?string $navigationGroup = 'Register';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Contract Information')
                    ->description('Basic information about the contract')
                    ->schema([
                        // Full-width components grid
                        Grid::make()
                            ->schema([
                                Forms\Components\Select::make('company_id')
                                    ->label('Company')
                                    ->options(Company::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => auth()->user()->employeeInfo->company_id)
                                    ->required(),

                                Forms\Components\Select::make('business_area')
                                    ->label('Business Area')
                                    ->options(function (callable $get) {
                                        $companyId = $get('company_id') ?? '316';
                                        return Organization::query()
                                            ->where('company_id', $companyId)
                                            ->where('level', 'Division')
                                            ->orderBy('sort')
                                            ->get()
                                            ->mapWithKeys(function ($org) {
                                                return [$org->id => "{$org->id} - {$org->name}"];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('requester_id')
                                    ->label('Requester')
                                    ->options(Employee::activeInCompany()->pluck('emp_name', 'emp_id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->nullable(),

                                Forms\Components\Textarea::make('remark')
                                    ->label('Remarks')
                                    ->rows(1)
                                    ->nullable(),
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
        
                                Forms\Components\Select::make('header_no')
                                    ->label('Month')
                                    ->options(self::getMonthAlphabet())
                                    ->default(self::getCurrentMonthAlphabet())
                                    ->required()
                                    ->native(false),

                                Forms\Components\TextInput::make('from_no')
                                    ->label('From No.')
                                    ->numeric()
                                    ->required()
                                    ->default(fn (callable $get) => 
                                        RegContractnumber::query()
                                            ->where('company_id', $get('company_id'))
                                            ->where('fiscal', $get('fiscal'))
                                            ->when(
                                                $get('company_id') && $get('fiscal'),
                                                fn ($query) => $query->selectRaw('GREATEST(COALESCE(MAX(from_no), 0), COALESCE(MAX(to_no), 0)) + 1 as next_number')
                                                    ->value('next_number'),
                                                fn () => 1
                                            ))
                                    ->live()
                                    ->afterStateUpdated(fn (callable $get, callable $set, $state) => 
                                        $get('from_no') && $get('to_no') >= $state ?: $set('to_no', $state)),

                                Forms\Components\TextInput::make('to_no')
                                    ->label('To No.')
                                    ->numeric()
                                    ->nullable()
                                    ->default(fn (callable $get) => 
                                        RegContractnumber::query()
                                            ->where('company_id', $get('company_id'))
                                            ->where('fiscal', $get('fiscal'))
                                            ->when(
                                                $get('company_id') && $get('fiscal'),
                                                fn ($query) => $query->selectRaw('GREATEST(COALESCE(MAX(from_no), 0), COALESCE(MAX(to_no), 0)) + 1 as next_number')
                                                    ->value('next_number'),
                                                fn () => 1
                                            )),

                                Forms\Components\DatePicker::make('request_date')
                                    ->label('Request Date')
                                    ->nullable()
                                    ->default(now()),

                                Forms\Components\Toggle::make('use_materai')
                                    ->label('Use Materai')
                                    ->default(true),
                            ])
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                                'lg' => 4
                            ]),
                    ]),

                Section::make('Attachments')
                    ->schema([
                        Forms\Components\FileUpload::make('document_file')
                            ->label('Document Files')
                            ->multiple()
                            ->downloadable()
                            ->disk('private')
                            ->reorderable()
                            ->directory(function ($livewire, $get) {
                                $companyId = $get('company_id') ?? '316';
                                $fiscal = $get('fiscal') ?? date('Y');
                                return "reg-contractnumber/{$companyId}/{$fiscal}";
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

                Tables\Columns\TextColumn::make('header_no')
                    ->label('Header')
                    ->searchable(),

                Tables\Columns\TextColumn::make('from_no')
                    ->label('From No.')
                    ->sortable(),

                Tables\Columns\TextColumn::make('to_no')
                    ->label('To No.')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('use_materai')
                    ->label('Materai')
                    //->boolean()
                    ,

                Tables\Columns\TextColumn::make('business_area')
                    ->label('Business Area')
                    ->formatStateUsing(function ($state) {
                        $org = Organization::where('level', 'Division')
                            ->find($state);
                        return $org ? "{$org->id} - {$org->name}" : $state;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('requester_id')
                    ->label('Requester')
                    ->formatStateUsing(fn ($state) => Employee::find($state)?->emp_name)
                    ->searchable(),

                Tables\Columns\TextColumn::make('request_date')
                    ->label('Request Date')
                    ->date()
                    ->sortable(),

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
                
                /*
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),*/
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
            'index' => Pages\ListRegContractnumbers::route('/'),
            'create' => Pages\CreateRegContractnumber::route('/create'),
            'edit' => Pages\EditRegContractnumber::route('/{record}/edit'),
        ];
    }

    protected static function getMonthAlphabet(): array
    {
        return [
            'A' => 'A - January',
            'B' => 'B - February',
            'C' => 'C - March',
            'D' => 'D - April',
            'E' => 'E - May',
            'F' => 'F - June',
            'G' => 'G - July',
            'H' => 'H - August',
            'I' => 'I - September',
            'J' => 'J - October',
            'K' => 'K - November',
            'L' => 'L - December',
        ];
    }

    protected static function getCurrentMonthAlphabet(): string
    {
        $currentMonth = date('n'); // 1-12
        return chr(64 + $currentMonth); // A=65, so we add current month to 64
    }

}