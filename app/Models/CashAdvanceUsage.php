<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashAdvanceUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_advance_request_id', 
        'usage_datetime', 
        'amount', 
        'purpose_id', 
        'remarks', 
        'files'];
    
    protected $casts = [
        'files' => 'array',
    ];

    public function cashAdvanceRequest()
    {
        return $this->belongsTo(CashAdvanceRequest::class);
    }

    public function purpose()
    {
        return $this->belongsTo(CaPurpose::class, 'purpose_id');
    }    
}
