<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DriverTripAssignment;
use App\Models\Driver;

class DriverTrip extends Model
{
    use HasFactory;

    protected $fillable = ['trip_name', 'truck_no', 'begin_date', 'destinations', 'status'];

    protected $casts = [
        'destinations' => 'array',
    ];
    // A trip can be assigned to multiple drivers
    public function drivers()
    {
        return $this->hasManyThrough(Driver::class, DriverTripAssignment::class, 'trip_id', 'emp_id', 'id', 'driver_id');
        //return $this->hasMany(Driver::class);
    }

    // A trip can have many assignments
    public function assignments()
    {
        return $this->hasMany(DriverTripAssignment::class, 'trip_id');
    }

    // Update trip status based on driver logs
    public function updateTripStatus()
    {
        $allLogs = $this->assignments->flatMap(function($assignment) {
            return $assignment->logs;
        });

        if ($allLogs->every(fn($log) => $log->status == 3)) {
            $this->status = 'completed';
        } elseif ($allLogs->contains(fn($log) => $log->status == 2)) {
            $this->status = 'in-progress';
        } else {
            $this->status = 'pending';
        }
        
        $this->save();
    }

     public function getDestinationRoutesAttribute()
    {
        // Fetch all TripDistance models based on the IDs in the destinations array

        $destinationIds = $this->destinations ?? [];

        return TripDistance::whereIn('id', $this->destinations)
            ->get()
            ->map(fn($tripDistance) => $tripDistance->route)
            //->join(', ')
            ->toArray();
    } 

    public function getDestinationsAttribute($value)
{
    return $value ? json_decode($value, true) : [];
}
}
