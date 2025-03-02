<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesActivity extends Model
{
    protected $fillable = [
        'activity_type', // meeting, call, email, etc.
        'description',
        'date',
        'outcome',
        'next_action',
        'next_action_date',
        'sales_opportunity_id',
        'user_id',
        'quotation_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'next_action_date' => 'datetime',
    ];

    public function salesOpportunity(): BelongsTo
    {
        return $this->belongsTo(SalesOpportunity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }
}
