<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\HasApproval;
use App\Traits\HasCashAdvanceNumber;

class CashAdvanceRequest extends Model
{
    use HasFactory;
    use HasApproval;
    use HasCashAdvanceNumber;

    protected $fillable = ['driver_trip_assignment_id',
    'emp_id', 
    'ca_no',
    'submit_date',
    'plan_use_date',
    'plan_usage',
    'cash_advance_type',
    'bank_name',
    'bank_account_no',
    'bank_account_name',
    'amount', 'status', 'description','approval_flow_id'];

    // Each request can have many usages
    public function usages()
    {
        return $this->hasMany(CashAdvanceUsage::class);
    }

    // Calculate the remaining/exceeding balance
    public function getRemainingBalanceAttribute()
    {
        $totalUsed = $this->usages->sum('amount');
        return $this->amount - $totalUsed;
    }

        public function driverTripAssignment(): BelongsTo
    {
        return $this->belongsTo(DriverTripAssignment::class);
    }

    public function cashAdvanceUsages(): HasMany
    {
        return $this->hasMany(CashAdvanceUsage::class, 'cash_advance_request_id');
    }

    public function getCompanyIdAttribute()
    {
        if ($this->emp_id) {
            return Employee::find($this->emp_id)->company_id;
        }
        return null;
    }
}
