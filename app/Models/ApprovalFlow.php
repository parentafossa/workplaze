<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalFlow extends Model
{
    protected $fillable = ['name', 'description', 'model_type', 'steps', 'is_active'];
    
    protected $casts = [
        'steps' => 'array',
        'is_active' => 'boolean'
    ];

    public function instances()
    {
        return $this->hasMany(ApprovalInstance::class);
    }    //
}
