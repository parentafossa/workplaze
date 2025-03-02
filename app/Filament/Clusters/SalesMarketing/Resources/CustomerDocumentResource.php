<?php

namespace App\Filament\Clusters\SalesMarketing\Resources;

use App\Filament\Clusters\SalesMarketing;
use App\Filament\Clusters\SalesMarketing\Resources\CustomerDocumentResource\Pages;
use App\Filament\Clusters\SalesMarketing\Resources\CustomerDocumentResource\RelationManagers;
use App\Models\CustomerDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\DocumentStatus;
use App\Models\DocumentType;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Carbon;
use Closure;
use Illuminate\Support\Collection;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Model;

class CustomerDocumentResource extends Resource
{
    protected static ?string $model = CustomerDocument::class;
    protected static ?string $cluster = SalesMarketing::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Customer Document';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Customer Document';
    protected static ?string $pluralModelLabel = 'Customer Documents';
    protected static ?string $navigationGroup = 'Customer Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('entity_id')
                            ->label('Entity')
                            ->relationship('entity', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(['default' => 1, 'sm' => 1]),
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('document_type_id')
                            ->label('Document Type')
                            ->options(
                                DocumentType::query()
                                    ->where('is_active', true)
                                    ->orderBy('sort_order', 'asc')
                                    ->pluck('name', 'id')
                            )
                            ->required()
                            ->reactive()
                            ->searchable()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state)
                                    return;

