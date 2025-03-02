<?php

namespace App\Filament\Clusters\DataOn\Resources;

use App\Filament\Clusters\DataOn;
use App\Filament\Clusters\DataOn\Resources\EmployeeResource\Pages;
use App\Filament\Clusters\DataOn\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeesExport;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Widgets\AttLogAnalysisWidget;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'DataOn';
    protected static ?string $cluster = DataOn::class;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('emp_id')
                            ->label('Employee ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('emp_name')
                            ->label('Full Name')
                            ->disabled(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')->disabled(),
                                Forms\Components\TextInput::make('middle_name')->disabled(),
                                Forms\Components\TextInput::make('last_name')->disabled(),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('birth_date')
                                    ->label('Birth Date')
                                    ->disabled(),
                                Forms\Components\TextInput::make('gender')->disabled(),
                                Forms\Components\TextInput::make('blood_type')->disabled(),
                            ]),
                        Forms\Components\TextInput::make('religion')->disabled(),
                    ]),

                Forms\Components\Section::make('Employment Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('status')->disabled(),
                                Forms\Components\DatePicker::make('joined_date')
                                    ->label('Join Date')
                                    ->disabled(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('job_title')
                                    ->label('Position')
                                    ->disabled(),
                                Forms\Components\TextInput::make('grade')->disabled(),
                            ]),
                    ]),

                Forms\Components\Section::make('Organization')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('org_company')
                                    ->label('Company')
                                    ->disabled(),
                                Forms\Components\TextInput::make('org_division')
                                    ->label('Division')
                                    ->disabled(),
                                Forms\Components\TextInput::make('org_department')
                                    ->label('Department')
                                    ->disabled(),
                                Forms\Components\TextInput::make('org_section')
                                    ->label('Section')
                                    ->disabled(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('business_area')
                                    ->disabled(),
                                Forms\Components\TextInput::make('cost_center')
                                    ->disabled(),
                            ]),
                    ]),

                Forms\Components\Section::make('Contact & Address')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('contact_no1')
                                    ->label('Primary Contact')
                                    ->disabled(),
                                Forms\Components\TextInput::make('contact_no2')
                                    ->label('Secondary Contact')
                                    ->disabled(),
                                Forms\Components\TextInput::make('email_official')
                                    ->label('Email')
                                    ->disabled(),
                            ]),
                        Forms\Components\Section::make('KTP Address')
                            ->schema([
                                Forms\Components\TextInput::make('ktp_jalan1')
                                    ->label('Address Line 1')
                                    ->disabled(),
                                Forms\Components\TextInput::make('ktp_jalan2')
                                    ->label('Address Line 2')
                                    ->disabled(),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('ktp_kelurahan')
                                            ->label('District')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('ktp_kecamatan')
                                            ->label('Sub-district')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('ktp_kotakabupaten')
                                            ->label('City')
                                            ->disabled(),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('ktp_province')
                                            ->label('Province')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('ktp_postcode')
                                            ->label('Postal Code')
                                            ->disabled(),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Identification')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('ktp_no')
                                    ->label('KTP Number')
                                    ->disabled(),
                                Forms\Components\TextInput::make('npwp')
                                    ->label('NPWP')
                                    ->disabled(),
                                Forms\Components\TextInput::make('tax_status')
                                    ->disabled(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('bpjshealth_no')
                                    ->label('BPJS Health')
                                    ->disabled(),
                                Forms\Components\TextInput::make('bpjsnaker_no')
                                    ->label('BPJS Employment')
                                    ->disabled(),
                            ]),
                    ]),

                Forms\Components\Section::make('Bank Accounts')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('idr_bankname')
                                    ->label('IDR Bank Name')
                                    ->disabled(),
                                Forms\Components\TextInput::make('idr_bankaccountno')
                                    ->label('IDR Account Number')
                                    ->disabled(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->filters([
                Tables\Filters\SelectFilter::make('active')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ])
                    ->default(1)
                    ->label('Status'),

                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company')
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('status')
                    ->options(
                        fn() => Employee::query()
                            ->whereNotNull('status')
                            ->distinct()
                            ->pluck('status', 'status')
                            ->toArray()
                    )
                    ->label('Employment Status'),

                Tables\Filters\SelectFilter::make('org_department')
                    ->options(
                        fn() => Employee::query()
                            ->whereNotNull('org_department')
                            ->distinct()
                            ->pluck('org_department', 'org_department')
                            ->toArray()
                    )
                    ->label('Department'),

                Tables\Filters\SelectFilter::make('org_division')
                    ->options(
                        fn() => Employee::query()
                            ->whereNotNull('org_division')
                            ->distinct()
                            ->pluck('org_division', 'org_division')
                            ->toArray()
                    )
                    ->label('Division'),

                Tables\Filters\SelectFilter::make('business_area')
                    ->options(
                        fn() => Employee::query()
                            ->whereNotNull('business_area')
                            ->distinct()
                            ->pluck('business_area', 'business_area')
                            ->toArray()
                    )
                    ->label('Business Area'),

                Tables\Filters\SelectFilter::make('location_city')
                    ->options(
                        fn() => Employee::query()
                            ->whereNotNull('location_city')
                            ->distinct()
                            ->pluck('location_city', 'location_city')
                            ->toArray()
                    )
                    ->label('Location'),

                Tables\Filters\SelectFilter::make('gender')
                    ->options(
                        fn() => Employee::query()
                            ->whereNotNull('gender')
                            ->distinct()
                            ->pluck('gender', 'gender')
                            ->toArray()
                    )
                    ->label('Gender'),

                Tables\Filters\Filter::make('joined_date')
                    ->form([
                        Forms\Components\DatePicker::make('joined_from')
                            ->label('Joined From'),
                        Forms\Components\DatePicker::make('joined_until')
                            ->label('Joined Until'),
                    ])
                    ->query(function ($query, array $data): mixed {
                        return $query
                            ->when(
                                $data['joined_from'],
                                fn($query, $date): mixed => $query->whereDate('joined_date', '>=', $date),
                            )
                            ->when(
                                $data['joined_until'],
                                fn($query, $date): mixed => $query->whereDate('joined_date', '<=', $date),
                            );
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('emp_id')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('emp_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_title')
                    ->label('Position')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('org_department')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('joined_date')
                    ->label('Join Date')
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export Employees')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($livewire) {
                        // Get the current query from the table
                        $query = $livewire->getFilteredTableQuery();

                        // Generate filename with timestamp
                        $filename = 'employees_' . now()->format('Y-m-d_His') . '.xlsx';

                        return Excel::download(
                            new EmployeesExport($query),
                            $filename
                        );
                    })
                    ->tooltip('Export to Excel')
                    ->color('success'),// ...
            ])
            ->striped();
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
            'index' => Pages\ListEmployees::route('/'),
            'view' => Pages\ViewEmployee::route('/{record}'),
        ];
    }
}
