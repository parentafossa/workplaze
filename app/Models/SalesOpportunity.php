<?php

namespace App\Models;

use App\Enums\WarehouseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SalesOpportunity extends Model
{
    protected $fillable = [
        'entity_id',
        'title',
        'is_new_customer',
        'customer_name',
        'customer_id',
        'estimated_value',
        'expected_closing_date',
        'status', // new, in_progress, quotation_phase, won, lost
        'description',
        'user_id',
        'emp_id',
        'gross_profit',
        'volume',
        'warehouse_type',
        'warehouse_address',
        'commodity',
        'commodity_value'
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'expected_closing_date' => 'date',
        'is_new_customer' => 'boolean',
        'warehouse_type' => WarehouseType::class,
    ];

    public function activities(): HasMany
    {
        return $this->hasMany(SalesActivity::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
}
