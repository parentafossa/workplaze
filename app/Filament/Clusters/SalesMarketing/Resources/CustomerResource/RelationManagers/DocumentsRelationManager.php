<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\DocumentStatus;
use Illuminate\Support\Carbon;
use App\Models\DocumentType;
use Closure;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';
    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('document_type_id')
                            ->relationship('documentType', 'name', fn ($query) => $query->where('is_active', true))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->form->clearState()),
                            
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('document_number')
                            ->maxLength(255),
                            
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\DatePicker::make('valid_from')
                                    ->required(fn (Forms\Get $get): bool => 
                                        DocumentType::find($get('document_type_id'))
                                            ?->requires_validity_control ?? false
                                    )
                                    ->visible(fn (Forms\Get $get): bool => 
                                        DocumentType::find($get('document_type_id'))
                                            ?->requires_validity_control ?? false
                                    ),
                                    
                                Forms\Components\DatePicker::make('valid_until')
                                    ->required(fn (Forms\Get $get): bool => 
                                        DocumentType::find($get('document_type_id'))
                                            ?->requires_validity_control ?? false
                                    )
                                    ->visible(fn (Forms\Get $get): bool => 
                                        DocumentType::find($get('document_type_id'))
                                            ?->requires_validity_control ?? false
                                    )
                                    ->after('valid_from')
                                    ->rules([
                                        fn (Forms\Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                            if ($get('valid_from') && $value) {
                                                $validFrom = Carbon::parse($get('valid_from'));
                                                $validUntil = Carbon::parse($value);
                                                if ($validFrom->greaterThanOrEqualTo($validUntil)) {
                                                    $fail('Valid until date must be after valid from date.');
                                                }
                                            }
                                        },
                                    ]),
                            ])
                            ->columns(2),
                            
                        Forms\Components\FileUpload::make('file_path')
                            ->required()
                            ->disk('public')
                            ->directory('customer-documents'),
                            
                        Forms\Components\Select::make('tags')
                            ->multiple()
                            ->relationship('tags', 'name')
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'business_area' => 'Business Area',
                                        'business_type' => 'Business Type',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('description')
                                    ->maxLength(255),
                            ]),
                            
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('status')
                            ->options(DocumentStatus::toArray())
                            ->default(DocumentStatus::ACTIVE->value)
                            ->required(),
                    ])
                    ->columns(2)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('documentType.name')
                    ->label('Document Type')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('document_number')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('valid_from')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('valid_until')
                    ->date()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record): string => 
                        $record->documentType->requires_validity_control && $record->valid_until
                            ? (Carbon::parse($record->valid_until)->isPast()
                                ? 'danger'
                                : (Carbon::parse($record->valid_until)
                                    ->subDays($record->documentType->notification_days_before)
                                    ->isPast()
                                    ? 'warning'
                                    : 'success'))
                            : 'gray'
                    ),

                Tables\Columns\TextColumn::make('businessAreaTags.short_name')
                    ->label('Business Areas')
                    ->badge()
                    ->colors(['primary'])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('businessTypeTags.short_name')
                    ->label('Business Types')
                    ->badge()

                    ->colors(['primary'])
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($record) => $record->status->getColor()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type_id')
                    ->relationship('documentType', 'name')
                    ->label('Document Type')
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->options(DocumentStatus::toArray()),
                    
                Tables\Filters\Filter::make('expires_soon')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('documentType', function ($query) {
                            $query->where('requires_validity_control', true);
                        })->where(function ($query) {
                            $query->whereRaw('DATE_SUB(valid_until, INTERVAL (SELECT notification_days_before FROM document_types WHERE id = document_type_id) DAY) <= ?', [now()])
                                ->where('valid_until', '>', now())
                                ->where('status', DocumentStatus::ACTIVE->value);
                        });
                    })
                    ->label('Expires Soon'),
                    
                Tables\Filters\Filter::make('expired')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('documentType', function ($query) {
                            $query->where('requires_validity_control', true);
                        })->where('valid_until', '<', now())
                            ->where('status', DocumentStatus::ACTIVE->value);
                    }),
                    
                Tables\Filters\SelectFilter::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function ($record) {
                        // Ensure file_path is an array
                        $files = is_array($record->file_path) ? $record->file_path : json_decode($record->file_path, true);

                        if (!$files || empty($files)) {
                            return; // No files to download
                        }

                        if (count($files) === 1) {
                            // Single file: Open in a new tab
                            return redirect(asset('storage/' . $files[0]));
                        }

                        // Multiple files: Return a response with all download links
                        return response()->json([
                            'message' => 'Multiple files available',
                            'files' => array_map(fn($file) => asset('storage/' . $file), $files),
                        ]);
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
