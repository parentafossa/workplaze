<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\QuotationType;

class Quotation extends Model
{
    protected $fillable = [
        'quotation_number',
        'subject',
        'amount',
        'validity_start_date',
        'validity_end_date',
        'status', // draft, sent, confirmed, rejected, expired
        'sales_opportunity_id',
        'file_path',
        'user_id',
        'notes',
        'gross_profit',
        'quotation_type',
        'signee',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'validity_start_date' => 'date',
        'validity_end_date' => 'date',
        'file_path' => 'array',
        'gross_profit' => 'decimal:2',
        'quotation_type' => QuotationType::class,
    ];

    public function salesOpportunity(): BelongsTo
    {
        return $this->belongsTo(SalesOpportunity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toRoman($month) {
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 
            6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 
            11 => 'XI', 12 => 'XII'
        ];
        return $romanMonths[$month] ?? '';
    }

    public function signee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'signee');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'signee', 'emp_id');
    }
    public function salesActivity(): BelongsTo
    {
        return $this->belongsTo(SalesActivity::class);
    }

}
