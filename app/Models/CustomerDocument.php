<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;

class CustomerDocument extends Model
{
    protected $fillable = [
        'entity_id',
        'customer_id',
        'document_type_id',
        'title',
        'document_number',
        'valid_from',
        'valid_until',
        'is_linked',
        'quotation_id',
        'file_path',
        'notes',
        'status',
    ];


    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'status' => DocumentStatus::class,
        'notes' => 'string',
        'file_path' => 'array',
        'is_linked' => 'boolean',
    ];
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(DocumentTag::class);
    }

    public function isExpired(): bool
    {
        if (!$this->documentType->requires_validity_control || !$this->valid_until) {
            return false;
        }

        return $this->valid_until->isPast();
    }

    public function isExpiringSoon(): bool
    {
        if (!$this->documentType->requires_validity_control || 
            !$this->valid_until || 
            !$this->documentType->notification_days_before) {
            return false;
        }

        return now()->addDays($this->documentType->notification_days_before)
                   ->greaterThanOrEqualTo($this->valid_until);
    }
    
    public function setBusinessAreaTagsAttribute($value)
    {
        if (!$value) return;
        
        $existingTagIds = $this->tags()
            ->where('type', '!=', 'business_area')
            ->pluck('document_tags.id');
            
        $this->tags()->sync(array_merge($existingTagIds->toArray(), $value));
    }

    public function setBusinessTypeTagsAttribute($value)
    {
        if (!$value) return;
        
        $existingTagIds = $this->tags()
            ->where('type', '!=', 'business_type')
            ->pluck('document_tags.id');
            
        $this->tags()->sync(array_merge($existingTagIds->toArray(), $value));
    }

    public function getBusinessAreaTagsAttribute()
    {
        return $this->tags()
            ->where('type', 'business_area')
            ->pluck('document_tags.id')
            ->toArray();
    }

    public function getBusinessTypeTagsAttribute()
    {
        return $this->tags()
            ->where('type', 'business_type')
            ->pluck('document_tags.id')
            ->toArray();
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function businessAreaTags(): BelongsToMany
    {
        return $this->belongsToMany(DocumentTag::class)
            ->where('document_tags.type', 'business_area');
    }

    public function businessTypeTags(): BelongsToMany
    {
        return $this->belongsToMany(DocumentTag::class)
            ->where('document_tags.type', 'business_type');
    }
    /* public static function shouldCheckPolicyToAccess(): bool
    {
        return false;
    }

    protected function beforeSave(): void
    {
        // Save business area tags
        if ($businessAreaTags = $this->data['business_area_tags']) {
            $existingTagIds = $this->record?->tags()
                ->where('type', '!=', 'business_area')
                ->pluck('document_tags.id') ?? collect();

            $this->record->tags()->sync(
                array_merge($existingTagIds->toArray(), (array) $businessAreaTags)
            );
        }

        // Save business type tags
        if ($businessTypeTags = $this->data['business_type_tags']) {
            $existingTagIds = $this->record?->tags()
                ->where('type', '!=', 'business_type')
                ->pluck('document_tags.id') ?? collect();

            $this->record->tags()->sync(
                array_merge($existingTagIds->toArray(), (array) $businessTypeTags)
            );
        }
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn($value) => is_string($value) ? DocumentStatus::from($value) : $value,
            set: fn($value) => $value instanceof DocumentStatus ? $value->value : $value,
        );
    } */

    /**
     * Get the quotation that owns the CustomerDocument
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id', 'id');
    }
}
