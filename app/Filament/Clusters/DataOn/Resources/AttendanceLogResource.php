<?php

namespace App\Filament\Clusters\DataOn\Resources;

use App\Filament\Clusters\DataOn;
use App\Filament\Clusters\DataOn\Resources\AttendanceLogResource\Pages;
use App\Filament\Clusters\DataOn\Resources\AttendanceLogResource\RelationManagers;
use App\Models\AttendanceLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Components\Tab;
use App\Models\Employee;
use App\Models\Company;
use App\Models\FpLocation;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\MultiSelectFilter;

use Illuminate\Support\Arr;

class AttendanceLogResource extends Resource
{
    protected static ?string $model = AttendanceLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = DataOn::class;
    protected static ?string $navigationGroup = 'LD';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sn')
                    ->label('Serial No')
                    ->required()
                    ->maxLength(30),
                Forms\Components\DateTimePicker::make('scan_date')
                    ->required(),
                Forms\Components\TextInput::make('pin')
                    ->label('Emp ID')
                    ->required()
                    ->maxLength(32),
                Forms\Components\TextInput::make('source')
                    ->required()
                    ->maxLength(4),
                Forms\Components\TextInput::make('inoutmode')
                    ->required()
                    ->maxLength(11),
                Forms\Components\TextInput::make('hash')
                    ->maxLength(98),
                Forms\Components\DateTimePicker::make('timestamp')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('short_name')
                    ->label('Entity')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sn')
                    ->label('Serial No')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable(),

                Tables\Columns\TextColumn::make('scan_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pin')
                    ->label('Employee ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('employee.emp_name')
                    ->label('Employee Name')
                    ->searchable(),

/*                 Tables\Columns\TextColumn::make('source')
                    ->searchable(),
                Tables\Columns\TextColumn::make('inoutmode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('hash')
                    ->searchable(),
                Tables\Columns\TextColumn::make('timestamp')
                    ->dateTime()
                    ->sortable(), */
            ])
            ->filters([
                //
                Tables\Filters\Filter::make('scan_date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('scan_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('scan_date', '<=', $date),
                            );
                    }),

                // Multiple select filter for employees
                Tables\Filters\SelectFilter::make('pin')
                    ->multiple()
                    ->label('Employees')
                    ->relationship('employee', 'emp_name', fn(Builder $query) => $query->select(['id', 'emp_name', 'emp_id'])
                        ->orderBy('emp_name'))
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->emp_name} ({$record->emp_id})")
                    ->preload(),

                // Multiple select filter for companies
                Tables\Filters\SelectFilter::make('company')
                    ->multiple()
                    ->label('Companies')
                    ->relationship('company', 'short_name', fn(Builder $query) => $query->select(['id', 'short_name']))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['values'],
                            fn(Builder $query, $companies): Builder => $query->whereIn('sn', function ($query) use ($companies) {
                                $query->select('sn')
                                    ->from('fp_locations')
                                    ->whereIn('company_id', $companies);
                            })
                        );
                    })
                    ->preload(),

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                //Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                /* Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]), */
            ])
            ->defaultSort('scan_date', 'desc');;
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
            'index' => Pages\ListAttendanceLogs::route('/'),
            //'create' => Pages\CreateAttendanceLog::route('/create'),
            'view' => Pages\ViewAttendanceLog::route('/{record}'),
            //'edit' => Pages\EditAttendanceLog::route('/{record}/edit'),
        ];
    }
    
}
