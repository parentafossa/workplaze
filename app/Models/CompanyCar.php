<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyCar extends Model
{
    protected $table = 'm_companycars';

    protected $fillable = ['business_area', 'license_plate', 'car_brand','car_color','type','active'];
}