                                $docType = DocumentType::find($state);
                                if (!$docType?->requires_validity_control) {
                                    $set('valid_from', null);
                                    $set('valid_until', null);
                                }
                            }),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('document_number')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_linked')
                            ->required()
                            ->onColor('success')
                            ->inline(false)
                            ->reactive()
                            ->columnSpan(['default' => 1, 'sm' => 1]),
                        Forms\Components\Select::make('quotation_id')
                            ->label('Quotation')
                            ->relationship(
                                'quotation',
                                'quotation_number',
                                fn($query) => $query
                                    ->where('id', 'not like', '9999%')
                                    ->orderBy('quotation_number')
                            )
                            ->searchable()
                            ->required(fn($get) => $get('is_linked'))
                            ->hidden(fn($get) => !$get('is_linked'))
                            ->preload()
                            ->columnSpan(['default' => 1, 'sm' => 3]),
                        Forms\Components\FileUpload::make('file_path')
                            ->required(fn($get) => !$get('is_linked'))
                            ->label('Document File(s)')
                            ->disk('private')
                            ->directory('customer-documents')
                            ->hidden(fn($get) => $get('is_linked'))
                            ->multiple()
                            ->downloadable()
                            ->preserveFilenames()
                            ->panelLayout('compact')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/*',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            ])
                            ->maxSize(10240), // 10MB

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\DatePicker::make('valid_from')
                                    ->required(
                                        fn(Forms\Get $get): bool =>
                                        DocumentType::find($get('document_type_id'))
                                                ?->requires_validity_control ?? false
                                    )
                                    ->visible(
                                        fn(Forms\Get $get): bool =>
                                        DocumentType::find($get('document_type_id'))
                                                ?->requires_validity_control ?? false
                                    )
                                    ->rules([
                                        fn(Forms\Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                            if (!$value)
                                                return;

                                            $validUntil = $get('valid_until');
                                            if ($validUntil && Carbon::parse($value)->greaterThanOrEqualTo($validUntil)) {
                                                $fail('Valid from date must be before valid until date.');
                                            }
                                        },
                                    ]),

                                Forms\Components\DatePicker::make('valid_until')
                                    ->required(
                                        fn(Forms\Get $get): bool =>
                                        DocumentType::find($get('document_type_id'))
                                                ?->requires_validity_control ?? false
                                    )
                                    ->visible(
                                        fn(Forms\Get $get): bool =>
                                        DocumentType::find($get('document_type_id'))
                                                ?->requires_validity_control ?? false
                                    )
                                    ->after('valid_from')
                                    ->rules([
                                        fn(Forms\Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                            if (!$value)
                                                return;

                                            $validFrom = $get('valid_from');
                                            if ($validFrom && Carbon::parse($validFrom)->greaterThanOrEqualTo($value)) {
                                                $fail('Valid until date must be after valid from date.');
                                            }
                                        },
                                    ]),
                            ])
                            ->columns(2),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('business_area_tags')
                                    ->label('Business Areas')
                                    ->multiple()
                                    ->relationship('tags', 'name', function ($query) {
                                        $query->where('type', 'business_area');
                                    })
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Hidden::make('type')
                                            ->default('business_area'),
                                        Forms\Components\TextInput::make('description')
                                            ->maxLength(255),
                                    ]),

                                Forms\Components\Select::make('business_type_tags')
                                    ->label('Business Types')
                                    ->multiple()
                                    ->relationship('tags', 'name', function ($query) {
                                        $query->where('type', 'business_type');
                                    })
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Hidden::make('type')
                                            ->default('business_type'),
                                        Forms\Components\TextInput::make('description')
                                            ->maxLength(255),
                                    ]),

                            ])
                            ->columns(2),

                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->options(DocumentStatus::toArray())
                            ->default(DocumentStatus::ACTIVE->value)
                            ->required()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state instanceof DocumentStatus) {
                                    $set('status', $state->value);
                                }
                            }),
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entity.short_name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->limit(50),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),

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
                    ->color(
                        fn($record): string =>
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
                    ->color(fn($record) => $record->status->getColor()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('entity_id')
                    ->label('Entity')
                    ->relationship('entity', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

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

                Tables\Filters\SelectFilter::make('business_area_tags')
                    ->label('Business Areas')
                    ->relationship('tags', 'name', function ($query) {
                        $query->where('type', 'business_area');
                    })
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('business_type_tags')
                    ->label('Business Types')
                    ->relationship('tags', 'name', function ($query) {
                        $query->where('type', 'business_type');
                    })
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
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
            ])
            ->bulkActions([
                /*                 Tables\Actions\BulkActionGroup::make([
                                    Tables\Actions\DeleteBulkAction::make(),
                                    Tables\Actions\BulkAction::make('download')
                                        ->icon('heroicon-o-document-arrow-down')
                                        ->action(function (Collection $records) {
                                            // Implementation for bulk download if needed
                                        }),
                                ]), */
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(
                fn(Model $record): string => route('filament.app.sales-marketing.resources.customer-documents.view', ['record' => $record]),
            );
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
            'index' => Pages\ListCustomerDocuments::route('/'),
            'view' => Pages\ViewCustomerDocument::route('/{record}'),
            'create' => Pages\CreateCustomerDocument::route('/create'),
            'edit' => Pages\EditCustomerDocument::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'documentType', 'tags']);
    }

    /*     protected function mutateFormDataBeforeCreate(array $data): array
        {
            // Clean up the business area tags format
            if (isset($data['business_area_tags']) && is_array($data['business_area_tags'])) {
                $data['business_area_tags'] = is_array($data['business_area_tags'][0])
                    ? $data['business_area_tags'][0]
                    : $data['business_area_tags'];
            }

            // Clean up the business type tags format
            if (isset($data['business_type_tags']) && is_array($data['business_type_tags'])) {
                $data['business_type_tags'] = is_array($data['business_type_tags'][0])
                    ? $data['business_type_tags'][0]
                    : $data['business_type_tags'];
            }

            return $data;
        }

        public function afterCreate(): void
        {
            $record = $this->getRecord();

            // Handle business area tags
            if ($businessAreaTags = $this->data['business_area_tags'] ?? null) {
                $tags = is_array($businessAreaTags[0]) ? $businessAreaTags[0] : $businessAreaTags;
                $record->tags()->attach($tags);
            }

            // Handle business type tags
            if ($businessTypeTags = $this->data['business_type_tags'] ?? null) {
                $tags = is_array($businessTypeTags[0]) ? $businessTypeTags[0] : $businessTypeTags;
                $record->tags()->attach($tags);
            }
        } */
}