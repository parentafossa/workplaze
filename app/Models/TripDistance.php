<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripDistance extends Model
{
    use HasFactory;

    protected $table = 'trip_distances';

    protected $fillable = [
        'origin',
        'destination',
        'distance',
        'remark',
    ];

    public function originLocation()
    {
        return $this->belongsTo(TripDestination::class, 'origin');
    }

    public function destinationLocation()
    {
        return $this->belongsTo(TripDestination::class, 'destination');
    }

    public function getRouteAttribute()
    {
        // Ensure we have loaded the related models
        $origin = $this->originLocation->name ?? 'Unknown Origin';
        $destination = $this->destinationLocation->name ?? 'Unknown Destination';

        return "{$origin} -> {$destination}";
    }
}
