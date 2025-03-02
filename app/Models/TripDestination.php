<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripDestination extends Model
{
    use HasFactory;

    protected $table = 'trip_destinations';

    protected $fillable = [
        'name',
    ];

    public function origins()
    {
        return $this->hasMany(TripDistance::class, 'origin');
    }

    public function destinations()
    {
        return $this->hasMany(TripDistance::class, 'destination');
    }

/*     protected static function booted()
    {
        static::saving(function ($model) {
            $model->name = strtolower($model->name); // Always save in lowercase
        });
    } */
}
