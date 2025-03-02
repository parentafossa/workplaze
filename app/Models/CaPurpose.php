<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaPurpose extends Model
{
    use HasFactory;

    protected $table = 'ca_purposes';

    protected $fillable = ['name', 'description'];

    public function usages()
    {
        return $this->hasMany(CashAdvanceUsage::class, 'purpose_id');
    }
}
