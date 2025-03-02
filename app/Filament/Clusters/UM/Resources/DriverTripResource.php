<?php

namespace App\Filament\Clusters\UM\Resources;

use App\Filament\Clusters\UM;

use App\Filament\Clusters\UM\Resources\DriverTripResource\Pages;
use App\Filament\Clusters\UM\Resources\DriverTripResource\RelationManagers;

use App\Models\DriverTrip;
//use App\Models\DriverLog;
use App\Models\DriverActivity;
use App\Models\Driver;
use App\Models\CompanyCar;
use App\Models\CashAdvanceRequest;
use App\Models\DriverTripAssignment;
use App\Models\TripDistance;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DateTimePicker;

use Filament\Resources\Resource;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Filament\Support\Enums\Alignment;

use Closure;

#use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;

class DriverTripResource extends Resource
{
    protected static ?string $model = DriverTrip::class;
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $cluster = UM::class;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('trip_name')
                    ->columnspan(2)
                    ->disabled(function (Get $get) {
                        $status = $get('status');
                        return $status !== 'pending';
                    })
                    ->live()
                    ->required(),
                Select::make('truck_no')
                    ->options(CompanyCar::query()->pluck('license_plate','license_plate'))
                    ->disabled(function (Get $get) {
                        $status = $get('status');
                        return $status !== 'pending';
                    })
                    ->searchable()
                    ->live()
                    ->columnspan(1)->required(),
                DatePicker::make('begin_date')
                    ->columnspan(1)
                    ->disabled(function (Get $get) {
                        $status = $get('status');
                        return $status !== 'pending';
                    })
                    ->afterStateUpdated(function ($state, Set $set) {
                        // Update plan_use_date in each cashAdvanceRequest when begin_date changes
                        $set('assignments.*.cashAdvanceRequests.*.plan_use_date', $state);
                    })
                    ->live()
                    ->required(),
                Select::make('destinations')
                    ->label('Trip Route')
                    ->options(TripDistance::all()->pluck('route', 'id'))
                    ->searchable()
                    ->multiple()
                    ->columnspan(3),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in-progress' => 'In Progress',
                        'waiting-settle' => 'Waiting Settlement',
                        'completed' => 'Completed',
                        'canceled' => 'Canceled',
                    ])
                    ->default('pending')
                    ->disabled(function (Get $get) {
                        $status = $get('status');
                        return $status !== 'pending';
                    })
                    ->columnspan(1)
                    ->required(),
                Repeater::make('assignments')
                    ->relationship('assignments')
                    ->schema([
                        Select::make('driver_id')
                            ->reactive()
                            ->label('Driver Name')
                            ->options(Driver::query()->pluck('emp_name', 'emp_id'))
                            ->disabled(function (Get $get) {
                                $assignmentId = $get('id');

                                // If assignmentId is not null and there are related cashAdvanceRequests in the database, disable the field
                                if ($assignmentId) {
                                    return CashAdvanceRequest::where('driver_trip_assignment_id', $assignmentId)->exists();
                                }

                                // If assignmentId is null (not yet created), allow editing
                                return false;
                            })
                            ->searchable()
                            ->required()
                            ->live(onBlur:true)
                            ->columnspan(2)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($state) {
                                    $driver = Driver::find($state);
                                    if ($driver) {
                                        // Set the default bank information for new cash advance requests
                                        $set('cashAdvanceRequests.*.bank_account_name', $driver->idr_bankaccountname);
                                        $set('cashAdvanceRequests.*.bank_account_no', $driver->idr_bankaccountno);
                                        $set('cashAdvanceRequests.*.bank_name', $driver->idr_bankname);
                                    }
                                }
                            }),
                        Repeater::make('cashAdvanceRequests')
                            ->relationship('cashAdvanceRequests')
                            ->schema([
                                Section::make(Function (Get $get) {
                                        // Set the default plan_use_date to the value of begin_date
                                        return $get('ca_no');
                                    })
                                ->id('ca_id')
                                ->collapsible()
                                ->collapsed()
                                //->persistCollapsed()
                                ->schema([
                                TextInput::make('ca_no')
                                    ->label('Cash Advance Number')
                                    ->default(function () {
                                            
                                        $lastId = CashAdvanceRequest::max('id') ?? 0;
                                        $sequence = str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
                                        $currentMonth = now()->format('n'); // Get month number (1-12)
                                        
                                        // Convert month to Roman numerals
                                        $romanMonths = [
                                            1 => 'I',
                                            2 => 'II',
                                            3 => 'III',
                                            4 => 'IV',
                                            5 => 'V',
                                            6 => 'VI',
                                            7 => 'VII',
                                            8 => 'VIII',
                                            9 => 'IX',
                                            10 => 'X',
                                            11 => 'XI',
                                            12 => 'XII'
                                        ];
                                        
                                        $romanMonth = $romanMonths[$currentMonth];
                                        $currentYear = now()->format('Y');
                                        
                                        return "ADV-{$sequence}/CA/LID/SBY/{$romanMonth}/{$currentYear}";
                                        })
                                    ->required()
                                    ->reactive()
                                    ->columnspan(2),
                                DatePicker::make('submit_date')
                                    ->default(now())
                                    ->required()
                                    ->columnspan(2),
                                DatePicker::make('plan_use_date')
                                    ->default(function (Get $get) {
                                        // Set the default plan_use_date to the value of begin_date
                                        return $get('../../../../begin_date');
                                    })
                                    ->required()
                                    ->columnspan(2),
                                Select::make('plan_usage')
                                    ->options([
                                        'um_operation' => 'UM Operation',
                                        'car_operation' => 'Car Operation',
                                        'entertainment' => 'Entertainment',
                                        'other' => 'Other',
                                    ])
                                    ->default('um_operation')
                                    ->columnspan(3),
                                Select::make('cash_advance_type')
                                    ->options([
                                        'necessary' => 'Necessary',
                                        'not_necessary' => 'Not Necessary',
                                    ])
                                    ->default('necessary')
                                    ->columnspan(3),
                                TextInput::make('bank_name')
                                ->live()
                                ->disabled() 
                                ->columnspan(1)
                                ->default(function (Get $get) {
                                        $driverId = $get('../../driver_id');
                                        return $driverId ? Driver::where('emp_id', $driverId)->value('idr_bankname') : null;
                                    }),
                                TextInput::make('bank_account_no')                               
                                ->live()
                                ->disabled() 
                                ->default(function (Get $get) {
                                        $driverId = $get('../../driver_id');
                                        return $driverId ? Driver::where('emp_id', $driverId)->value('idr_bankaccountno') : null;
                                    })
                                ->columnspan(2),
                                TextInput::make('bank_account_name')
                                ->live()
                                ->disabled() 
                                ->default(function (Get $get) {
                                        $driverId = $get('../../driver_id');
                                        return $driverId ? Driver::where('emp_id', $driverId)->value('idr_bankaccountname') : null;
                                    })
                                ->columnspan(2),
                                TextInput::make('amount')
                                    ->numeric()
                                    //->mask('999,999,999,999')
                                    ->prefix('Rp')
                                    ->inputMode('decimal')
                                    ->minValue(0)
                                    ->default(0)
                                    ->maxValue(20000000)
                                    ->columnspan(1),
                                TextArea::make('description')
                                ->columnspan(6),
                                ]),
                            ])
                            ->defaultItems(0)
                            ->columnspan(10)
                            ->columns(6)
                            ->addActionLabel('Add Cash Advance')
                            ->addActionAlignment(addActionAlignment: Alignment::End)
                            ->hidden(fn (Get $get): bool => !filled($get('driver_id'))),
                        Section::make('Driver Logs')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                            TableRepeater::make('logs')
                                ->emptyLabel('No Driver Activity recorded.')
                                ->relationship('logs')
                                //->renderHeader(false)
                                ->streamlined()
                                ->headers([
                                    Header::make('Driver Action')->width('200px'),
                                    Header::make('Action Type')->width('250px'),
                                    Header::make('Time')->width('200px'),
                                    Header::make('Remark')->width('200px'),
                                    Header::make('Lat')->width('120px'),
                                    Header::make('Lon')->width('120px'),
                                    Header::make('Acc')->width('80px'),
                                    Header::make('Alt')->width('80px'),
                                    Header::make('Vel')->width('80px'),
                                ]) 
                                ->schema([
                                    Select::make('driveraction_id')
                                        ->label('Action')
                                        ->options(DriverActivity::where('active', 1)->pluck('name', 'id')) // Only active activities
                                        ->required()
                                        ->searchable()
                                        ->columnspan(2),
                                    ToggleButtons::make('driveraction_type')
                                        ->label('Action Type')
                                        ->required()
                                        ->columnspan(3)
                                                                                ->options([
                                            '3' => 'Start/End',
                                            '0' => 'Inactive',
                                            '1' => 'Active'
                                        ])
                                        ->inline()
                                        ->grouped(),
                                    DateTimePicker::make('driver_timestamp')
                                        ->label('Timestamp')

                                        ->required()
                                        ->columnspan(3),
                                    /*
                                    TextInput::make('truck_no')
                                        ->label('Truck No')
                                        ->default(fn(Get $get) => $get('../../../../truck_no'))
                                        ->disabled()
                                        ->hidden()
                                        ->columnspan(1),
                                    TextInput::make('device_info')
                                        ->label('Device Info')
                                        ->columnspan(2)
                                        ->hidden(),
                                        */
                                    TextInput::make('remark')
                                        ->label('remark')
                                        //->hidden()
                                        //->required()
                                        ->columnspan(3),
                                    TextInput::make('latitude')
                                        ->label('Latitude')
                                        ->numeric()
                                        //->hidden()
                                        //->required()
                                        ->columnspan(2),
                                    TextInput::make('longitude')
                                        ->label('Longitude')
                                        ->numeric()
                                        //->hidden()
                                        //->required()
                                        ->columnspan(2),
                                    TextInput::make('accuracy')
                                        ->label('Accuracy')
                                        ->numeric()
                                        //->hidden()
                                        //->required()
                                        ->columnspan(1),
                                    TextInput::make('altitude')
                                        ->label('Altitude')
                                        ->numeric()
                                        //->hidden()
                                        //->required()
                                        ->columnspan(1),
                                    TextInput::make('speed')
                                        ->label('Speed')
                                        ->numeric()
                                        //->hidden()
                                        //->required()
                                        ->columnspan(1),/*
                                    TextInput::make('address')
                                        ->label('Address')
                                        ->hidden()
                                        ->columnspan(span: 5),
                                        */

                                ])
                                ->defaultItems(0)
                                ->columnspan(12)
                                //->collapsed() // Start in collapsed state
                                ->columns(18)
                                ->addActionLabel('Add Driver Log')
                                ,
                            ])

                    ])

                    ->minItems(1)
                    ->maxItems(2)
                    ->columnspan('full')
                    ->columns(12)
                    ->addActionLabel('Add Driver')
                    ->hidden(fn (Get $get): bool => !(filled($get('trip_name')) && filled($get('truck_no')) && filled($get('begin_date'))))
                    ->addActionAlignment(addActionAlignment: Alignment::Start)
                    ->extraItemActions([
                    Action::make('Print Trip')
                        ->icon('heroicon-o-qr-code')
                        ->label('Print Instruction')
                        ->action(function (array $arguments, Repeater $component): void {
                            $itemData = $component->getRawItemState($arguments['item']);
                            $driverTrip = DriverTrip::find($itemData['trip_id']);
                            if ($driverTrip) {
                                // Add DriverTrip information
                                $itemData['trip_name'] = $driverTrip->trip_name;
                                $itemData['truck_no'] = $driverTrip->truck_no;
                                $itemData['begin_date'] = $driverTrip->begin_date;
                            }

                            // Fetch the driver's name using driver_id
                            $driver = Driver::find($itemData['driver_id']);
                            if ($driver) {
                                $itemData['driver_name'] = $driver->emp_name;
                            }

                            //Log::info($itemData);
                            $filePath = self::exportAssignmentAsPdf($itemData);
                            $fileName = basename($filePath);
                            //Log::info('filename: '. $filePath);
                            // Return a download response to initiate file download
                            redirect()->route('download.assignment', ['file' => $fileName]);

                        }),
                ])
            ])
            ->columns(8);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Split::make([
                    Stack::make([
                        TextColumn::make('trip_name')
                            ->label('Trip Name')
                            ->grow(false)
                            ->searchable(),
                        TextColumn::make('truck_no')
                            ->label('Truck No')
                            ->grow(false)
                            ->searchable(),
                        TextColumn::make('begin_date')
                            ->label('Begin Date')
                            ->grow(false)
                            ->sortable(),
                        ])->grow(false),
                
                TextColumn::make('destinations')
                ->label('Trip Route')
                ->getStateUsing(fn ($record) => $record->destination_routes)
                ->sortable()
                ->searchable()
                ->listWithLineBreaks()
                ->bulleted(),
                TextColumn::make('assignments')
                    ->label('Driver & Cash Advances')
                    ->formatStateUsing(function ($state, $record) {
                        $html = '';
                        foreach ($record->assignments as $assignment) {
                            $html .= "<div class='mb-2 text-sm'>";
                            $html .= "<table>";
                            $html .= "<tr>";
                            
                            $html .= "{$assignment->driver->emp_name}<br>";
                            $html .= "<div class='text-sm text-gray-500 ml-4'>";
                                
                            // Only render cashAdvanceRequests if there are any
                            if ($assignment->cashAdvanceRequests->count() > 0) {
                                foreach ($assignment->cashAdvanceRequests as $ca) {
                                    // Generate the URL to the CashAdvanceRequest edit page
                                    $editUrl = url("app/u-m/cash-advance-requests/{$ca->id}/edit");
                                    
                                    // Format the remaining balance
                                    $remainingBalance = 'Rp' . number_format($ca->remaining_balance, 2);

                                    // Display the link to CA# with amount and remaining balance
                                    $html .= "<a href='{$editUrl}' class='text-blue-500 underline'>{$ca->ca_no}</a>";
                                    $html .= " (CA Rp" . number_format($ca->amount, 2);
                                    $html .= "/ {$remainingBalance})<br>";
                                }
                            }else{
                                $html .= "<a href='' class='text-blue-500 underline'>No Cash Advance</a>";
                                //$html .= " (CA Rp: Rp 0.00";
                                //$html .= "<br>Remain : Rp 0.00<br>";
                            
                            }
                            $html .= "</div>";
                            $html .= "</tr>";
                            $html .= "</table>";
                            $html .= "</div>";
                        }
                        return $html;
                    })
                    ->html(),
                TextColumn::make('status')->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'gray',
                    'in-progress' => 'warning',
                    'completed' => 'success',
                    'canceled' => 'danger',
                    'waiting-settle' => 'warning',
                    'canceled' => 'danger',
                }),
                ]),
            ])
            ->filters([
                //
                Filter::make('truck_no')
                ->label('Truck No')
                ->form([
                    Select::make('truck_no')
                        ->label('Select Truck No')
                        ->options(CompanyCar::query()->pluck('license_plate', 'license_plate'))
                        ->multiple()
                        ->preload()
                ])
                ->query(function (Builder $query, array $data) {
                    //Log::info($data);
                    return $query->when($data['truck_no'] ?? null, 
                    fn($query, $truckNos) => $query->whereIn('truck_no', $truckNos));
                }),
                
                Filter::make('driver_id')
                ->label('Driver')
                ->form([
                    Select::make('driver_id')
                        ->label('Select Driver')
                        ->options(Driver::query()->pluck('emp_name', 'emp_id'))
                        ->multiple()
                        ->preload()
                ])
                ->query(function (Builder $query, array $data) {
                    return $query->when($data['driver_id'] ?? null, function ($query, $driverIds) {
                        $query->whereHas('assignments', function ($query) use ($driverIds) {
                            $query->whereIn('driver_id', $driverIds);
                        });
                    });
                }),

                Filter::make('status')
                ->label('Status')
                ->form([
                    Select::make('status')
                        ->label('Select Status')
                        ->options([
                            'pending' => 'Pending',
                            'in-progress' => 'In Progress',
                            'waiting-settle' => 'Waiting Settlement',
                            'completed' => 'Completed',
                            'canceled' => 'Canceled',
                        ])
                        ->multiple()
                        ->preload(),
                ])
                ->query(function (Builder $query, array $data) {
                    //Log::info($data);
                    return $query->when($data['status'] ?? null, 
                    fn($query, $truckNos) => $query->whereIn('status', $truckNos));
                }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('Export Assignments')
                    ->label('Download Assignments')
                    ->action(function (Collection $records) {
                        // Call method to generate combined PDF for selected DriverTrips
                        $filePath= self::exportAssignmentAsPdfMany($records);
                        $fileName = basename($filePath);
                        redirect()->route('download.assignment', ['file' => $fileName]);
                    })
                    ->icon('heroicon-o-printer'),
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
            'index' => Pages\ListDriverTrips::route('/'),
            'create' => Pages\CreateDriverTrip::route('/create'),
            'edit' => Pages\EditDriverTrip::route('/{record}/edit'),
        ];
    }

    protected static function exportAssignmentAsPdfMany(Collection $records)
    {
        $allAssignmentsData = []; // Initialize an array to hold all assignment data

        foreach ($records as $record) {
            // Get all assignments related to the current DriverTrip record
            $assignments = DriverTripAssignment::where('trip_id', $record->id)->get();

            foreach ($assignments as $assignment) {
                $itemData = [
                    'trip_id' => $record->id,
                    'trip_name' => $record->trip_name,
                    'truck_no' => $record->truck_no,
                    'begin_date' => $record->begin_date,
                    'driver_id' => $assignment->driver_id,
                    'assignment_id' => $assignment->id,
                    'created_at' => $assignment->created_at,
                    'updated_at' => $assignment->updated_at,
                ];

                // Retrieve driver name
                $driver = Driver::find($assignment->driver_id);
                if ($driver) {
                    $itemData['driver_name'] = $driver->emp_name;
                } else {
                    $itemData['driver_name'] = 'Unknown';
                }

                // Get cash advance requests related to the assignment
                $itemData['cashAdvanceRequests'] = $assignment->cashAdvanceRequests()->get()->toArray();

                // Add this assignment's data to the collection of all assignments
                $allAssignmentsData[] = $itemData;
            }
        }

        //Log::info('Assignment Data',$allAssignmentsData);

        $tripsData = [];

        foreach ($allAssignmentsData as $assignment) {
            $tripId = $assignment['trip_id'];

            // Initialize trip data if it doesn't exist yet
            if (!isset($tripsData[$tripId])) {
                $tripsData[$tripId] = [
                    'trip_id' => $assignment['trip_id'],
                    'trip_name' => $assignment['trip_name'],
                    'truck_no' => $assignment['truck_no'],
                    'begin_date' => $assignment['begin_date'],
                    'assignments' => [],
                ];
            }

            // Generate QR code for each assignment
            $qrData = json_encode([
                'assignment_id' => $assignment['assignment_id'],
                'trip_id' => $assignment['trip_id'],
                'driver_id' => $assignment['driver_id'],
                'truck_no' => $assignment['truck_no'],
                //'cashAdvanceRequests' => $assignment['cashAdvanceRequests'],
            ]);

            $qrCode = QrCode::format('png')->size(200)->generate($qrData);
            $qrCodePath = storage_path("app/public/qr_codes/assignment_{$assignment['assignment_id']}.png");
            Storage::disk('public')->put("qr_codes/assignment_{$assignment['assignment_id']}.png", $qrCode);

            // Add the QR code path to the assignment data
            $assignment['qrCodePath'] = $qrCodePath;

            // Append the assignment to the respective trip
            $tripsData[$tripId]['assignments'][] = $assignment;
        }
        $timestamp = now()->format('Ymd_His');
        // Step 2: Generate the PDF with grouped data
        $pdf = Pdf::loadView('pdf.multi_trip_report', ['tripsData' => $tripsData])
            ->setPaper('a4');

        // Define the file path for the combined PDF
        $pdfFilePath = storage_path("app/public/reports/all_trips_assignments_report_{$timestamp}.pdf");

        // Save the PDF to the specified path
        $pdf->save($pdfFilePath);

        return $pdfFilePath;
    }
    protected static function exportAssignmentAsPdf(array $assignmentData)
    {
        // Generate QR Code URL based on assignment ID
        $qrData = json_encode([
            'id' => $assignmentData['id'],
            'trip_id' => $assignmentData['trip_id'],
            'driver_id' => $assignmentData['driver_id'],
            'truck_no' => $assignmentData['truck_no'],
            //'cashAdvanceRequests' => $assignmentData['cashAdvanceRequests'],
        ]);

        //$qrCodeUrl = route('assignment.show', ['id' => $assignmentData['id']]);
        $qrCode = QrCode::format('png')->size(200)->generate($qrData);
        $qrCodePath = storage_path("app/public/qr_codes/assignment_{$assignmentData['id']}.png");
        Storage::disk('public')->put("qr_codes/assignment_{$assignmentData['id']}.png", $qrCode);

        // Prepare data for the PDF, directly using the passed array structure
        $data = [
            'assignment' => $assignmentData,
            'qrCodePath' => $qrCodePath,
        ];
        $timestamp = now()->format('Ymd_His');
        // Generate PDF from the prepared data
        $pdf = Pdf::loadView('pdf.assignment_report', $data)->setPaper('a4');
        //$pdfPath = storage_path("app/public/reports/assignment_{$assignmentData['id']}.pdf");
        $pdfPath = storage_path("app/public/reports/assignment_{$assignmentData['id']}_{$timestamp}.pdf");
        $pdf->save($pdfPath);

        // Provide download link
        //return response()->download($pdfPath)->deleteFileAfterSend(true);
        return $pdfPath;
    }

}
