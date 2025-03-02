<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'requires_validity_control',
        'notification_days_before',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'requires_validity_control' => 'boolean',
        'notification_days_before' => 'integer',
        'is_active' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }
}
