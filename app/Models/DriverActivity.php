<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverActivity extends Model
{
    protected $table = 'm_driveractivities';

    protected $fillable = ['name', 'name_en', 'name_jp','flag','active','remark'];
}
