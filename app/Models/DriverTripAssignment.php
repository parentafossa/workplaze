<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverTripAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['trip_id', 'driver_id'];

    // A driver trip assignment belongs to a driver
    public function driver()
    {
        return $this->belongsTo(Driver::class,'driver_id','emp_id');
    }

    // A driver trip assignment belongs to a trip
    public function trip()
    {
        return $this->belongsTo(DriverTrip::class,'trip_id');
    }

    // Each assignment can have many cash advance requests
    public function cashAdvanceRequests()
    {
        return $this->hasMany(CashAdvanceRequest::class)
        ->where('driver_trip_assignment_id', $this->attributes['trip_id']);
        
    }

    // Each assignment can have many logs
    public function logs()
    {
        return $this->hasMany(DriverLog::class, 'emp_id', 'driver_id')
        ->where('assignment_id', $this->attributes['trip_id']);
    }
}
