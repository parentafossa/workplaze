<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverLog extends Model
{
    use HasFactory;

    protected $table = 'emp_driverlogs';
        
    protected $fillable = [
        'emp_id',
        'assignment_id',
        'driveraction_id',
        'driveraction_type',
        'driver_timestamp',
		'driver_manual_time',
        'truck_no',
        'device_info',
        'latitude',
        'longitude',
        'accuracy',
        'altitude',
        'speed',
        'address',
        'remark'
    ];

    // Each driver log belongs to a driver trip assignment
    public function assignment()
    {
        return $this->belongsTo(DriverTripAssignment::class, 'emp_id', 'driver_id');
    }

    // Check if the log starts or ends a trip and update status
    public function updateTripStatus()
    {
        $trip = $this->assignment->trip;

        if ($this->status == 2) {
            $trip->status = 'in-progress';
        } elseif ($this->status == 3) {
            $trip->updateTripStatus();
        } else {
            $trip->status = 'pending';
        }

        $trip->save();
    }

    // In App\Models\DriverLog.php

    public function activity()
    {
        return $this->belongsTo(DriverActivity::class, 'driveraction_id');
    }
    public function driver()
    {
        return $this->belongsTo(Driver::class,'driver_id','emp_id');
    }
}
